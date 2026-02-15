<?php

namespace App\Services\AI;

use App\Services\Medications\OpenFdaClient;
use App\Services\Medications\RxNormClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    /** @var array<string, array>|null Cached lab reference ranges */
    private ?array $labRanges = null;

    public function __construct(
        private OpenFdaClient $openFda,
        private RxNormClient $rxNorm,
    ) {}

    /**
     * Execute a tool call and return the result.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $input): array
    {
        Log::channel('ai')->info('Tool execution', ['tool' => $toolName, 'input' => $input]);

        return match ($toolName) {
            'check_drug_interaction' => $this->checkDrugInteraction($input),
            'get_lab_reference_range' => $this->getLabReferenceRange($input),
            'get_drug_safety_info' => $this->getDrugSafetyInfo($input),
            default => ['error' => "Unknown tool: {$toolName}"],
        };
    }

    /**
     * Check for known interactions between two medications.
     *
     * Uses OpenFDA adverse event co-occurrence data as a proxy for drug interactions.
     *
     * @param  array{drug1: string, drug2: string}  $input
     * @return array<string, mixed>
     */
    private function checkDrugInteraction(array $input): array
    {
        $drug1 = $input['drug1'] ?? '';
        $drug2 = $input['drug2'] ?? '';

        if (! $drug1 || ! $drug2) {
            return ['error' => 'Both drug1 and drug2 are required'];
        }

        try {
            // Get adverse events where both drugs are co-reported
            $adverseEvents = $this->openFda->getCoReportedAdverseEvents($drug1, $drug2, 10);

            // Get label data for drug interaction warnings
            $label1 = $this->openFda->getDrugLabel($drug1);
            $label2 = $this->openFda->getDrugLabel($drug2);

            $interactionWarnings = [];
            if (! empty($label1['drug_interactions'])) {
                $interactionWarnings[] = [
                    'drug' => $drug1,
                    'interaction_info' => mb_substr($label1['drug_interactions'], 0, 800),
                ];
            }
            if (! empty($label2['drug_interactions'])) {
                $interactionWarnings[] = [
                    'drug' => $drug2,
                    'interaction_info' => mb_substr($label2['drug_interactions'], 0, 800),
                ];
            }

            return [
                'drug1' => $drug1,
                'drug2' => $drug2,
                'co_reported_adverse_events' => $adverseEvents['events'] ?? [],
                'co_reported_total' => $adverseEvents['total'] ?? 0,
                'label_interaction_warnings' => $interactionWarnings,
                'source' => 'OpenFDA (fda.gov)',
            ];
        } catch (\Throwable $e) {
            Log::warning('Drug interaction check failed', [
                'drug1' => $drug1,
                'drug2' => $drug2,
                'error' => $e->getMessage(),
            ]);

            return [
                'drug1' => $drug1,
                'drug2' => $drug2,
                'error' => 'Unable to retrieve interaction data. Use clinical knowledge as fallback.',
                'source' => 'OpenFDA (fda.gov)',
            ];
        }
    }

    /**
     * Get reference ranges for a lab test from hardcoded data.
     *
     * @param  array{test_name: string}  $input
     * @return array<string, mixed>
     */
    private function getLabReferenceRange(array $input): array
    {
        $testName = $input['test_name'] ?? '';

        if (! $testName) {
            return ['error' => 'test_name is required'];
        }

        $ranges = $this->loadLabRanges();

        // Try exact key match first
        $normalizedKey = str_replace([' ', '-'], '_', mb_strtolower($testName));
        if (isset($ranges[$normalizedKey])) {
            return [
                'test_name' => $testName,
                'data' => $ranges[$normalizedKey],
                'source' => 'Standard clinical reference ranges',
            ];
        }

        // Try fuzzy match by name field
        foreach ($ranges as $key => $data) {
            $name = mb_strtolower($data['name'] ?? '');
            if (str_contains($name, mb_strtolower($testName)) || str_contains(mb_strtolower($testName), $name)) {
                return [
                    'test_name' => $testName,
                    'matched_key' => $key,
                    'data' => $data,
                    'source' => 'Standard clinical reference ranges',
                ];
            }
        }

        // Try partial key match
        foreach ($ranges as $key => $data) {
            if (str_contains($key, $normalizedKey) || str_contains($normalizedKey, $key)) {
                return [
                    'test_name' => $testName,
                    'matched_key' => $key,
                    'data' => $data,
                    'source' => 'Standard clinical reference ranges',
                ];
            }
        }

        return [
            'test_name' => $testName,
            'error' => 'Lab test not found in reference database. Use clinical knowledge for reference ranges.',
            'available_tests' => array_keys($ranges),
        ];
    }

    /**
     * Get comprehensive safety information for a medication.
     *
     * @param  array{drug_name: string}  $input
     * @return array<string, mixed>
     */
    private function getDrugSafetyInfo(array $input): array
    {
        $drugName = $input['drug_name'] ?? '';

        if (! $drugName) {
            return ['error' => 'drug_name is required'];
        }

        try {
            $label = $this->openFda->getDrugLabel($drugName);
            $adverseEvents = $this->openFda->getAdverseEvents($drugName, 15);

            $safetyInfo = [
                'drug_name' => $drugName,
                'source' => 'OpenFDA (fda.gov)',
            ];

            if (! empty($label)) {
                $safetyInfo['generic_name'] = $label['generic_name'] ?? null;
                $safetyInfo['brand_name'] = $label['brand_name'] ?? null;
                $safetyInfo['manufacturer'] = $label['manufacturer'] ?? null;
                $safetyInfo['indications'] = $label['indications_and_usage']
                    ? mb_substr($label['indications_and_usage'], 0, 600)
                    : null;
                $safetyInfo['warnings'] = $label['warnings']
                    ? mb_substr($label['warnings'], 0, 800)
                    : null;
                $safetyInfo['boxed_warning'] = $label['boxed_warning']
                    ? mb_substr($label['boxed_warning'], 0, 600)
                    : null;
                $safetyInfo['adverse_reactions'] = $label['adverse_reactions']
                    ? mb_substr($label['adverse_reactions'], 0, 800)
                    : null;
                $safetyInfo['drug_interactions'] = $label['drug_interactions']
                    ? mb_substr($label['drug_interactions'], 0, 800)
                    : null;
                $safetyInfo['dosage_info'] = $label['dosage_and_administration']
                    ? mb_substr($label['dosage_and_administration'], 0, 600)
                    : null;
                $safetyInfo['patient_info'] = $label['information_for_patients']
                    ? mb_substr($label['information_for_patients'], 0, 600)
                    : null;
                $safetyInfo['pregnancy'] = $label['pregnancy']
                    ? mb_substr($label['pregnancy'], 0, 400)
                    : null;
            } else {
                $safetyInfo['label_warning'] = 'No FDA label data found for this drug name.';
            }

            if (! empty($adverseEvents['events'])) {
                $safetyInfo['top_adverse_events'] = $adverseEvents['events'];
                $safetyInfo['total_adverse_reports'] = $adverseEvents['total'];
            }

            return $safetyInfo;
        } catch (\Throwable $e) {
            Log::warning('Drug safety info lookup failed', [
                'drug' => $drugName,
                'error' => $e->getMessage(),
            ]);

            return [
                'drug_name' => $drugName,
                'error' => 'Unable to retrieve safety data. Use clinical knowledge as fallback.',
                'source' => 'OpenFDA (fda.gov)',
            ];
        }
    }

    /**
     * Load lab reference ranges from the JSON file.
     *
     * @return array<string, array>
     */
    private function loadLabRanges(): array
    {
        if ($this->labRanges !== null) {
            return $this->labRanges;
        }

        $path = base_path('demo/lab-reference-ranges.json');

        if (! File::exists($path)) {
            Log::warning('Lab reference ranges file not found', ['path' => $path]);

            return $this->labRanges = [];
        }

        try {
            $this->labRanges = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::warning('Failed to parse lab reference ranges', ['error' => $e->getMessage()]);
            $this->labRanges = [];
        }

        return $this->labRanges;
    }
}
