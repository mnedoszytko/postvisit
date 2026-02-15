<?php

namespace App\Services\Medications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RxNormClient
{
    private const BASE_URL = 'https://rxnav.nlm.nih.gov/REST';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Search for drugs by name via RxNorm API.
     *
     * @return array Array of matching drug results
     */
    public function search(string $query): array
    {
        $cacheKey = 'rxnorm_search_'.md5($query);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL.'/drugs.json', [
                    'name' => $query,
                ]);

            if (! $response->successful()) {
                Log::warning('RxNorm search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseSearchResults($response->json());
        });
    }

    /**
     * Get detailed drug information by RxNorm code (RxCUI).
     *
     * @return array Drug details including name, form, strength
     */
    public function getDrugInfo(string $rxcui): array
    {
        $cacheKey = "rxnorm_drug_{$rxcui}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($rxcui) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL."/rxcui/{$rxcui}/allProperties.json", [
                    'prop' => 'all',
                ]);

            if (! $response->successful()) {
                Log::warning('RxNorm drug info failed', [
                    'rxcui' => $rxcui,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseDrugInfo($response->json());
        });
    }

    /**
     * Get known drug interactions for a given RxNorm code.
     *
     * @return array Array of interaction records
     */
    public function getInteractions(string $rxcui): array
    {
        $cacheKey = "rxnorm_interactions_{$rxcui}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($rxcui) {
            $response = Http::timeout(10)
                ->get(self::BASE_URL.'/interaction/interaction.json', [
                    'rxcui' => $rxcui,
                ]);

            if (! $response->successful()) {
                Log::warning('RxNorm interactions failed', [
                    'rxcui' => $rxcui,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseInteractions($response->json());
        });
    }

    /**
     * Check interactions between multiple drugs.
     *
     * @param  array  $rxcuis  Array of RxCUI codes
     * @return array Interaction pairs
     */
    public function getMultiInteractions(array $rxcuis): array
    {
        if (count($rxcuis) < 2) {
            return [];
        }

        $cacheKey = 'rxnorm_multi_'.md5(implode(',', $rxcuis));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($rxcuis) {
            $response = Http::timeout(15)
                ->get(self::BASE_URL.'/interaction/list.json', [
                    'rxcuis' => implode('+', $rxcuis),
                ]);

            if (! $response->successful()) {
                return [];
            }

            return $this->parseMultiInteractions($response->json());
        });
    }

    private function parseSearchResults(array $data): array
    {
        $results = [];
        $groups = $data['drugGroup']['conceptGroup'] ?? [];

        foreach ($groups as $group) {
            $concepts = $group['conceptProperties'] ?? [];
            foreach ($concepts as $concept) {
                $results[] = [
                    'rxcui' => $concept['rxcui'] ?? null,
                    'name' => $concept['name'] ?? null,
                    'synonym' => $concept['synonym'] ?? null,
                    'tty' => $concept['tty'] ?? null,
                ];
            }
        }

        return $results;
    }

    private function parseDrugInfo(array $data): array
    {
        $properties = [];
        $propConcepts = $data['propConceptGroup']['propConcept'] ?? [];

        foreach ($propConcepts as $prop) {
            $name = $prop['propName'] ?? '';
            $value = $prop['propValue'] ?? '';
            $properties[$name] = $value;
        }

        return $properties;
    }

    private function parseInteractions(array $data): array
    {
        $interactions = [];
        $groups = $data['interactionTypeGroup'] ?? [];

        foreach ($groups as $group) {
            $types = $group['interactionType'] ?? [];
            foreach ($types as $type) {
                $pairs = $type['interactionPair'] ?? [];
                foreach ($pairs as $pair) {
                    $interactions[] = [
                        'severity' => $pair['severity'] ?? 'unknown',
                        'description' => $pair['description'] ?? '',
                        'drugs' => array_map(
                            fn ($c) => [
                                'rxcui' => $c['minConceptItem']['rxcui'] ?? '',
                                'name' => $c['minConceptItem']['name'] ?? '',
                            ],
                            $pair['interactionConcept'] ?? []
                        ),
                    ];
                }
            }
        }

        return $interactions;
    }

    private function parseMultiInteractions(array $data): array
    {
        $interactions = [];
        $groups = $data['fullInteractionTypeGroup'] ?? [];

        foreach ($groups as $group) {
            $types = $group['fullInteractionType'] ?? [];
            foreach ($types as $type) {
                $pairs = $type['interactionPair'] ?? [];
                foreach ($pairs as $pair) {
                    $interactions[] = [
                        'severity' => $pair['severity'] ?? 'unknown',
                        'description' => $pair['description'] ?? '',
                        'drugs' => array_map(
                            fn ($c) => [
                                'rxcui' => $c['minConceptItem']['rxcui'] ?? '',
                                'name' => $c['minConceptItem']['name'] ?? '',
                            ],
                            $pair['interactionConcept'] ?? []
                        ),
                    ];
                }
            }
        }

        return $interactions;
    }
}
