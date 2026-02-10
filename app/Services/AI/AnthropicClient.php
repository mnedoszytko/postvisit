<?php

namespace App\Services\AI;

use Anthropic\Client;
use Generator;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    private Client $client;
    private string $defaultModel;

    public function __construct()
    {
        $this->client = new Client(
            apiKey: config('anthropic.api_key'),
        );
        $this->defaultModel = config('anthropic.default_model', 'claude-opus-4-6');
    }

    /**
     * Send a chat request and return the full response.
     */
    public function chat(string $systemPrompt, array $messages, array $options = []): string
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
        );

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

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
     * @return Generator<string> Yields response text chunks
     */
    public function stream(string $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $stream = $this->client->messages->createStream(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
        );

        foreach ($stream as $event) {
            if ($event->type === 'content_block_delta' && $event->delta->type === 'text_delta') {
                yield $event->delta->text;
            }
        }
    }
}
