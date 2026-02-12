<?php

namespace App\Console\Commands;

use App\Models\Medication;
use App\Services\Guidelines\PmcClient;
use App\Services\Medications\OpenFdaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Pre-warm external API caches (FDA, PMC) so chat responses don't block on HTTP calls.
 *
 * Should be run after seeding, after cache clear, or via scheduler daily.
 */
class WarmClinicalCache extends Command
{
    protected $signature = 'app:warm-clinical-cache {--force : Re-warm even if cache exists}';

    protected $description = 'Pre-warm FDA safety data and PMC guidelines caches for all medications and visits';

    public function handle(OpenFdaClient $openFda, PmcClient $pmcClient): int
    {
        $this->info('Warming clinical caches...');

        $this->warmFdaCache($openFda);
        $this->warmPmcCache($pmcClient);

        $this->newLine();
        $this->info('Clinical cache warming complete.');

        return self::SUCCESS;
    }

    private function warmFdaCache(OpenFdaClient $openFda): void
    {
        $medications = Medication::whereNotNull('generic_name')->get();

        if ($medications->isEmpty()) {
            $this->warn('No medications found — skipping FDA cache.');

            return;
        }

        $this->info("Warming FDA cache for {$medications->count()} medications...");
        $bar = $this->output->createProgressBar($medications->count());

        foreach ($medications as $med) {
            $cacheKey = 'fda_safety:'.mb_strtolower($med->generic_name);

            if (! $this->option('force') && Cache::has($cacheKey)) {
                $bar->advance();

                continue;
            }

            try {
                $parts = [];

                $adverse = $openFda->getAdverseEvents($med->generic_name, 5);
                if (! empty($adverse['events'])) {
                    $parts[] = "\nFDA Adverse Event Reports for {$med->generic_name}:";
                    foreach ($adverse['events'] as $event) {
                        $parts[] = "- {$event['reaction']}: {$event['count']} reports";
                    }
                }

                $label = $openFda->getDrugLabel($med->generic_name);
                if (! empty($label)) {
                    if (! empty($label['boxed_warning'])) {
                        $parts[] = "\nBOXED WARNING: ".mb_substr($label['boxed_warning'], 0, 500);
                    }
                    if (! empty($label['information_for_patients'])) {
                        $parts[] = "\nPatient Info: ".mb_substr($label['information_for_patients'], 0, 500);
                    }
                }

                Cache::put($cacheKey, $parts !== [] ? implode("\n", $parts) : null, 86400);
            } catch (\Throwable $e) {
                $this->warn(" FDA failed for {$med->generic_name}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function warmPmcCache(PmcClient $pmcClient): void
    {
        $guidelineKeys = array_keys(PmcClient::GUIDELINE_IDS);

        $this->info('Warming PMC guidelines cache ('.count($guidelineKeys).' articles)...');
        $bar = $this->output->createProgressBar(count($guidelineKeys));

        foreach ($guidelineKeys as $key) {
            $cacheKey = "pmc_guideline_{$key}";

            if (! $this->option('force') && Cache::has($cacheKey)) {
                $bar->advance();

                continue;
            }

            try {
                // This call populates the cache internally
                $pmcClient->getGuideline($key);
            } catch (\Throwable $e) {
                $this->warn(" PMC failed for {$key}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
