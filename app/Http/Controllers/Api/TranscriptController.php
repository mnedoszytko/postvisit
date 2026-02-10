<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transcript;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        $validated['visit_id'] = $visit->id;
        $validated['patient_id'] = $visit->patient_id;
        $validated['stt_provider'] = $validated['stt_provider'] ?? 'none';
        $validated['audio_duration_seconds'] = $validated['audio_duration_seconds'] ?? 0;
        $validated['processing_status'] = 'pending';
        $validated['consent_timestamp'] = $validated['patient_consent_given'] ? now() : null;

        $transcript = Transcript::create($validated);

        return response()->json(['data' => $transcript], 201);
    }

    public function show(Visit $visit): JsonResponse
    {
        $transcript = $visit->transcript;

        if (! $transcript) {
            return response()->json(['data' => null, 'message' => 'No transcript found for this visit']);
        }

        return response()->json(['data' => $transcript]);
    }

    public function process(Visit $visit): JsonResponse
    {
        $transcript = $visit->transcript;

        if (! $transcript) {
            return response()->json(['error' => ['message' => 'No transcript found for this visit']], 404);
        }

        if ($transcript->processing_status === 'completed') {
            return response()->json(['data' => $transcript, 'message' => 'Transcript already processed']);
        }

        // Mark as processing — AI processing will be handled asynchronously
        $transcript->update(['processing_status' => 'processing']);

        // TODO: Dispatch AI processing job (ScribeProcessor service)

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
