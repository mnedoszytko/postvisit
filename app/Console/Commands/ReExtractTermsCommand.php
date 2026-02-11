<?php

namespace App\Console\Commands;

use App\Models\VisitNote;
use App\Services\AI\TermExtractor;
use Illuminate\Console\Command;

class ReExtractTermsCommand extends Command
{
    protected $signature = 'visits:reextract-terms
        {--visit= : Re-extract for a specific visit ID}
        {--all : Re-extract for all visit notes}
        {--missing : Only re-extract where medical_terms is null or empty}';

    protected $description = 'Re-extract medical terms with definitions for existing visit notes';

    public function handle(TermExtractor $extractor): int
    {
        $query = VisitNote::query()->whereNotNull('chief_complaint');

        if ($visitId = $this->option('visit')) {
            $query->where('visit_id', $visitId);
        } elseif ($this->option('missing')) {
            $query->where(function ($q) {
                $q->whereNull('medical_terms')
                    ->orWhere('medical_terms', '{}')
                    ->orWhere('medical_terms', '[]');
            });
        } elseif (! $this->option('all')) {
            $this->error('Please specify --visit=<id>, --missing, or --all');

            return self::FAILURE;
        }

        $notes = $query->get();

        if ($notes->isEmpty()) {
            $this->info('No visit notes found matching criteria.');

            return self::SUCCESS;
        }

        $this->info("Re-extracting terms for {$notes->count()} visit note(s)...");
        $bar = $this->output->createProgressBar($notes->count());

        $succeeded = 0;
        $failed = 0;

        foreach ($notes as $note) {
            try {
                $terms = $extractor->extract($note);
                $totalTerms = collect($terms)->flatten(1)->count();
                $this->line(" Visit {$note->visit_id}: {$totalTerms} terms extracted");
                $succeeded++;
            } catch (\Throwable $e) {
                $this->warn(" Visit {$note->visit_id}: FAILED — {$e->getMessage()}");
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$succeeded} succeeded, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
