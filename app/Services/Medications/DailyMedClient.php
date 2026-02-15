<?php

namespace App\Services\Medications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DailyMedClient
{
    private const BASE_URL = 'https://dailymed.nlm.nih.gov/dailymed/services/v2';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Search for drug names in DailyMed.
     *
     * @return array Array of matching drug names with SPL set IDs
     */
    public function searchDrugNames(string $query): array
    {
        $cacheKey = 'dailymed_search_'.md5($query);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $response = Http::timeout(15)
                ->get(self::BASE_URL.'/drugnames.json', [
                    'drug_name' => $query,
                ]);

            if (! $response->successful()) {
                Log::warning('DailyMed search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseSearchResults($response->json());
        });
    }

    /**
     * Get structured product label (SPL) by set ID.
     *
     * @return array Label data with sections
     */
    public function getSpl(string $setId): array
    {
        $cacheKey = "dailymed_spl_{$setId}";

        return Cache::remember($cacheKey, self::CACHE_TTL * 7, function () use ($setId) {
            $response = Http::timeout(15)
                ->get(self::BASE_URL."/spls/{$setId}.json");

            if (! $response->successful()) {
                Log::warning('DailyMed SPL fetch failed', [
                    'setId' => $setId,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseSpl($response->json());
        });
    }

    /**
     * Search SPLs by drug name and return matching label info.
     *
     * @return array First matching SPL data
     */
    public function searchSpls(string $drugName): array
    {
        $cacheKey = 'dailymed_spls_'.md5($drugName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($drugName) {
            $response = Http::timeout(15)
                ->get(self::BASE_URL.'/spls.json', [
                    'drug_name' => $drugName,
                    'pagesize' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('DailyMed SPL search failed', [
                    'drug' => $drugName,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();
            $results = $data['data'] ?? [];

            if (empty($results)) {
                return [];
            }

            $setId = $results[0]['setid'] ?? null;

            return $setId ? $this->getSpl($setId) : [];
        });
    }

    /**
     * Look up RxCUI mapping for a drug name.
     *
     * @return array RxCUI references
     */
    public function getRxcuiMapping(string $drugName): array
    {
        $cacheKey = 'dailymed_rxcui_'.md5($drugName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($drugName) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL.'/rxcuis.json', [
                    'drug_name' => $drugName,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            return $data['data'] ?? [];
        });
    }

    private function parseSearchResults(array $data): array
    {
        $results = [];

        foreach ($data['data'] ?? [] as $item) {
            $results[] = [
                'drug_name' => $item['drug_name'] ?? '',
            ];
        }

        return $results;
    }

    private function parseSpl(array $data): array
    {
        return [
            'setid' => $data['setid'] ?? null,
            'title' => $data['title'] ?? null,
            'effective_time' => $data['effective_time'] ?? null,
            'version_number' => $data['version_number'] ?? null,
            'author' => $data['author'] ?? null,
        ];
    }
}
