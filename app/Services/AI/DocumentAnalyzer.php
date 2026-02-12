<?php

namespace App\Services\AI;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentAnalyzer
{
    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Analyze a medical document using Claude's vision capabilities.
     *
     * @return array Structured analysis (see prompts/document-analyzer.md for format)
     */
    public function analyze(Document $document): array
    {
        $systemPrompt = $this->promptLoader->load('document-analyzer');

        $contentBlocks = $this->buildContentBlocks($document);
        $contentBlocks[] = ['type' => 'text', 'text' => $this->buildContextText($document)];

        $messages = [
            [
                'role' => 'user',
                'content' => $contentBlocks,
            ],
        ];

        $response = $this->client->chat($systemPrompt, $messages, [
            'max_tokens' => 4096,
        ]);

        return $this->parseJsonResponse($response);
    }

    /**
     * Build multimodal content blocks for the document file.
     *
     * @return array<array<string, mixed>>
     */
    private function buildContentBlocks(Document $document): array
    {
        $fileContent = Storage::disk('local')->get($document->file_path);
        $base64 = base64_encode($fileContent);
        $mimeType = Storage::disk('local')->mimeType($document->file_path);

        if ($document->content_type === 'pdf') {
            return [
                [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => 'application/pdf',
                        'data' => $base64,
                    ],
                ],
            ];
        }

        // Image types
        $mediaType = match (true) {
            str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') => 'image/jpeg',
            str_contains($mimeType, 'png') => 'image/png',
            str_contains($mimeType, 'gif') => 'image/gif',
            str_contains($mimeType, 'webp') => 'image/webp',
            default => $mimeType,
        };

        return [
            [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mediaType,
                    'data' => $base64,
                ],
            ],
        ];
    }

    /**
     * Build brief visit context text for the AI prompt.
     */
    private function buildContextText(Document $document): string
    {
        $parts = ['Analyze this medical document and return structured findings as JSON.'];

        $parts[] = "Document title: {$document->title}";
        $parts[] = "Document type: {$document->document_type}";

        $visit = $document->visit;
        if ($visit) {
            $parts[] = 'Visit date: '.($visit->started_at?->format('Y-m-d') ?? 'unknown');

            if ($visit->reason_for_visit) {
                $parts[] = "Visit reason: {$visit->reason_for_visit}";
            }

            $visitNote = $visit->visitNote;
            if ($visitNote?->assessment) {
                $parts[] = "Assessment: {$visitNote->assessment}";
            }
        }

        return implode("\n", $parts);
    }

    private function parseJsonResponse(string $response): array
    {
        return AnthropicClient::parseJsonOutput($response, [
            'summary' => 'Unable to analyze this document automatically.',
            'findings' => [],
            'key_values' => [],
            'confidence' => 'low',
            'document_category' => 'other',
            'safety_note' => 'This is an AI-generated analysis for informational purposes only. It does not constitute a medical diagnosis. Always consult your healthcare provider for clinical interpretation of your results.',
        ]);
    }
}
