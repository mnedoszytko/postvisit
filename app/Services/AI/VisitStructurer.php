<?php

namespace App\Services\AI;

use App\Models\Visit;

class VisitStructurer
{
    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Structure visit data into patient-browsable sections.
     *
     * Takes processed transcript, discharge notes, and documents,
     * and organizes them into the standard visit section format.
     *
     * @return array Structured visit sections (see prompts/visit-structurer.md)
     */
    public function structure(Visit $visit): array
    {
        $systemPrompt = $this->promptLoader->load('visit-structurer');

        $inputParts = [];

        // Transcript data (processed)
        if ($visit->transcript) {
            $inputParts[] = "## Processed Transcript";
            if ($visit->transcript->extracted_entities) {
                $inputParts[] = "Extracted Entities:\n" .
                    json_encode($visit->transcript->extracted_entities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            if ($visit->transcript->soap_note) {
                $inputParts[] = "SOAP Note:\n" .
                    json_encode($visit->transcript->soap_note, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $inputParts[] = "Clean Transcript:\n" .
                ($visit->transcript->clean_text ?? $visit->transcript->raw_text ?? '');
        }

        // Discharge notes
        if ($visit->visitNote && $visit->visitNote->content) {
            $inputParts[] = "\n## Discharge Notes\n" . $visit->visitNote->content;
        }

        // Documents
        if ($visit->documents && $visit->documents->isNotEmpty()) {
            $inputParts[] = "\n## Uploaded Documents";
            foreach ($visit->documents as $doc) {
                $inputParts[] = "- {$doc->title} ({$doc->document_type})" .
                    ($doc->extracted_text ? ": " . $doc->extracted_text : '');
            }
        }

        // Visit metadata
        $inputParts[] = "\n## Visit Metadata";
        $inputParts[] = "Specialty: " . ($visit->specialty ?? 'general');
        $inputParts[] = "Date: " . ($visit->visit_date ?? 'unknown');

        $messages = [
            [
                'role' => 'user',
                'content' => "Structure the following visit data into patient-browsable sections.\n\n" .
                    implode("\n", $inputParts),
            ],
        ];

        $response = $this->client->chat($systemPrompt, $messages, [
            'max_tokens' => 8192,
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
                'visit_type' => 'unknown',
                'sections' => [],
                'specialty_data' => [],
                'completeness' => [
                    'score' => 0,
                    'missing_sections' => ['parse_error'],
                    'notes' => 'Failed to parse AI response into structured format.',
                ],
            ];
        }

        return $decoded;
    }
}
