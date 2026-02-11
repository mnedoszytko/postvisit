<?php

namespace App\Jobs;

use App\Models\Transcript;
use App\Models\VisitNote;
use App\Services\AI\ScribeProcessor;
use App\Services\AI\TermExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTranscriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public Transcript $transcript,
    ) {}

    public function handle(ScribeProcessor $processor): void
    {
        try {
            $scribeResult = $processor->process($this->transcript);

            $this->transcript->update([
                'entities_extracted' => $scribeResult['extracted_entities'] ?? [],
                'soap_note' => $scribeResult['soap_note'] ?? [],
                'diarized_transcript' => [
                    'clean_text' => $scribeResult['clean_transcript'] ?? null,
                    'speakers' => $scribeResult['speakers'] ?? [],
                ],
                'processing_status' => 'completed',
            ]);

            // Update visit reason from chief complaint if still default
            $visit = $this->transcript->visit;
            $soap = $scribeResult['soap_note'] ?? [];
            $chiefComplaint = $soap['subjective'] ?? null;
            if ($visit && $chiefComplaint && str_contains($visit->reason_for_visit ?? '', 'Companion Scribe')) {
                // Extract first sentence as a concise reason
                $reason = strtok(trim($chiefComplaint), "\n");
                if (strlen($reason) > 120) {
                    $reason = substr($reason, 0, 117).'...';
                }
                $visit->update(['reason_for_visit' => $reason]);
            }

            VisitNote::updateOrCreate(
                ['visit_id' => $this->transcript->visit_id],
                [
                    'patient_id' => $this->transcript->patient_id,
                    'author_practitioner_id' => $this->transcript->visit->practitioner_id,
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

            // Extract medical terms for highlighting in patient view
            $visitNote = VisitNote::where('visit_id', $this->transcript->visit_id)->first();
            if ($visitNote) {
                try {
                    app(TermExtractor::class)->extract($visitNote);
                    Log::info('Medical terms extracted', ['visit_id' => $this->transcript->visit_id]);
                } catch (\Throwable $e) {
                    Log::warning('Term extraction failed (non-fatal)', [
                        'visit_id' => $this->transcript->visit_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Transcript processed successfully', [
                'transcript_id' => $this->transcript->id,
                'visit_id' => $this->transcript->visit_id,
            ]);
        } catch (\Throwable $e) {
            $this->transcript->update([
                'processing_status' => 'failed',
            ]);

            Log::error('Transcript processing failed', [
                'transcript_id' => $this->transcript->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
