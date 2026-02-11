<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTranscriptJob;
use App\Models\Transcript;
use App\Models\Visit;
use App\Models\VisitNote;
use App\Services\AI\ScribeProcessor;
use App\Services\AI\TermExtractor;
use App\Services\Stt\SpeechToTextProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscriptController extends Controller
{
    /**
     * Backup audio file outside the project directory for safekeeping.
     */
    private function backupAudio(string $storagePath, Visit $visit, string $label = ''): void
    {
        try {
            $contents = Storage::disk('local')->get($storagePath);
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
            $timestamp = now()->format('Ymd_His');
            $backupName = "{$visit->id}/{$timestamp}".($label ? "_{$label}" : '').".{$ext}";
            Storage::disk('audio_backup')->put($backupName, $contents);
        } catch (\Throwable $e) {
            Log::warning('Audio backup failed', ['path' => $storagePath, 'error' => $e->getMessage()]);
        }
    }

    public function store(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'raw_transcript' => ['required_without:use_demo_transcript', 'string'],
            'use_demo_transcript' => ['nullable', 'boolean'],
            'source_type' => ['required', 'in:ambient_phone,ambient_device,manual_upload'],
            'stt_provider' => ['nullable', 'string'],
            'audio_duration_seconds' => ['nullable', 'integer'],
            'patient_consent_given' => ['required', 'boolean'],
            'process' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('use_demo_transcript')) {
            $demoPath = database_path('../demo/transcript.txt');
            $validated['raw_transcript'] = file_exists($demoPath)
                ? file_get_contents($demoPath)
                : $validated['raw_transcript'] ?? 'Demo transcript not found';
            $validated['audio_duration_seconds'] = 1590;
            $validated['stt_provider'] = 'whisper';
        }

        $shouldProcess = $validated['process'] ?? false;
        unset($validated['process'], $validated['use_demo_transcript']);

        // Quality gate: evaluate transcript before storing
        $quality = ScribeProcessor::evaluateQuality($validated['raw_transcript'] ?? '');

        $validated['visit_id'] = $visit->id;
        $validated['patient_id'] = $visit->patient_id;
        $validated['stt_provider'] = $validated['stt_provider'] ?? 'none';
        $validated['audio_duration_seconds'] = $validated['audio_duration_seconds'] ?? 0;
        $validated['consent_timestamp'] = $validated['patient_consent_given'] ? now() : null;

        if (! $quality['sufficient']) {
            // Still save the transcript but mark as insufficient
            $validated['processing_status'] = 'insufficient_content';
            $transcript = Transcript::create($validated);

            return response()->json([
                'data' => $transcript,
                'quality' => $quality,
            ], 201);
        }

        $validated['processing_status'] = $shouldProcess ? 'processing' : 'pending';
        $transcript = Transcript::create($validated);

        if ($shouldProcess) {
            ProcessTranscriptJob::dispatch($transcript);
        }

        return response()->json([
            'data' => $transcript,
            'quality' => $quality,
        ], 201);
    }

    public function uploadAudio(Request $request, Visit $visit, SpeechToTextProvider $stt): JsonResponse
    {
        $validated = $request->validate([
            'audio' => ['required', 'file', 'max:102400'],
            'source_type' => ['required', 'in:ambient_phone,ambient_device,manual_upload'],
            'patient_consent_given' => ['required', 'boolean'],
        ]);

        $storagePath = $request->file('audio')->store(
            "transcripts/{$visit->id}",
            'local'
        );

        $this->backupAudio($storagePath, $visit, 'full');

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

    public function process(Request $request, Visit $visit): JsonResponse
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
            // Synchronous processing for demo — resolve ScribeProcessor only when needed
            try {
                $scribeProcessor = app(ScribeProcessor::class);
                $scribeResult = $scribeProcessor->process($transcript);

                $transcript->update([
                    'entities_extracted' => $scribeResult['extracted_entities'] ?? [],
                    'soap_note' => $scribeResult['soap_note'] ?? [],
                    'diarized_transcript' => [
                        'clean_text' => $scribeResult['clean_transcript'] ?? null,
                        'speakers' => $scribeResult['speakers'] ?? [],
                    ],
                    'processing_status' => 'completed',
                ]);

                // Update visit reason from chief complaint if still default
                $soap = $scribeResult['soap_note'] ?? [];
                $chiefComplaint = $soap['chief_complaint'] ?? null;
                if ($chiefComplaint && str_contains($visit->reason_for_visit ?? '', 'Companion Scribe')) {
                    $reason = strtok(trim($chiefComplaint), "\n");
                    if (strlen($reason) > 120) {
                        $reason = substr($reason, 0, 117).'...';
                    }
                    $visit->update(['reason_for_visit' => $reason]);
                }

                // Build plan text, appending current_medications if present
                $planText = $soap['plan'] ?? null;
                $medsText = $soap['current_medications'] ?? null;
                if ($medsText && $planText) {
                    $planText .= "\n\nCurrent Medications:\n".$medsText;
                } elseif ($medsText && ! $planText) {
                    $planText = "Current Medications:\n".$medsText;
                }

                VisitNote::updateOrCreate(
                    ['visit_id' => $transcript->visit_id],
                    [
                        'patient_id' => $transcript->patient_id,
                        'author_practitioner_id' => $transcript->visit->practitioner_id,
                        'composition_type' => 'progress_note',
                        'status' => 'preliminary',
                        'chief_complaint' => $chiefComplaint,
                        'history_of_present_illness' => $soap['history_of_present_illness'] ?? null,
                        'assessment' => $soap['assessment'] ?? null,
                        'plan' => $planText,
                        'review_of_systems' => $soap['review_of_systems'] ?? null,
                        'physical_exam' => $soap['physical_exam'] ?? null,
                    ]
                );

                // Extract medical terms for highlighting
                $visitNote = VisitNote::where('visit_id', $transcript->visit_id)->first();
                if ($visitNote) {
                    try {
                        app(TermExtractor::class)->extract($visitNote);
                    } catch (\Throwable $e) {
                        Log::warning('Term extraction failed (non-fatal)', [
                            'visit_id' => $transcript->visit_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return response()->json([
                    'data' => $transcript->fresh(),
                    'message' => 'Transcript processed successfully',
                ]);
            } catch (\Throwable $e) {
                $transcript->update(['processing_status' => 'failed']);

                return response()->json([
                    'error' => ['message' => 'Processing failed: '.$e->getMessage()],
                ], 500);
            }
        }

        ProcessTranscriptJob::dispatch($transcript);

        return response()->json([
            'data' => ['processing_status' => 'processing', 'transcript_id' => $transcript->id],
            'message' => 'Transcript processing started',
        ]);
    }

    /**
     * Transcribe a single audio chunk and return the text without creating a Transcript record.
     * Used by the frontend for chunked uploads of long recordings (>10 min).
     */
    public function transcribeChunk(Request $request, Visit $visit, SpeechToTextProvider $stt): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:102400'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
        ]);

        $storagePath = $request->file('audio')->store(
            "transcripts/{$visit->id}/chunks",
            'local'
        );

        $absolutePath = Storage::disk('local')->path($storagePath);

        // Keep chunk file on disk — audio is preserved even if transcription fails
        $text = $stt->transcribe($absolutePath);

        return response()->json([
            'data' => [
                'text' => $text,
                'chunk_index' => (int) $request->input('chunk_index'),
                'total_chunks' => (int) $request->input('total_chunks'),
                'stored_path' => $storagePath,
            ],
        ]);
    }

    /**
     * Save an audio chunk to disk without transcribing. Returns the stored path.
     * Used as a safety net — audio is preserved even if later transcription fails.
     */
    public function saveChunk(Request $request, Visit $visit): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:102400'],
            'chunk_index' => ['required', 'integer', 'min:0'],
        ]);

        $storagePath = $request->file('audio')->store(
            "transcripts/{$visit->id}/chunks",
            'local'
        );

        $this->backupAudio($storagePath, $visit, 'chunk'.$request->input('chunk_index'));

        return response()->json([
            'data' => [
                'stored_path' => $storagePath,
                'chunk_index' => (int) $request->input('chunk_index'),
            ],
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
