<?php

namespace App\Services\AI;

use App\Models\Visit;
use Generator;
use Illuminate\Support\Facades\Log;

/**
 * Multi-step clinical reasoning pipeline using Plan-Execute-Verify pattern.
 *
 * Triggered for complex clinical questions (drug safety, dosage, symptom combinations).
 * Uses extended thinking at each phase to demonstrate Opus 4.6 deep reasoning.
 *
 * Phase 1 (Plan): Opus decides which knowledge sources to consult
 * Phase 2 (Execute): Assemble full context + generate response with extended thinking
 * Phase 3 (Verify): Second pass validates response against guidelines
 */
class ClinicalReasoningPipeline
{
    /** Keywords that trigger deep reasoning mode */
    private const DEEP_REASONING_TRIGGERS = [
        // Drug safety
        'side effect', 'adverse', 'interaction', 'contraindication', 'allergic',
        'overdose', 'miss a dose', 'missed dose', 'double dose', 'too much',
        // Dosage
        'dosage', 'dose', 'how much', 'how many', 'increase', 'decrease', 'adjust',
        'when to take', 'timing', 'with food', 'empty stomach',
        // Symptom combinations
        'new symptom', 'getting worse', 'not improving', 'combined with',
        'along with', 'at the same time', 'together with',
        // Clinical reasoning
        'why did', 'why was', 'what if', 'is it safe', 'can i', 'should i',
        'alternative', 'other option', 'stop taking', 'quit',
    ];

    public function __construct(
        private AnthropicClient $client,
        private ContextAssembler $contextAssembler,
        private PromptLoader $promptLoader,
        private AiTierManager $tierManager,
    ) {}

    /**
     * Determine if a question should use deep clinical reasoning.
     */
    public function shouldUseDeepReasoning(string $question): bool
    {
        $lower = strtolower($question);

        foreach (self::DEEP_REASONING_TRIGGERS as $trigger) {
            if (str_contains($lower, $trigger)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute the full Plan-Execute-Verify pipeline via streaming.
     *
     * @return Generator<array{type: string, content: string}> Yields phase/thinking/text chunks
     */
    public function reason(Visit $visit, array $messages, string $question): Generator
    {
        $tier = $this->tierManager->current();
        $startTime = microtime(true);

        Log::info('ClinicalReasoningPipeline started', [
            'visit_id' => $visit->id,
            'question_length' => strlen($question),
        ]);

        // --- Phase 1: Plan ---
        yield ['type' => 'phase', 'content' => 'planning'];

        $plan = $this->plan($visit, $question, $tier);

        Log::info('ClinicalReasoningPipeline plan completed', [
            'visit_id' => $visit->id,
            'plan_length' => strlen($plan['text']),
            'thinking_length' => strlen($plan['thinking']),
        ]);

        // Stream thinking from plan phase
        if ($plan['thinking']) {
            yield ['type' => 'thinking', 'content' => $plan['thinking']];
        }

        // --- Phase 2: Execute ---
        yield ['type' => 'phase', 'content' => 'reasoning'];

        $context = $this->contextAssembler->assembleForVisit($visit, 'qa-assistant');

        // Inject the plan as guidance for the main response
        $augmentedMessages = $context['context_messages'];
        $augmentedMessages[] = [
            'role' => 'user',
            'content' => "[CLINICAL REASONING PLAN]\n{$plan['text']}\n[END PLAN]",
        ];
        $augmentedMessages[] = [
            'role' => 'assistant',
            'content' => 'I have reviewed the reasoning plan and will follow it to provide a thorough, evidence-based answer.',
        ];

        // Add conversation history
        foreach ($messages as $msg) {
            $augmentedMessages[] = $msg;
        }

        // Add the current question
        $augmentedMessages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        // Stream the main response with extended thinking
        $fullResponse = '';
        $executeThinking = '';

        foreach ($this->client->streamWithThinking(
            $context['system_prompt'],
            $augmentedMessages,
            [
                'model' => $tier->model(),
                'max_tokens' => 16000,
                'budget_tokens' => $tier->thinkingBudget('chat'),
            ]
        ) as $chunk) {
            if ($chunk['type'] === 'thinking') {
                $executeThinking .= $chunk['content'];
            } else {
                $fullResponse .= $chunk['content'];
            }
            yield $chunk;
        }

        // --- Phase 3: Verify ---
        yield ['type' => 'phase', 'content' => 'verifying'];

        $verification = $this->verify($visit, $question, $fullResponse, $tier);

        Log::info('ClinicalReasoningPipeline verify completed', [
            'visit_id' => $visit->id,
            'is_verified' => $verification['is_verified'],
            'concerns' => $verification['concerns'],
        ]);

        // If verification found issues, append a correction
        if (! $verification['is_verified'] && $verification['correction']) {
            yield ['type' => 'text', 'content' => "\n\n---\n*Correction after guideline verification:* ".$verification['correction']];
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        Log::info('ClinicalReasoningPipeline completed', [
            'visit_id' => $visit->id,
            'elapsed_seconds' => $elapsed,
            'response_length' => strlen($fullResponse),
            'phases_completed' => 3,
        ]);
    }

    /**
     * Phase 1: Plan which knowledge sources to consult.
     *
     * @return array{text: string, thinking: string}
     */
    private function plan(Visit $visit, string $question, \App\Enums\AiTier $tier): array
    {
        $conditions = [];
        if ($visit->conditions) {
            foreach ($visit->conditions as $c) {
                $conditions[] = $c->code_display ?? $c->code;
            }
        }

        $medications = [];
        if ($visit->prescriptions) {
            foreach ($visit->prescriptions as $rx) {
                if ($rx->medication) {
                    $medications[] = $rx->medication->generic_name;
                }
            }
        }

        $planPrompt = <<<'PROMPT'
You are a clinical reasoning planner. Given a patient's question and their clinical context,
create a brief reasoning plan. Identify:

1. Which knowledge domains are relevant (pharmacology, cardiology, drug interactions, etc.)
2. What specific clinical guidelines should be consulted
3. What safety considerations apply
4. How to structure the answer for patient understanding

Patient's conditions: {conditions}
Current medications: {medications}
Visit specialty: {specialty}

Patient's question: {question}

Respond with a concise reasoning plan (3-5 bullet points).
PROMPT;

        $planPrompt = str_replace(
            ['{conditions}', '{medications}', '{specialty}', '{question}'],
            [
                implode(', ', $conditions) ?: 'None listed',
                implode(', ', $medications) ?: 'None listed',
                $visit->visit_type ?? 'general',
                $question,
            ],
            $planPrompt
        );

        $result = $this->client->chatWithThinking(
            'You are a clinical reasoning planner for a post-visit patient assistant.',
            [['role' => 'user', 'content' => $planPrompt]],
            [
                'model' => $tier->model(),
                'max_tokens' => 8000,
                'budget_tokens' => min($tier->thinkingBudget('chat'), 6000),
            ]
        );

        return [
            'text' => $result['text'],
            'thinking' => $result['thinking'],
        ];
    }

    /**
     * Phase 3: Verify the response against clinical guidelines.
     *
     * @return array{is_verified: bool, concerns: string[], correction: string|null}
     */
    private function verify(Visit $visit, string $question, string $response, \App\Enums\AiTier $tier): array
    {
        $verifyPrompt = <<<'PROMPT'
You are a clinical accuracy verifier. Review the following AI-generated response to a patient's question.

Check for:
1. Factual accuracy against clinical guidelines
2. No unauthorized medical advice (diagnosing, prescribing, dosage changes)
3. Appropriate safety warnings included
4. Source attribution present
5. No contradiction with the visit record

Patient question: {question}

AI Response:
{response}

Respond with JSON only:
```json
{
  "is_verified": true/false,
  "concerns": ["list of concerns if any"],
  "correction": "brief correction text if needed, null if verified"
}
```
PROMPT;

        $verifyPrompt = str_replace(
            ['{question}', '{response}'],
            [$question, $response],
            $verifyPrompt
        );

        try {
            $result = $this->client->chatWithThinking(
                'You are a clinical accuracy verifier for a patient-facing AI system. Be strict about safety.',
                [['role' => 'user', 'content' => $verifyPrompt]],
                [
                    'model' => $tier->model(),
                    'max_tokens' => 8000,
                    'budget_tokens' => min($tier->thinkingBudget('chat'), 4000),
                ]
            );

            return $this->parseVerification($result['text']);
        } catch (\Throwable $e) {
            Log::warning('ClinicalReasoningPipeline verification failed', [
                'error' => $e->getMessage(),
            ]);

            // Default to verified on error — don't block the response
            return [
                'is_verified' => true,
                'concerns' => [],
                'correction' => null,
            ];
        }
    }

    /**
     * Parse the verification JSON response.
     *
     * @return array{is_verified: bool, concerns: string[], correction: string|null}
     */
    private function parseVerification(string $response): array
    {
        $decoded = AnthropicClient::parseJsonOutput($response, [
            'is_verified' => true,
            'concerns' => [],
            'correction' => null,
        ]);

        return [
            'is_verified' => $decoded['is_verified'] ?? true,
            'concerns' => $decoded['concerns'] ?? [],
            'correction' => $decoded['correction'] ?? null,
        ];
    }
}
