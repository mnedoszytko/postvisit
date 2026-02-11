<?php

namespace App\Services\Guidelines;

use App\Models\Visit;
use Illuminate\Support\Facades\Log;

/**
 * Loads relevant clinical guidelines for a visit based on conditions and medications.
 *
 * Sources: WikiDoc (CC-BY-SA 3.0), DailyMed (public domain),
 * PMC Open Access (runtime fetch via BioC API, cached 24h).
 *
 * Architecture note: ESC guidelines have an explicit AI/TDM opt-out under
 * EU Directive 2019/790 Article 4(3). AHA/ACC guidelines are fully copyrighted.
 * We use only open-access and public domain sources instead.
 */
class GuidelinesRepository
{
    /** Map ICD-10 condition codes to WikiDoc article filenames */
    private const CONDITION_MAP = [
        'I49.3' => ['premature-ventricular-contraction'],
        'I49.4' => ['premature-ventricular-contraction'],
        'I50' => ['heart-failure'],
        'I50.1' => ['heart-failure'],
        'I50.2' => ['heart-failure'],
        'I50.20' => ['heart-failure'],
        'I50.21' => ['heart-failure'],
        'I50.22' => ['heart-failure'],
        'I50.23' => ['heart-failure'],
        'I50.9' => ['heart-failure'],
        'I10' => ['hypertension'],
        'I11' => ['hypertension'],
        'I12' => ['hypertension'],
        'I13' => ['hypertension'],
    ];

    /** Map WikiDoc condition article names to PMC guideline keys */
    private const CONDITION_PMC_MAP = [
        'premature-ventricular-contraction' => 'pvc_2020',
        'heart-failure' => 'hf_2022',
        'hypertension' => 'htn_2017',
    ];

    /** Map generic drug names (lowercase) to DailyMed label filenames */
    private const DRUG_LABEL_MAP = [
        'propranolol' => 'propranolol',
        'propranolol hydrochloride' => 'propranolol',
        'furosemide' => 'furosemide',
        'lisinopril' => 'lisinopril',
        'carvedilol' => 'carvedilol',
        'dapagliflozin' => 'dapagliflozin',
        'amlodipine' => 'amlodipine',
        'amlodipine besylate' => 'amlodipine',
        'hydrochlorothiazide' => 'hydrochlorothiazide',
    ];

    /** Map generic drug names (lowercase) to WikiDoc drug class articles */
    private const DRUG_CLASS_MAP = [
        'propranolol' => ['beta-blocker', 'propranolol'],
        'propranolol hydrochloride' => ['beta-blocker', 'propranolol'],
        'carvedilol' => ['beta-blocker'],
        'metoprolol' => ['beta-blocker'],
        'furosemide' => ['diuretic'],
        'hydrochlorothiazide' => ['diuretic'],
        'lisinopril' => ['ace-inhibitor'],
        'enalapril' => ['ace-inhibitor'],
        'ramipril' => ['ace-inhibitor'],
        'amlodipine' => ['calcium-channel-blocker'],
        'amlodipine besylate' => ['calcium-channel-blocker'],
    ];

    private string $guidelinesPath;

    public function __construct(private PmcClient $pmcClient)
    {
        $this->guidelinesPath = base_path('demo/guidelines');
    }

    /**
     * Assemble relevant clinical guidelines context for a visit.
     */
    public function getRelevantGuidelines(Visit $visit): string
    {
        $parts = [];
        $loadedFiles = [];

        // Load condition-specific guidelines from WikiDoc
        $conditionArticles = $this->resolveConditionArticles($visit);
        foreach ($conditionArticles as $filename) {
            if (isset($loadedFiles[$filename])) {
                continue;
            }
            $content = $this->loadWikidocArticle($filename);
            if ($content) {
                $parts[] = $content;
                $loadedFiles[$filename] = true;
            }
        }

        // Load drug class guidelines from WikiDoc + DailyMed labels
        [$drugClassArticles, $drugLabels] = $this->resolveMedicationArticles($visit);

        foreach ($drugClassArticles as $filename) {
            if (isset($loadedFiles[$filename])) {
                continue;
            }
            $content = $this->loadWikidocArticle($filename);
            if ($content) {
                $parts[] = $content;
                $loadedFiles[$filename] = true;
            }
        }

        foreach ($drugLabels as $filename) {
            $key = "dailymed_{$filename}";
            if (isset($loadedFiles[$key])) {
                continue;
            }
            $content = $this->loadDailymedLabel($filename);
            if ($content) {
                $parts[] = $content;
                $loadedFiles[$key] = true;
            }
        }

        // Load PMC Open Access articles for matched conditions (runtime fetch, cached 24h)
        $pmcArticles = $this->loadPmcArticles($conditionArticles);
        foreach ($pmcArticles as $key => $content) {
            $parts[] = $content;
            $loadedFiles[$key] = true;
        }

        if (empty($parts)) {
            return '';
        }

        $pmcCount = count($pmcArticles);
        $localCount = count($parts) - $pmcCount;
        $sourceSummary = sprintf(
            'Loaded %d clinical reference documents: %d from WikiDoc (CC-BY-SA 3.0) / DailyMed (public domain), %d from PMC Open Access (runtime fetch).',
            count($parts),
            $localCount,
            $pmcCount,
        );

        // Token estimation: ~4 chars per token (conservative estimate)
        $totalChars = array_sum(array_map('strlen', $parts));
        $estimatedTokens = (int) ceil($totalChars / 4);

        Log::info('Guidelines context assembled', [
            'visit_id' => $visit->id,
            'documents_loaded' => count($parts),
            'pmc_articles_loaded' => $pmcCount,
            'files' => array_keys($loadedFiles),
            'total_chars' => $totalChars,
            'estimated_tokens' => $estimatedTokens,
        ]);

        return implode("\n\n", [
            '--- CLINICAL GUIDELINES CONTEXT ---',
            "Source Attribution: {$sourceSummary}",
            'Note: ESC guidelines are unavailable for AI use due to EU Directive 2019/790 Article 4(3) opt-out. '
                .'AHA/ACC guidelines are copyrighted. All guidelines below are from open-access or public domain sources.',
            ...$parts,
            '--- END GUIDELINES ---',
        ]);
    }

    /**
     * Resolve which WikiDoc condition articles are relevant for a visit.
     *
     * @return list<string>
     */
    private function resolveConditionArticles(Visit $visit): array
    {
        $articles = [];

        $conditions = $visit->conditions;
        if (! $conditions || $conditions->isEmpty()) {
            return $articles;
        }

        foreach ($conditions as $condition) {
            $code = $condition->code ?? '';

            // Direct match
            if (isset(self::CONDITION_MAP[$code])) {
                $articles = array_merge($articles, self::CONDITION_MAP[$code]);

                continue;
            }

            // Try prefix match (e.g. I50.22 → I50.2 → I50)
            $prefix = $code;
            while (strlen($prefix) > 2) {
                $prefix = substr($prefix, 0, -1);
                if (isset(self::CONDITION_MAP[$prefix])) {
                    $articles = array_merge($articles, self::CONDITION_MAP[$prefix]);
                    break;
                }
            }
        }

        return array_values(array_unique($articles));
    }

    /**
     * Resolve which drug class articles and DailyMed labels are relevant.
     *
     * @return array{list<string>, list<string>}
     */
    private function resolveMedicationArticles(Visit $visit): array
    {
        $classArticles = [];
        $labels = [];

        $prescriptions = $visit->prescriptions;
        if (! $prescriptions || $prescriptions->isEmpty()) {
            return [$classArticles, $labels];
        }

        foreach ($prescriptions as $rx) {
            $med = $rx->medication;
            if (! $med || ! $med->generic_name) {
                continue;
            }

            $name = strtolower(trim($med->generic_name));

            // Drug class articles from WikiDoc
            if (isset(self::DRUG_CLASS_MAP[$name])) {
                $classArticles = array_merge($classArticles, self::DRUG_CLASS_MAP[$name]);
            }

            // DailyMed label
            if (isset(self::DRUG_LABEL_MAP[$name])) {
                $labels[] = self::DRUG_LABEL_MAP[$name];
            }
        }

        return [
            array_values(array_unique($classArticles)),
            array_values(array_unique($labels)),
        ];
    }

    /**
     * Load PMC Open Access articles for the matched condition articles.
     *
     * @param  list<string>  $conditionArticles  WikiDoc article filenames matched for this visit
     * @return array<string, string> Map of PMC keys to article content
     */
    private function loadPmcArticles(array $conditionArticles): array
    {
        $articles = [];
        $loadedKeys = [];

        foreach ($conditionArticles as $articleName) {
            $pmcKey = self::CONDITION_PMC_MAP[$articleName] ?? null;
            if ($pmcKey === null || isset($loadedKeys[$pmcKey])) {
                continue;
            }
            $loadedKeys[$pmcKey] = true;

            try {
                $content = $this->pmcClient->getGuideline($pmcKey);
                if ($content) {
                    $pmcId = PmcClient::GUIDELINE_IDS[$pmcKey] ?? $pmcKey;
                    $articles["pmc_{$pmcKey}"] = "--- PMC Open Access Article ({$pmcId}) ---\n{$content}";
                }
            } catch (\Throwable $e) {
                Log::warning('PMC article load failed, continuing with local guidelines', [
                    'pmc_key' => $pmcKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $articles;
    }

    private function loadWikidocArticle(string $filename): ?string
    {
        $path = "{$this->guidelinesPath}/wikidoc/{$filename}.md";

        if (! file_exists($path)) {
            Log::warning('WikiDoc article not found', ['path' => $path]);

            return null;
        }

        return file_get_contents($path);
    }

    private function loadDailymedLabel(string $filename): ?string
    {
        $path = "{$this->guidelinesPath}/dailymed/{$filename}.md";

        if (! file_exists($path)) {
            Log::warning('DailyMed label not found', ['path' => $path]);

            return null;
        }

        return file_get_contents($path);
    }
}
