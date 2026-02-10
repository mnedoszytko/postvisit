<?php

namespace App\Services\AI;

class MedsAnalyzer
{
    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Analyze a set of medications for interactions, side effects, and practical guidance.
     *
     * @param array $medications Array of medication data with dosing info
     * @return array Analysis results (see prompts/meds-analyzer.md for format)
     */
    public function analyze(array $medications): array
    {
        $systemPrompt = $this->promptLoader->load('meds-analyzer');

        $messages = [
            [
                'role' => 'user',
                'content' => "Analyze the following medications:\n\n" .
                    json_encode($medications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ],
        ];

        $response = $this->client->chat($systemPrompt, $messages, [
            'max_tokens' => 4096,
        ]);

        return $this->parseJsonResponse($response);
    }

    private function parseJsonResponse(string $response): array
    {
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $response, $matches)) {
            $response = $matches[1];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'medications' => [],
                'interactions' => [],
                'changes_summary' => [
                    'new' => [],
                    'changed' => [],
                    'continued' => [],
                    'discontinued' => [],
                ],
            ];
        }

        return $decoded;
    }
}
