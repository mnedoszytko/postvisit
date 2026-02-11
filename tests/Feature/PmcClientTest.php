<?php

namespace Tests\Feature;

use App\Services\Guidelines\PmcClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PmcClientTest extends TestCase
{
    private PmcClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new PmcClient;
    }

    public function test_fetch_article_returns_cleaned_text(): void
    {
        Http::fake([
            '*/BioC_json/PMC9386162/unicode' => Http::response([
                'documents' => [
                    [
                        'passages' => [
                            ['text' => 'Heart failure (HF) is a complex clinical syndrome (Fig. 1).'],
                            ['text' => 'Treatment involves ACE inhibitors and beta-blockers.'],
                            ['text' => ''],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->client->fetchArticle('PMC9386162');

        $this->assertNotNull($result);
        $this->assertStringContainsString('Heart failure', $result);
        $this->assertStringContainsString('ACE inhibitors', $result);
        // Figure references should be stripped
        $this->assertStringNotContainsString('(Fig. 1)', $result);
        // Empty passages should not produce extra whitespace issues
        $this->assertStringNotContainsString("\n\n\n", $result);
    }

    public function test_fetch_article_returns_null_on_failure(): void
    {
        Http::fake([
            '*/BioC_json/PMC0000000/unicode' => Http::response([], 404),
        ]);

        $result = $this->client->fetchArticle('PMC0000000');

        $this->assertNull($result);
    }

    public function test_fetch_article_returns_null_for_empty_passages(): void
    {
        Http::fake([
            '*/BioC_json/PMC1111111/unicode' => Http::response([
                'documents' => [
                    ['passages' => []],
                ],
            ]),
        ]);

        $result = $this->client->fetchArticle('PMC1111111');

        $this->assertNull($result);
    }

    public function test_fetch_article_truncates_long_text(): void
    {
        // Generate text with more than 4000 words
        $longText = implode(' ', array_fill(0, 5000, 'word'));

        Http::fake([
            '*/BioC_json/PMC9999999/unicode' => Http::response([
                'documents' => [
                    [
                        'passages' => [
                            ['text' => $longText],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->client->fetchArticle('PMC9999999');

        $this->assertNotNull($result);
        $this->assertStringContainsString('[Truncated', $result);
        // Should be approximately 4000 words + truncation notice
        $wordCount = str_word_count(preg_replace('/\[Truncated.*\]/', '', $result));
        $this->assertLessThanOrEqual(4001, $wordCount);
    }

    public function test_search_guidelines_parses_eutils_response(): void
    {
        Http::fake([
            '*/esearch.fcgi*' => Http::response([
                'esearchresult' => [
                    'idlist' => ['9386162', '7384247', '7880852'],
                ],
            ]),
        ]);

        $results = $this->client->searchGuidelines('heart failure guidelines');

        $this->assertCount(3, $results);
        $this->assertEquals('PMC9386162', $results[0]['pmcid']);
        $this->assertEquals('PMC7384247', $results[1]['pmcid']);
        $this->assertEquals('PMC7880852', $results[2]['pmcid']);
    }

    public function test_search_guidelines_returns_empty_on_failure(): void
    {
        Http::fake([
            '*/esearch.fcgi*' => Http::response([], 500),
        ]);

        $results = $this->client->searchGuidelines('invalid query');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function test_search_guidelines_caches_results(): void
    {
        Http::fake([
            '*/esearch.fcgi*' => Http::response([
                'esearchresult' => [
                    'idlist' => ['9386162'],
                ],
            ]),
        ]);

        // First call — hits the API
        $this->client->searchGuidelines('heart failure');
        // Second call — should use cache
        $this->client->searchGuidelines('heart failure');

        Http::assertSentCount(1);
    }

    public function test_get_guideline_uses_cache(): void
    {
        Http::fake([
            '*/BioC_json/PMC9386162/unicode' => Http::response([
                'documents' => [
                    [
                        'passages' => [
                            ['text' => 'Guideline text for heart failure management.'],
                        ],
                    ],
                ],
            ]),
        ]);

        // First call — fetches from API
        $result1 = $this->client->getGuideline('hf_2022');
        // Second call — should use cache
        $result2 = $this->client->getGuideline('hf_2022');

        $this->assertEquals($result1, $result2);
        $this->assertStringContainsString('heart failure management', $result1);
        Http::assertSentCount(1);
    }

    public function test_get_guideline_returns_null_for_unknown_key(): void
    {
        $result = $this->client->getGuideline('nonexistent_key');

        $this->assertNull($result);
    }

    public function test_guideline_ids_contains_expected_keys(): void
    {
        $this->assertArrayHasKey('hf_2022', PmcClient::GUIDELINE_IDS);
        $this->assertArrayHasKey('htn_2017', PmcClient::GUIDELINE_IDS);
        $this->assertArrayHasKey('pvc_2020', PmcClient::GUIDELINE_IDS);
    }
}
