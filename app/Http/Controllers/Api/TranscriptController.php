<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTranscriptJob;
use App\Models\Transcript;
use App\Models\Visit;
use App\Models\VisitNote;
use App\Services\AI\ScribeProcessor;
use App\Services\Stt\SpeechToTextProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TranscriptController extends Controller
{
    public function store(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'raw_transcript' => ['required', 'string'],
            'source_type' => ['required', 'in:ambient_phone,ambient_device,manual_upload'],
            'stt_provider' => ['nullable', 'string'],
            'audio_duration_seconds' => ['nullable', 'integer'],
            'patient_consent_given' => ['required', 'boolean'],
            'process' => ['nullable', 'boolean'],
        ]);

        $shouldProcess = $validated['process'] ?? false;
        unset($validated['process']);

        $validated['visit_id'] = $visit->id;
        $validated['patient_id'] = $visit->patient_id;
        $validated['stt_provider'] = $validated['stt_provider'] ?? 'none';
        $validated['audio_duration_seconds'] = $validated['audio_duration_seconds'] ?? 0;
        $validated['processing_status'] = $shouldProcess ? 'processing' : 'pending';
        $validated['consent_timestamp'] = $validated['patient_consent_given'] ? now() : null;

        $transcript = Transcript::create($validated);

        if ($shouldProcess) {
            ProcessTranscriptJob::dispatch($transcript);
        }

        return response()->json(['data' => $transcript], 201);
    }

    public function uploadAudio(Request $request, Visit $visit, SpeechToTextProvider $stt): JsonResponse
    {
        $validated = $request->validate([
            'audio' => ['required', 'file', 'mimes:mp3,mp4,m4a,wav,webm,ogg', 'max:25600'],
            'source_type' => ['required', 'in:ambient_phone,ambient_device,manual_upload'],
            'patient_consent_given' => ['required', 'boolean'],
        ]);

        $storagePath = $request->file('audio')->store(
            "transcripts/{$visit->id}",
            'local'
        );

        $absolutePath = Storage::disk('local')->path($storagePath);

        $rawTranscript = $stt->transcribe($absolutePath);

        $transcript = Transcript::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'source_type' => $validated['source_type'],
            'stt_provider' => 'whisper',
            'audio_file_path' => $storagePath,
            'raw_transcript' => $rawTranscript,
            'audio_duration_seconds' => 0,
            'processing_status' => 'processing',
            'patient_consent_given' => $validated['patient_consent_given'],
            'consent_timestamp' => $validated['patient_consent_given'] ? now() : null,
        ]);

        ProcessTranscriptJob::dispatch($transcript);

        return response()->json([
            'data' => $transcript,
            'processing_status' => 'processing',
        ], 201);
    }

    public function show(Visit $visit): JsonResponse
    {
        $transcript = $visit->transcript;

        if (! $transcript) {
            return response()->json(['data' => null, 'message' => 'No transcript found for this visit']);
        }

        return response()->json(['data' => $transcript]);
    }

    public function process(Request $request, Visit $visit, ScribeProcessor $scribeProcessor): JsonResponse
    {
        $transcript = $visit->transcript;

        if (! $transcript) {
            return response()->json(['error' => ['message' => 'No transcript found for this visit']], 404);
        }

        if ($transcript->processing_status === 'completed') {
            return response()->json(['data' => $transcript, 'message' => 'Transcript already processed']);
        }

        $transcript->update(['processing_status' => 'processing']);

        $sync = $request->boolean('sync', false);

        if ($sync) {
            // Synchronous processing for demo — calls ScribeProcessor directly
            try {
                $scribeResult = $scribeProcessor->process($transcript);

                $transcript->update([
                    'entities_extracted' => $scribeResult['extracted_entities'] ?? [],
                    'soap_note' => $scribeResult['soap_note'] ?? [],
                    'processing_status' => 'completed',
                ]);

                $soap = $scribeResult['soap_note'] ?? [];

                VisitNote::updateOrCreate(
                    ['visit_id' => $transcript->visit_id],
                    [
                        'patient_id' => $transcript->patient_id,
                        'author_practitioner_id' => $transcript->visit->practitioner_id,
                        'composition_type' => 'progress_note',
                        'status' => 'preliminary',
                        'chief_complaint' => $soap['subjective'] ?? null,
                        'history_of_present_illness' => $soap['subjective'] ?? null,
                        'assessment' => $soap['assessment'] ?? null,
                        'plan' => $soap['plan'] ?? null,
                        'review_of_systems' => $soap['objective'] ?? null,
                        'physical_exam' => $soap['objective'] ?? null,
                    ]
                );

                return response()->json([
                    'data' => $transcript->fresh(),
                    'message' => 'Transcript processed successfully',
                ]);
            } catch (\Throwable $e) {
                $transcript->update(['processing_status' => 'failed']);

                return response()->json([
                    'error' => ['message' => 'Processing failed: ' . $e->getMessage()],
                ], 500);
            }
        }

        ProcessTranscriptJob::dispatch($transcript);

        return response()->json([
            'data' => ['processing_status' => 'processing', 'transcript_id' => $transcript->id],
            'message' => 'Transcript processing started',
        ]);
    }

    public function status(Visit $visit): JsonResponse
    {
        $transcript = $visit->transcript;

        if (! $transcript) {
            return response()->json(['error' => ['message' => 'No transcript found for this visit']], 404);
        }

        return response()->json([
            'data' => [
                'processing_status' => $transcript->processing_status,
                'has_soap_note' => ! empty($transcript->soap_note),
                'has_entities' => ! empty($transcript->entities_extracted),
                'has_summary' => ! empty($transcript->summary),
            ],
        ]);
    }
}
