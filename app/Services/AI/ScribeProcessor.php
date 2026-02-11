<?php

namespace App\Services\AI;

use App\Models\Transcript;

class ScribeProcessor
{
    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
        private AiTierManager $tierManager,
    ) {}

    /**
     * Process a raw transcript into structured clinical data.
     *
     * Uses extended thinking for deeper clinical reasoning before
     * producing the structured output.
     *
     * @return array{clean_transcript: string, extracted_entities: array, soap_note: array, speakers: array, thinking: string}
     */
    public function process(Transcript $transcript): array
    {
        $systemPrompt = $this->promptLoader->load('scribe-processor');

        $visit = $transcript->visit;
        $metadata = [
            'specialty' => $visit?->specialty ?? 'general',
            'visit_date' => $visit?->visit_date?->toDateString() ?? 'unknown',
            'practitioner' => $visit?->practitioner?->full_name ?? 'unknown',
        ];

        $messages = [
            [
                'role' => 'user',
                'content' => "Process the following visit transcript.\n\n".
                    "Visit Metadata:\n".json_encode($metadata, JSON_PRETTY_PRINT)."\n\n".
                    "Raw Transcript:\n".($transcript->raw_transcript ?? ''),
            ],
        ];

        $tier = $this->tierManager->current();

        if ($tier->thinkingEnabled()) {
            $result = $this->client->chatWithThinking($systemPrompt, $messages, [
                'model' => $tier->model(),
                'max_tokens' => 16000,
                'budget_tokens' => $tier->thinkingBudget('scribe'),
            ]);

            $parsed = $this->parseJsonResponse($result['text']);
            $parsed['thinking'] = $result['thinking'];

            return $parsed;
        }

        $response = $this->client->chat($systemPrompt, $messages, [
            'model' => $tier->model(),
            'max_tokens' => 8192,
        ]);

        return $this->parseJsonResponse($response);
    }

    private function parseJsonResponse(string $response): array
    {
        // Extract JSON from markdown code block if wrapped
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $response, $matches)) {
            $response = $matches[1];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'clean_transcript' => $response,
                'extracted_entities' => [],
                'soap_note' => [],
                'speakers' => [],
                'unclear_sections' => [],
            ];
        }

        return $decoded;
    }
}
