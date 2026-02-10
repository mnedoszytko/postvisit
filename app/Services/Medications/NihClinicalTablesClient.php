<?php

namespace App\Services\Medications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NihClinicalTablesClient
{
    private const BASE_URL = 'https://clinicaltables.nlm.nih.gov/api';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Search for drug names (autocompletion/fuzzy matching).
     *
     * @return array{total: int, matches: array}
     */
    public function searchDrugs(string $query, int $maxResults = 10): array
    {
        $cacheKey = 'nih_drugs_' . md5($query . $maxResults);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $maxResults) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL . '/rxterms/v3/search', [
                    'terms' => $query,
                    'maxList' => $maxResults,
                ]);

            if (! $response->successful()) {
                Log::warning('NIH Clinical Tables drug search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return ['total' => 0, 'matches' => []];
            }

            return $this->parseSearchResults($response->json());
        });
    }

    /**
     * Search for conditions/diagnoses (ICD-10 codes).
     *
     * @return array{total: int, matches: array}
     */
    public function searchConditions(string $query, int $maxResults = 10): array
    {
        $cacheKey = 'nih_conditions_' . md5($query . $maxResults);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $maxResults) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL . '/icd10cm/v3/search', [
                    'sf' => 'code,name',
                    'terms' => $query,
                    'maxList' => $maxResults,
                ]);

            if (! $response->successful()) {
                Log::warning('NIH Clinical Tables condition search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return ['total' => 0, 'matches' => []];
            }

            return $this->parseConditionResults($response->json());
        });
    }

    /**
     * Search for procedures (HCPCS codes).
     *
     * @return array{total: int, matches: array}
     */
    public function searchProcedures(string $query, int $maxResults = 10): array
    {
        $cacheKey = 'nih_procedures_' . md5($query . $maxResults);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $maxResults) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL . '/hcpcs/v3/search', [
                    'terms' => $query,
                    'maxList' => $maxResults,
                ]);

            if (! $response->successful()) {
                return ['total' => 0, 'matches' => []];
            }

            return $this->parseProcedureResults($response->json());
        });
    }

    private function parseSearchResults(array $data): array
    {
        // NIH Clinical Tables returns: [totalCount, codelist, null, displayStrings]
        $total = $data[0] ?? 0;
        $names = $data[1] ?? [];
        $extras = $data[2] ?? [];

        $matches = [];
        foreach ($names as $i => $name) {
            $matches[] = [
                'name' => $name,
                'extra' => $extras[$i] ?? null,
            ];
        }

        return ['total' => $total, 'matches' => $matches];
    }

    private function parseConditionResults(array $data): array
    {
        $total = $data[0] ?? 0;
        $codes = $data[1] ?? [];
        $extras = $data[2] ?? null;
        $display = $data[3] ?? [];

        $matches = [];
        foreach ($codes as $i => $code) {
            $fields = $display[$i] ?? [];
            $matches[] = [
                'code' => $fields[0] ?? $code,
                'name' => $fields[1] ?? $code,
            ];
        }

        return ['total' => $total, 'matches' => $matches];
    }

    private function parseProcedureResults(array $data): array
    {
        $total = $data[0] ?? 0;
        $names = $data[1] ?? [];

        return [
            'total' => $total,
            'matches' => array_map(fn ($n) => ['name' => $n], $names),
        ];
    }
}
