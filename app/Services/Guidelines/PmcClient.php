<?php

namespace App\Services\Guidelines;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PmcClient
{
    /** Known PMC IDs for key cardiology guidelines */
    public const GUIDELINE_IDS = [
        'hf_2022' => 'PMC9386162',   // 2022 AHA/ACC/HFSA Heart Failure Guidelines
        'htn_2017' => 'PMC7384247',  // 2017 ACC/AHA Hypertension Guidelines
        'pvc_2020' => 'PMC7880852',  // PVC management consensus
    ];

    private const MAX_WORDS = 50000;

    private string $baseUrl;

    private string $eutilsUrl;

    private int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.pmc.base_url', 'https://www.ncbi.nlm.nih.gov/research/bionlp/RESTful/pmcoa.cgi');
        $this->eutilsUrl = config('services.pmc.eutils_url', 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils');
        $this->cacheTtl = (int) config('services.pmc.cache_ttl', 86400);
    }

    /**
     * Fetch full text of a PMC article via BioC API.
     */
    public function fetchArticle(string $pmcId): ?string
    {
        $response = Http::timeout(30)
            ->get("{$this->baseUrl}/BioC_json/{$pmcId}/unicode");

        if (! $response->successful()) {
            Log::warning('PMC article fetch failed', [
                'pmc_id' => $pmcId,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $this->extractText($response->json());
    }

    /**
     * Search PubMed for guideline articles matching a query.
     *
     * @return array<int, array{pmcid: string, title: string}> Search results
     */
    public function searchGuidelines(string $query, int $limit = 5): array
    {
        $cacheKey = 'pmc_search_'.md5($query.$limit);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query, $limit) {
            $searchResponse = Http::timeout(15)
                ->get("{$this->eutilsUrl}/esearch.fcgi", [
                    'db' => 'pmc',
                    'retmode' => 'json',
                    'retmax' => $limit,
                    'term' => $query,
                ]);

            if (! $searchResponse->successful()) {
                Log::warning('PMC search failed', [
                    'query' => $query,
                    'status' => $searchResponse->status(),
                ]);

                return [];
            }

            return $this->parseSearchResults($searchResponse->json());
        });
    }

    /**
     * Get cached or fresh guideline text by key (e.g. 'hf_2022').
     */
    public function getGuideline(string $key): ?string
    {
        $pmcId = self::GUIDELINE_IDS[$key] ?? null;

        if ($pmcId === null) {
            Log::warning('Unknown guideline key', ['key' => $key]);

            return null;
        }

        $cacheKey = "pmc_guideline_{$key}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($pmcId) {
            return $this->fetchArticle($pmcId);
        });
    }

    /**
     * Extract and clean text passages from BioC JSON response.
     */
    private function extractText(array $data): ?string
    {
        $passages = [];

        $documents = $data['documents'] ?? [];
        foreach ($documents as $document) {
            foreach ($document['passages'] ?? [] as $passage) {
                $text = $passage['text'] ?? '';
                if ($text !== '') {
                    $passages[] = $text;
                }
            }
        }

        if (empty($passages)) {
            return null;
        }

        $fullText = implode("\n\n", $passages);

        return $this->cleanText($fullText);
    }

    /**
     * Clean extracted text: remove artifacts, excessive whitespace, truncate.
     */
    private function cleanText(string $text): string
    {
        // Remove figure/table references like (Fig. 1), [Table 2], (Figure S1)
        $text = preg_replace('/\((?:Fig(?:ure)?|Table|Supplementa(?:l|ry)\s+(?:Fig|Table))\.?\s*\w*\d*\)/i', '', $text);
        $text = preg_replace('/\[(?:Fig(?:ure)?|Table)\.?\s*\w*\d*\]/i', '', $text);

        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        // Truncate to max words
        $words = preg_split('/\s+/', $text);
        if (count($words) > self::MAX_WORDS) {
            $words = array_slice($words, 0, self::MAX_WORDS);
            $text = implode(' ', $words)."\n\n[Truncated — full text available via PMC]";
        }

        return $text;
    }

    /**
     * Parse E-Utilities search response into PMC ID list.
     *
     * @return array<int, array{pmcid: string}>
     */
    private function parseSearchResults(array $data): array
    {
        $ids = $data['esearchresult']['idlist'] ?? [];

        return array_map(fn (string $id) => [
            'pmcid' => 'PMC'.$id,
        ], $ids);
    }
}
