<?php

namespace App\Services\AI;

use Generator;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    private string $defaultModel;

    public function __construct()
    {
        $this->defaultModel = config('anthropic.default_model', 'claude-opus-4-6');
    }

    /**
     * Send a chat request and return the full response.
     *
     * @param string $systemPrompt System-level instructions
     * @param array $messages Array of message objects [{role: 'user'|'assistant', content: '...'}]
     * @param array $options Override model, max_tokens, temperature, etc.
     * @return string The assistant's response text
     */
    public function chat(string $systemPrompt, array $messages, array $options = []): string
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $response = \Anthropic\Laravel\Facades\Anthropic::messages()->create([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ]);

        $text = $response->content[0]->text ?? '';

        Log::channel('ai')->info('Anthropic chat request', [
            'model' => $model,
            'input_tokens' => $response->usage->inputTokens ?? null,
            'output_tokens' => $response->usage->outputTokens ?? null,
        ]);

        return $text;
    }

    /**
     * Send a streaming chat request and yield tokens.
     *
     * @param string $systemPrompt System-level instructions
     * @param array $messages Array of message objects
     * @param array $options Override model, max_tokens, temperature, etc.
     * @return Generator<string> Yields response text chunks
     */
    public function stream(string $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $stream = \Anthropic\Laravel\Facades\Anthropic::messages()->createStreamed([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ]);

        foreach ($stream as $event) {
            if (isset($event->delta->text)) {
                yield $event->delta->text;
            }
        }
    }
}
