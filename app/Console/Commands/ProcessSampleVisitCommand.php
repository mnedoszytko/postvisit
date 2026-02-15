<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTranscriptJob;
use App\Models\Transcript;
use App\Models\Visit;
use App\Models\VisitNote;
use Illuminate\Console\Command;

class ProcessSampleVisitCommand extends Command
{
    protected $signature = 'app:process-sample-visit
                            {--visit-id= : UUID of the visit to process (defaults to first visit)}';

    protected $description = 'Load sample transcript, run AI ScribeProcessor, and display structured results';

    public function handle(): int
    {
        $transcriptPath = base_path('samples/sample-transcript.txt');

        if (! file_exists($transcriptPath)) {
            $this->error("Sample transcript not found: {$transcriptPath}");

            return self::FAILURE;
        }

        $rawTranscript = file_get_contents($transcriptPath);
        $wordCount = str_word_count($rawTranscript);
        $this->info("Loaded sample transcript ({$wordCount} words)");

        // Find the visit
        $visitId = $this->option('visit-id');
        $visit = $visitId
            ? Visit::find($visitId)
            : Visit::first();

        if (! $visit) {
            $this->error($visitId
                ? "Visit not found with ID: {$visitId}"
                : 'No visits found in the database. Run seeders first.');

            return self::FAILURE;
        }

        $this->info("Using visit: {$visit->id}");
        $this->info("  Patient ID:      {$visit->patient_id}");
        $this->info("  Practitioner ID: {$visit->practitioner_id}");
        $this->info("  Reason:          {$visit->reason_for_visit}");

        // Create or update Transcript record
        $transcript = Transcript::updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'patient_id' => $visit->patient_id,
                'source_type' => 'manual_upload',
                'stt_provider' => 'whisper',
                'raw_transcript' => $rawTranscript,
                'processing_status' => 'pending',
                'patient_consent_given' => true,
                'consent_timestamp' => now(),
            ],
        );

        $this->info("Transcript record: {$transcript->id} (status: {$transcript->processing_status})");
        $this->newLine();
        $this->info('Running AI ScribeProcessor... (this may take 30-60 seconds)');

        $startTime = microtime(true);

        try {
            ProcessTranscriptJob::dispatchSync($transcript);
        } catch (\Throwable $e) {
            $this->error("Processing failed: {$e->getMessage()}");
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        $this->info("Processing completed in {$elapsed}s");
        $this->newLine();

        // Reload from database
        $transcript->refresh();

        // Display results
        $this->components->twoColumnDetail('Processing Status', $transcript->processing_status);
        $this->newLine();

        // SOAP Note
        $soap = $transcript->soap_note;
        if (! empty($soap)) {
            $this->components->info('SOAP Note');
            foreach (['subjective', 'objective', 'assessment', 'plan'] as $section) {
                $value = $soap[$section] ?? '(empty)';
                $this->newLine();
                $this->components->twoColumnDetail(strtoupper($section), '');
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $this->line("  - {$item}");
                    }
                } else {
                    // Wrap long text
                    $lines = explode("\n", wordwrap((string) $value, 100));
                    foreach ($lines as $line) {
                        $this->line("  {$line}");
                    }
                }
            }
        } else {
            $this->warn('No SOAP note generated.');
        }

        $this->newLine();

        // Extracted entities
        $entities = $transcript->entities_extracted;
        if (! empty($entities)) {
            $this->components->info('Extracted Entities');
            foreach ($entities as $category => $items) {
                if (is_array($items)) {
                    $count = count($items);
                    $this->components->twoColumnDetail(ucfirst((string) $category), "{$count} item(s)");
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            $name = $item['name'] ?? $item['description'] ?? json_encode($item);
                            $this->line("    - {$name}");
                        } else {
                            $this->line("    - {$item}");
                        }
                    }
                } else {
                    $this->components->twoColumnDetail(ucfirst((string) $category), (string) $items);
                }
            }
        } else {
            $this->warn('No entities extracted.');
        }

        $this->newLine();

        // VisitNote check
        $visitNote = VisitNote::where('visit_id', $visit->id)->first();
        if ($visitNote) {
            $this->components->info('VisitNote Created');
            $this->components->twoColumnDetail('ID', $visitNote->id);
            $this->components->twoColumnDetail('Status', $visitNote->status);
            $this->components->twoColumnDetail('Chief Complaint', mb_substr((string) $visitNote->chief_complaint, 0, 80).'...');
            $this->components->twoColumnDetail('Assessment', mb_substr((string) $visitNote->assessment, 0, 80).'...');
            $this->components->twoColumnDetail('Plan', mb_substr((string) $visitNote->plan, 0, 80).'...');
        } else {
            $this->warn('No VisitNote was created.');
        }

        $this->newLine();
        $this->components->info('Pipeline test complete.');

        return self::SUCCESS;
    }
}
