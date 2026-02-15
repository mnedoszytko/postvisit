<?php

namespace App\Services\Medications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenFdaClient
{
    private const BASE_URL = 'https://api.fda.gov/drug';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get adverse event reports for a drug by generic name.
     *
     * @return array{events: array, total: int}
     */
    public function getAdverseEvents(string $drugName, int $limit = 10): array
    {
        $cacheKey = 'openfda_adverse_'.md5($drugName.$limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($drugName, $limit) {
            $response = Http::timeout((int) config('services.openfda.timeout', 5))
                ->get(self::BASE_URL.'/event.json', [
                    'search' => 'patient.drug.openfda.generic_name:"'.$drugName.'"',
                    'count' => 'patient.reaction.reactionmeddrapt.exact',
                    'limit' => $limit,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenFDA adverse events failed', [
                    'drug' => $drugName,
                    'status' => $response->status(),
                ]);

                return ['events' => [], 'total' => 0];
            }

            return $this->parseAdverseEvents($response->json());
        });
    }

    /**
     * Get drug label information by generic name or brand name.
     *
     * @return array Drug label data (warnings, adverse reactions, etc.)
     */
    public function getDrugLabel(string $drugName): array
    {
        $cacheKey = 'openfda_label_'.md5($drugName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($drugName) {
            $response = Http::timeout((int) config('services.openfda.timeout', 5))
                ->get(self::BASE_URL.'/label.json', [
                    'search' => 'openfda.generic_name:"'.$drugName.'"',
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenFDA drug label failed', [
                    'drug' => $drugName,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseDrugLabel($response->json());
        });
    }

    /**
     * Get adverse events co-reported for two drugs together.
     *
     * @return array{events: array, total: int}
     */
    public function getCoReportedAdverseEvents(string $drug1, string $drug2, int $limit = 10): array
    {
        $cacheKey = 'openfda_coreported_'.md5($drug1.$drug2.$limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($drug1, $drug2, $limit) {
            $search = 'patient.drug.openfda.generic_name:"'.$drug1.'"'
                .'+AND+patient.drug.openfda.generic_name:"'.$drug2.'"';

            $response = Http::timeout((int) config('services.openfda.timeout', 5))
                ->get(self::BASE_URL.'/event.json', [
                    'search' => $search,
                    'count' => 'patient.reaction.reactionmeddrapt.exact',
                    'limit' => $limit,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenFDA co-reported adverse events failed', [
                    'drug1' => $drug1,
                    'drug2' => $drug2,
                    'status' => $response->status(),
                ]);

                return ['events' => [], 'total' => 0];
            }

            return $this->parseAdverseEvents($response->json());
        });
    }

    private function parseAdverseEvents(array $data): array
    {
        $results = $data['results'] ?? [];

        return [
            'events' => array_map(fn ($r) => [
                'reaction' => $r['term'] ?? '',
                'count' => $r['count'] ?? 0,
            ], $results),
            'total' => array_sum(array_column($results, 'count')),
        ];
    }

    private function parseDrugLabel(array $data): array
    {
        $label = $data['results'][0] ?? null;

        if (! $label) {
            return [];
        }

        return [
            'generic_name' => $label['openfda']['generic_name'][0] ?? null,
            'brand_name' => $label['openfda']['brand_name'][0] ?? null,
            'manufacturer' => $label['openfda']['manufacturer_name'][0] ?? null,
            'warnings' => $this->firstOrNull($label, 'warnings'),
            'boxed_warning' => $this->firstOrNull($label, 'boxed_warning'),
            'adverse_reactions' => $this->firstOrNull($label, 'adverse_reactions'),
            'drug_interactions' => $this->firstOrNull($label, 'drug_interactions'),
            'indications_and_usage' => $this->firstOrNull($label, 'indications_and_usage'),
            'dosage_and_administration' => $this->firstOrNull($label, 'dosage_and_administration'),
            'information_for_patients' => $this->firstOrNull($label, 'information_for_patients'),
            'pregnancy' => $this->firstOrNull($label, 'pregnancy'),
            'nursing_mothers' => $this->firstOrNull($label, 'nursing_mothers'),
        ];
    }

    private function firstOrNull(array $data, string $key): ?string
    {
        return $data[$key][0] ?? null;
    }
}
