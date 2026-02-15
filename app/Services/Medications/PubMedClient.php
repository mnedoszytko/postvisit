<?php

namespace App\Services\Medications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PubMedClient
{
    private const BASE_URL = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils';

    public function verifyPmid(string $pmid): ?array
    {
        return Cache::remember("pubmed:pmid:{$pmid}", 86400, function () use ($pmid) {
            try {
                $response = Http::timeout(10)->get(self::BASE_URL.'/esummary.fcgi', [
                    'db' => 'pubmed',
                    'id' => $pmid,
                    'retmode' => 'json',
                ]);

                if (! $response->ok()) {
                    return null;
                }

                $data = $response->json();
                $result = $data['result'][$pmid] ?? null;

                if (! $result || isset($result['error'])) {
                    return null;
                }

                return [
                    'pmid' => $pmid,
                    'title' => $result['title'] ?? null,
                    'authors' => collect($result['authors'] ?? [])->pluck('name')->implode(', '),
                    'journal' => $result['fulljournalname'] ?? $result['source'] ?? null,
                    'year' => substr($result['pubdate'] ?? '', 0, 4),
                    'doi' => collect($result['articleids'] ?? [])->firstWhere('idtype', 'doi')['value'] ?? null,
                    'url' => "https://pubmed.ncbi.nlm.nih.gov/{$pmid}/",
                    'exists' => true,
                ];
            } catch (\Exception $e) {
                Log::warning('PubMed verification failed', ['pmid' => $pmid, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }

    public function searchByDoi(string $doi): ?array
    {
        return Cache::remember("pubmed:doi:{$doi}", 86400, function () use ($doi) {
            try {
                $response = Http::timeout(10)->get(self::BASE_URL.'/esearch.fcgi', [
                    'db' => 'pubmed',
                    'term' => "{$doi}[doi]",
                    'retmode' => 'json',
                ]);

                if (! $response->ok()) {
                    return null;
                }

                $data = $response->json();
                $ids = $data['esearchresult']['idlist'] ?? [];

                if (empty($ids)) {
                    return null;
                }

                return $this->verifyPmid($ids[0]);
            } catch (\Exception $e) {
                Log::warning('PubMed DOI search failed', ['doi' => $doi, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }
}
