<?php

namespace App\Services\AI;

use App\Models\Visit;
use Generator;

class PatientEducationGenerator
{
    public function __construct(
        private AnthropicClient $client,
        private ContextAssembler $contextAssembler,
        private AiTierManager $tierManager,
        private ToolExecutor $toolExecutor,
    ) {}

    /**
     * Generate a comprehensive patient education document for a visit.
     *
     * Phase 1: Non-streaming tool use loop — AI gathers data via medical database tools.
     * Phase 2: Streaming generation — AI writes the education document with gathered data.
     *
     * @return Generator<array{type: string, content: string}> Yields status/tool_use/thinking/text chunks
     */
    public function generate(Visit $visit): Generator
    {
        $tier = $this->tierManager->current();

        // Assemble full context using the patient-education prompt
        $context = $this->contextAssembler->assembleForVisit($visit, 'patient-education');

        $baseMessages = $context['context_messages'];

        // --- Phase 1: Gather data via tool use (non-streaming) ---
        yield ['type' => 'status', 'content' => 'Researching your medications and conditions...'];

        $toolResults = $this->gatherToolData($context, $tier);

        // Emit tool usage info to frontend
        foreach ($toolResults['tools_used'] as $tool) {
            yield ['type' => 'tool_use', 'content' => json_encode($tool)];
        }

        // --- Phase 2: Generate education document (streaming) with tool data in context ---
        yield ['type' => 'status', 'content' => 'Writing your personalized health guide...'];

        // Build final messages: include tool-gathered data as additional context
        $messages = $baseMessages;

        if (! empty($toolResults['tools_used'])) {
            $toolContext = $this->formatToolResultsAsContext($toolResults);
            $messages[] = [
                'role' => 'user',
                'content' => $toolContext,
            ];
            $messages[] = [
                'role' => 'assistant',
                'content' => 'I have reviewed the medical database results and will incorporate this verified data into the education document.',
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => 'Generate a comprehensive patient education document for my visit. Cover everything I need to know. Incorporate the verified medical database information where relevant.',
        ];

        // Stream the final document
        $options = [
            'max_tokens' => 65536,
        ];

        if ($tier->thinkingEnabled()) {
            $options['budget_tokens'] = $tier->thinkingBudget('reasoning');
            $options['model'] = $tier->model();
        } else {
            $options['model'] = $tier->model();
            $options['max_tokens'] = 16000;
        }

        if ($tier->thinkingEnabled()) {
            yield from $this->client->streamWithThinking(
                $context['system_prompt'],
                $messages,
                $options,
            );
        } else {
            foreach ($this->client->stream($context['system_prompt'], $messages, $options) as $chunk) {
                yield ['type' => 'text', 'content' => $chunk];
            }
        }
    }

    /**
     * Phase 1: Use AI tool calls to gather verified medical data.
     *
     * The AI decides which tools to call based on the patient's conditions
     * and medications. This happens non-streaming before the main generation.
     *
     * @return array{text: string, thinking: string, tools_used: array}
     */
    private function gatherToolData(array $context, \App\Enums\AiTier $tier): array
    {
        $gatherPrompt = 'Review this patient\'s visit data carefully. Use the available tools to verify and gather additional safety information about their medications, check for drug interactions between any medications they are taking, and look up reference ranges for any lab values mentioned. Call each relevant tool now — this data will be used to generate an accurate patient education document.';

        $messages = $context['context_messages'];
        $messages[] = [
            'role' => 'user',
            'content' => $gatherPrompt,
        ];

        $options = [
            'max_tokens' => 4096,
            'model' => $tier->model(),
        ];

        if ($tier->thinkingEnabled()) {
            $options['budget_tokens'] = $tier->thinkingBudget('reasoning');
        } else {
            $options['budget_tokens'] = 0;
        }

        try {
            return $this->client->chatWithTools(
                systemPrompt: $context['system_prompt'],
                messages: $messages,
                tools: $this->getToolDefinitions(),
                toolExecutor: fn (string $name, array $input) => $this->toolExecutor->execute($name, $input),
                options: $options,
            );
        } catch (\Throwable $e) {
            // If tool gathering fails, continue without tool data
            \Illuminate\Support\Facades\Log::warning('Tool data gathering failed, continuing without', [
                'error' => $e->getMessage(),
            ]);

            return ['text' => '', 'thinking' => '', 'tools_used' => []];
        }
    }

    /**
     * Format gathered tool results as context for the generation phase.
     */
    private function formatToolResultsAsContext(array $toolResults): string
    {
        $parts = ['--- VERIFIED MEDICAL DATABASE RESULTS ---'];
        $parts[] = 'The following data was retrieved from medical databases (OpenFDA, clinical references) to ensure accuracy:';

        if (! empty($toolResults['text'])) {
            $parts[] = '';
            $parts[] = $toolResults['text'];
        }

        $parts[] = '--- END VERIFIED MEDICAL DATABASE RESULTS ---';

        return implode("\n", $parts);
    }

    /**
     * Get tool definitions in Anthropic format.
     *
     * @return array<int, array{name: string, description: string, input_schema: array}>
     */
    private function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'check_drug_interaction',
                'description' => 'Check for known interactions between two medications using FDA adverse event data and drug label warnings. Use when the patient takes multiple drugs.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'drug1' => [
                            'type' => 'string',
                            'description' => 'First drug generic name',
                        ],
                        'drug2' => [
                            'type' => 'string',
                            'description' => 'Second drug generic name',
                        ],
                    ],
                    'required' => ['drug1', 'drug2'],
                ],
            ],
            [
                'name' => 'get_drug_safety_info',
                'description' => 'Get comprehensive safety information for a medication including warnings, side effects, boxed warnings, drug interactions, dosage, and patient information from FDA labels.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'drug_name' => [
                            'type' => 'string',
                            'description' => 'Drug generic name',
                        ],
                    ],
                    'required' => ['drug_name'],
                ],
            ],
            [
                'name' => 'get_lab_reference_range',
                'description' => 'Get normal reference ranges for a lab test, with interpretation guidance for different levels (normal, borderline, high/low).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'test_name' => [
                            'type' => 'string',
                            'description' => 'Lab test name (e.g., "glucose", "HbA1c", "cholesterol", "TSH", "hemoglobin")',
                        ],
                    ],
                    'required' => ['test_name'],
                ],
            ],
        ];
    }
}
