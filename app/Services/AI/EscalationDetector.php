<?php

namespace App\Services\AI;

use App\Models\Visit;

class EscalationDetector
{
    private const CRITICAL_KEYWORDS = [
        'chest pain', 'chest pressure', 'chest tightness',
        'can\'t breathe', 'cannot breathe', 'difficulty breathing', 'shortness of breath',
        'worst headache', 'sudden headache',
        'passed out', 'fainted', 'lost consciousness', 'blacked out',
        'suicidal', 'kill myself', 'end my life', 'self-harm', 'hurt myself',
        'throat swelling', 'can\'t swallow', 'face drooping', 'arm weakness',
        'severe bleeding', 'uncontrolled bleeding',
        'vision loss', 'can\'t see', 'sudden blindness',
    ];

    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Evaluate a patient message for urgency.
     *
     * First performs a fast keyword check for critical terms,
     * then uses AI for nuanced evaluation if no critical keywords found.
     *
     * @param string $message The patient's message text
     * @param Visit|null $visit Visit context for condition-aware evaluation
     * @return array{is_urgent: bool, severity: string, reason: string, recommended_action: string}
     */
    public function evaluate(string $message, ?Visit $visit = null): array
    {
        // Fast path: check for critical keywords
        $keywordResult = $this->checkCriticalKeywords($message);
        if ($keywordResult['is_urgent']) {
            return $keywordResult;
        }

        // AI evaluation for nuanced detection
        return $this->aiEvaluate($message, $visit);
    }

    private function checkCriticalKeywords(string $message): array
    {
        $lower = strtolower($message);

        foreach (self::CRITICAL_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return [
                    'is_urgent' => true,
                    'severity' => 'critical',
                    'reason' => "Message contains critical symptom: '{$keyword}'",
                    'trigger_phrases' => [$keyword],
                    'recommended_action' => 'This sounds like it could be urgent. Please contact your doctor immediately or call emergency services (911). Do not wait.',
                    'context_factors' => [],
                ];
            }
        }

        return [
            'is_urgent' => false,
            'severity' => 'low',
            'reason' => 'No critical keywords detected',
            'trigger_phrases' => [],
            'recommended_action' => 'No action needed',
            'context_factors' => [],
        ];
    }

    private function aiEvaluate(string $message, ?Visit $visit): array
    {
        $systemPrompt = $this->promptLoader->load('escalation-detector');

        $input = "Evaluate the following patient message for urgency.\n\n";
        $input .= "Patient Message: {$message}\n\n";

        if ($visit) {
            $conditions = [];
            if ($visit->conditions) {
                foreach ($visit->conditions as $condition) {
                    $conditions[] = $condition->display_name;
                }
            }

            if ($conditions) {
                $input .= "Known Conditions: " . implode(', ', $conditions) . "\n";
            }

            $input .= "Visit Specialty: " . ($visit->specialty ?? 'general') . "\n";
        }

        $messages = [
            ['role' => 'user', 'content' => $input],
        ];

        // Use a faster/cheaper model for escalation checks
        $response = $this->client->chat($systemPrompt, $messages, [
            'model' => config('anthropic.escalation_model', 'claude-sonnet-4-5-20250929'),
            'max_tokens' => 512,
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
            // Default to non-urgent if we can't parse
            return [
                'is_urgent' => false,
                'severity' => 'low',
                'reason' => 'Unable to evaluate (parse error)',
                'trigger_phrases' => [],
                'recommended_action' => 'No action needed',
                'context_factors' => [],
            ];
        }

        return $decoded;
    }
}
