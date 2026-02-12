<?php

namespace App\Services\AI;

use App\Models\ChatSession;
use Generator;

class QaAssistant
{
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
     * Answer a patient question about their visit via streaming.
     *
     * Uses extended thinking for clinical reasoning, then streams
     * the response. Yields typed arrays for thinking vs text chunks.
     * Complex clinical questions (drug safety, dosage, symptom combinations)
     * trigger the Plan-Execute-Verify pipeline on Opus 4.6 tier.
     *
     * @return Generator<array{type: string, content: string}> Yields status/thinking/text/phase chunks
     */
    public function answer(ChatSession $session, string $question): Generator
    {
        $visit = $session->visit;

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
            yield from $this->client->streamWithThinking(
                $context['system_prompt'],
                $messages,
                [
                    'model' => $tier->model(),
                    'max_tokens' => 16000,
                    'budget_tokens' => $tier->thinkingBudget('chat'),
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
