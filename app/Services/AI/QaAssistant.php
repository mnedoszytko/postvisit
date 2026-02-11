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
    ) {}

    /**
     * Answer a patient question about their visit via streaming.
     *
     * Uses extended thinking for clinical reasoning, then streams
     * the response. Yields typed arrays for thinking vs text chunks.
     *
     * @return Generator<array{type: string, content: string}> Yields thinking/text chunks
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

        // Assemble static context (loaded once per session concept)
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

        yield from $this->client->streamWithThinking(
            $context['system_prompt'],
            $messages,
            [
                'max_tokens' => 16000,
                'budget_tokens' => config('anthropic.thinking.chat_budget', 8000),
            ]
        );
    }
}
