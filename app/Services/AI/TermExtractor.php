<?php

namespace App\Services\AI;

use App\Models\VisitNote;
use Illuminate\Support\Facades\Log;

class TermExtractor
{
    /** @var array<string> SOAP sections to extract terms from */
    private const SECTIONS = [
        'chief_complaint',
        'history_of_present_illness',
        'review_of_systems',
        'physical_exam',
        'assessment',
        'plan',
    ];

    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Extract medical terms from all SOAP sections of a visit note.
     *
     * @return array<string, array<array{term: string, start: int, end: int}>>
     */
    public function extract(VisitNote $visitNote): array
    {
        $systemPrompt = $this->promptLoader->load('term-extractor');

        $userMessage = $this->buildUserMessage($visitNote);

        $response = $this->client->chat($systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ], ['max_tokens' => 8192]);

        $terms = $this->parseResponse($response);
        $validated = $this->validateOffsets($visitNote, $terms);

        $visitNote->update(['medical_terms' => $validated]);

        return $validated;
    }

    private function buildUserMessage(VisitNote $visitNote): string
    {
        $parts = [];

        foreach (self::SECTIONS as $section) {
            $text = $visitNote->{$section};
            if ($text) {
                $parts[] = "=== SECTION: {$section} ===\n{$text}";
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return array<string, array<array{term: string, start: int, end: int}>>
     */
    private function parseResponse(string $response): array
    {
        $cleaned = trim($response);

        // Strip markdown code fences if present
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```(?:json)?\s*\n?/', '', $cleaned);
            $cleaned = preg_replace('/\n?```\s*$/', '', $cleaned);
        }

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            Log::channel('ai')->warning('TermExtractor: failed to parse response', [
                'response' => substr($response, 0, 500),
            ]);

            return [];
        }

        return $decoded;
    }

    /**
     * Validate that each term's offsets match the actual text.
     *
     * @param  array<string, array<array{term: string, start: int, end: int}>>  $terms
     * @return array<string, array<array{term: string, start: int, end: int}>>
     */
    private function validateOffsets(VisitNote $visitNote, array $terms): array
    {
        $validated = [];

        foreach ($terms as $section => $sectionTerms) {
            $text = $visitNote->{$section} ?? '';
            if (! $text || ! is_array($sectionTerms)) {
                continue;
            }

            $validTerms = [];
            foreach ($sectionTerms as $entry) {
                if (! isset($entry['term'], $entry['start'], $entry['end'])) {
                    continue;
                }

                $start = (int) $entry['start'];
                $end = (int) $entry['end'];
                $length = $end - $start;

                if ($length <= 0 || $start < 0 || $end > strlen($text)) {
                    Log::channel('ai')->debug('TermExtractor: invalid offset range', $entry);

                    continue;
                }

                $actual = substr($text, $start, $length);
                $matched = strtolower($actual) === strtolower($entry['term']);

                // Fallback: if offset is wrong, search for the term in the text
                if (! $matched) {
                    $pos = stripos($text, $entry['term']);
                    if ($pos !== false) {
                        $start = $pos;
                        $end = $pos + strlen($entry['term']);
                        $actual = substr($text, $start, strlen($entry['term']));
                        $matched = true;
                    }
                }

                if ($matched) {
                    $validated_entry = [
                        'term' => $actual,
                        'start' => $start,
                        'end' => $end,
                    ];
                    if (! empty($entry['definition'])) {
                        $validated_entry['definition'] = $entry['definition'];
                    }
                    $validTerms[] = $validated_entry;
                } else {
                    Log::channel('ai')->debug('TermExtractor: term not found in text', [
                        'section' => $section,
                        'term' => $entry['term'],
                    ]);
                }
            }

            if ($validTerms) {
                $validated[$section] = $validTerms;
            }
        }

        return $validated;
    }
}
