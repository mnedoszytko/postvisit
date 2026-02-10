<?php

namespace App\Services\AI;

use App\Models\Visit;

class VisitSummarizer
{
    public function __construct(
        private AnthropicClient $client,
        private ContextAssembler $contextAssembler,
    ) {}

    /**
     * Create a patient-friendly visit summary.
     *
     * @return string Markdown-formatted summary
     */
    public function summarize(Visit $visit): string
    {
        $context = $this->contextAssembler->assembleForVisit($visit, 'visit-summarizer');

        $messages = $context['context_messages'];

        $messages[] = [
            'role' => 'user',
            'content' => 'Generate a patient-friendly summary of this visit. ' .
                'Follow the output format specified in your instructions.',
        ];

        return $this->client->chat(
            $context['system_prompt'],
            $messages,
            ['max_tokens' => 4096]
        );
    }
}
