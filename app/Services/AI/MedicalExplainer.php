<?php

namespace App\Services\AI;

use App\Models\Visit;
use Generator;

class MedicalExplainer
{
    public function __construct(
        private AnthropicClient $client,
        private ContextAssembler $contextAssembler,
        private AiTierManager $tierManager,
    ) {}

    /**
     * Explain a medical term or element in the context of the patient's visit.
     *
     * @param  Visit  $visit  The visit context
     * @param  string  $element  The medical term or element to explain
     * @param  string|null  $section  The visit section the element comes from (e.g., 'diagnosis', 'medications')
     * @return Generator<string> Yields explanation text chunks
     */
    public function explain(Visit $visit, string $element, ?string $section = null): Generator
    {
        $context = $this->contextAssembler->assembleForVisit($visit, 'medical-explainer');

        $messages = $context['context_messages'];

        $request = 'Please explain the following medical element in simple language, '.
            "in the context of this patient's visit.\n\n".
            "Element: {$element}";

        if ($section) {
            $request .= "\nFrom section: {$section}";
        }

        $messages[] = [
            'role' => 'user',
            'content' => $request,
        ];

        $tier = $this->tierManager->current();

        yield from $this->client->stream(
            $context['system_prompt'],
            $messages,
            [
                'model' => $tier->model(),
                'max_tokens' => 2048,
            ]
        );
    }
}
