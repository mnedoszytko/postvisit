<?php

namespace App\Services\AI;

use App\Models\ChatSession;
use Generator;

class QaAssistant
{
    /** Patterns that indicate a simple factual question (low effort). */
    private const LOW_EFFORT_PATTERNS = [
        '/^what is\b/i',
        '/^when is\b/i',
        '/^where\b/i',
        '/^who is\b/i',
        '/\bappointment\b/i',
        '/\bfollow-up date\b/i',
        '/\bnext visit\b/i',
        '/\bnext appointment\b/i',
        '/\bwhat time\b/i',
        '/\bphone number\b/i',
        '/\baddress\b/i',
        '/\bwhich doctor\b/i',
        '/\bwhat day\b/i',
    ];

    /** Patterns that indicate max effort (escalation-level urgency). */
    private const MAX_EFFORT_PATTERNS = [
        '/\bchest pain\b/i',
        '/\bchest pressure\b/i',
        '/\bcan\'t breathe\b/i',
        '/\bcannot breathe\b/i',
        '/\bdifficulty breathing\b/i',
        '/\bshortness of breath\b/i',
        '/\bdouble dose\b/i',
        '/\boverdose[ds]?\b/i',
        '/\bsuicidal\b/i',
        '/\bkill myself\b/i',
        '/\bself-harm\b/i',
        '/\bsevere bleeding\b/i',
        '/\bvision loss\b/i',
        '/\bpassed out\b/i',
        '/\bfainted\b/i',
        '/\blost consciousness\b/i',
    ];

    /** Patterns that indicate a complex drug/safety question (high effort). */
    private const HIGH_EFFORT_PATTERNS = [
        '/\binteractions?\b/i',
        '/\bcontraindications?\b/i',
        '/\bside effects?\b/i',
        '/\badverse\b/i',
        '/\ballergic\b/i',
        '/\bis it safe\b/i',
        '/\bcan i take .+ with\b/i',
        '/\btogether with\b/i',
        '/\bcombined with\b/i',
        '/\bmiss(?:ed)? (?:a )?dose\b/i',
        '/\bstop taking\b/i',
        '/\bincrease .+ dose\b/i',
        '/\bdecrease .+ dose\b/i',
        '/\balternatives?\b/i',
    ];

    public function __construct(
        private AnthropicClient $client,
        private ContextAssembler $contextAssembler,
        private EscalationDetector $escalationDetector,
        private AiTierManager $tierManager,
        private ClinicalReasoningPipeline $reasoningPipeline,
    ) {}

    /**
     * Generate a quick first response using Haiku with minimal context.
     * Yields 'quick' type chunks for the SSE stream.
     *
     * @return Generator<array{type: string, content: string}>
     */
    public function quickAnswer(ChatSession $session, string $question): Generator
    {
        $visit = $session->visit;
        $context = $this->contextAssembler->assembleQuickContext($visit);

        $messages = $context['context_messages'];

        // Only last 2 history messages for speed
        $history = $session->messages()
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->sender_type === 'patient' ? 'user' : 'assistant',
                'content' => $msg->message_text,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        foreach ($this->client->stream($context['system_prompt'], $messages, [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 150,
        ]) as $chunk) {
            yield ['type' => 'quick', 'content' => $chunk];
        }
    }

    /**
     * Classify question complexity to determine thinking budget.
     *
     * Returns 'low', 'medium', 'high', or 'max' based on keyword heuristics.
     * - low:    simple factual lookups (appointment dates, names)
     * - medium: general clinical questions (default)
     * - high:   drug interactions, safety concerns, multi-drug questions
     * - max:    escalation-level urgency (chest pain, overdose, suicidal)
     */
    public function classifyEffort(string $question): string
    {
        // Max effort: escalation-critical patterns (checked first — safety priority)
        foreach (self::MAX_EFFORT_PATTERNS as $pattern) {
            if (preg_match($pattern, $question)) {
                return 'max';
            }
        }

        // High effort: drug safety and interaction questions
        foreach (self::HIGH_EFFORT_PATTERNS as $pattern) {
            if (preg_match($pattern, $question)) {
                return 'high';
            }
        }

        // Low effort: simple factual lookups
        foreach (self::LOW_EFFORT_PATTERNS as $pattern) {
            if (preg_match($pattern, $question)) {
                return 'low';
            }
        }

        // Default: medium effort
        return 'medium';
    }

    /**
     * Answer a patient question about their visit via streaming.
     *
     * Uses extended thinking for clinical reasoning, then streams
     * the response. Yields typed arrays for thinking vs text chunks.
     * Complex clinical questions (drug safety, dosage, symptom combinations)
     * trigger the Plan-Execute-Verify pipeline on Opus 4.6 tier.
     *
     * @return Generator<array{type: string, content: string}> Yields effort/status/thinking/text/phase chunks
     */
    public function answer(ChatSession $session, string $question): Generator
    {
        $visit = $session->visit;

        // Classify effort level for adaptive thinking budget
        $effort = $this->classifyEffort($question);
        yield ['type' => 'effort', 'content' => $effort];

        // Check for urgent content before answering
        $escalation = $this->escalationDetector->evaluate($question, $visit);
        if ($escalation['is_urgent'] && $escalation['severity'] === 'critical') {
            yield ['type' => 'text', 'content' => $escalation['recommended_action']];

            return;
        }

        $tier = $this->tierManager->current();

        // Send status events during context assembly
        if ($tier->thinkingEnabled()) {
            yield ['type' => 'status', 'content' => 'Loading clinical data...'];
        }

        // Deep reasoning pipeline for complex clinical questions on Opus 4.6 tier
        if ($tier->thinkingEnabled() && $this->reasoningPipeline->shouldUseDeepReasoning($question)) {
            $visit->load(['patient', 'practitioner', 'visitNote', 'observations', 'conditions', 'prescriptions.medication', 'transcript']);

            // Pre-assemble full context NOW while user sees "Loading clinical data..."
            // This moves the expensive FDA/PMC/guidelines calls out of the plan→execute gap
            yield ['type' => 'status', 'content' => 'Loading clinical guidelines...'];
            $context = $this->contextAssembler->assembleForVisit($visit, 'qa-assistant');

            // Emit token breakdown so frontend can display context size
            $tokenBreakdown = $this->contextAssembler->getTokenBreakdown();
            if (! empty($tokenBreakdown)) {
                yield ['type' => 'context_tokens', 'content' => json_encode($tokenBreakdown)];
            }

            yield ['type' => 'status', 'content' => 'Deep clinical reasoning...'];

            // Build conversation history for the pipeline
            $historyMessages = [];
            $history = $session->messages()
                ->orderBy('created_at')
                ->get();

            foreach ($history as $msg) {
                $historyMessages[] = [
                    'role' => $msg->sender_type === 'patient' ? 'user' : 'assistant',
                    'content' => $msg->message_text,
                ];
            }

            yield from $this->reasoningPipeline->reason($visit, $historyMessages, $question, $context);

            return;
        }

        // Standard path: assemble context + stream response
        yield ['type' => 'status', 'content' => 'Preparing detailed analysis...'];

        $context = $this->contextAssembler->assembleForVisit($visit, 'qa-assistant');

        // Emit token breakdown so frontend can display context size
        $tokenBreakdown = $this->contextAssembler->getTokenBreakdown();
        if (! empty($tokenBreakdown)) {
            yield ['type' => 'context_tokens', 'content' => json_encode($tokenBreakdown)];
        }

        // Build message array: static context + conversation history + new question
        $messages = $context['context_messages'];

        // Append conversation history
        $history = $session->messages()
            ->orderBy('created_at')
            ->get();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->sender_type === 'patient' ? 'user' : 'assistant',
                'content' => $msg->message_text,
            ];
        }

        // Append current question
        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        // Add escalation context if moderate/high urgency detected
        if ($escalation['is_urgent']) {
            $messages[] = [
                'role' => 'user',
                'content' => "[SYSTEM NOTE: Urgency detected - severity: {$escalation['severity']}. ".
                    "Reason: {$escalation['reason']}. Address this concern appropriately in your response.]",
            ];
        }

        if ($tier->thinkingEnabled()) {
            $budgets = $tier->thinkingBudgetForEffort($effort);

            yield from $this->client->streamWithThinking(
                $context['system_prompt'],
                $messages,
                [
                    'model' => $tier->model(),
                    'max_tokens' => $budgets['max_tokens'],
                    'budget_tokens' => $budgets['budget_tokens'],
                ]
            );
        } else {
            foreach ($this->client->stream($context['system_prompt'], $messages, [
                'model' => $tier->model(),
                'max_tokens' => 4096,
            ]) as $chunk) {
                yield ['type' => 'text', 'content' => $chunk];
            }
        }
    }
}
