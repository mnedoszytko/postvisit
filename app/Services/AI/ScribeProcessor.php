<?php

namespace App\Services\AI;

use App\Models\Transcript;

class ScribeProcessor
{
    /** Minimum word count for a transcript to be considered clinically useful. */
    private const MIN_WORD_COUNT = 30;

    /** Clinical keywords that indicate meaningful medical content. */
    private const CLINICAL_KEYWORDS = [
        'patient', 'symptoms', 'diagnosis', 'medication', 'prescri',
        'treatment', 'blood', 'pressure', 'heart', 'pain', 'mg',
        'dose', 'history', 'exam', 'lab', 'test', 'follow',
        'condition', 'chronic', 'acute', 'doctor', 'nurse',
    ];

    /** Minimum number of clinical keywords required. */
    private const MIN_CLINICAL_KEYWORDS = 3;

    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
        private AiTierManager $tierManager,
    ) {}

    /**
     * Evaluate whether a transcript has enough clinical content to process.
     *
     * @return array{sufficient: bool, word_count: int, clinical_keyword_count: int, reason: string|null}
     */
    public static function evaluateQuality(string $rawTranscript): array
    {
        $text = trim($rawTranscript);
        $wordCount = str_word_count($text);

        if ($wordCount < self::MIN_WORD_COUNT) {
            return [
                'sufficient' => false,
                'word_count' => $wordCount,
                'clinical_keyword_count' => 0,
                'reason' => 'insufficient_length',
            ];
        }

        $lowerText = strtolower($text);
        $clinicalKeywordCount = 0;
        foreach (self::CLINICAL_KEYWORDS as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                $clinicalKeywordCount++;
            }
        }

        if ($clinicalKeywordCount < self::MIN_CLINICAL_KEYWORDS) {
            return [
                'sufficient' => false,
                'word_count' => $wordCount,
                'clinical_keyword_count' => $clinicalKeywordCount,
                'reason' => 'insufficient_clinical_content',
            ];
        }

        return [
            'sufficient' => true,
            'word_count' => $wordCount,
            'clinical_keyword_count' => $clinicalKeywordCount,
            'reason' => null,
        ];
    }

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
        return AnthropicClient::parseJsonOutput($response, [
            'clean_transcript' => $response,
            'extracted_entities' => [],
            'soap_note' => [],
            'speakers' => [],
            'unclear_sections' => [],
        ]);
    }
}
