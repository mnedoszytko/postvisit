<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\TextBlockParam;
use Anthropic\Messages\ThinkingConfigEnabled;
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
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam for cache control
     */
    public function chat(string|array $systemPrompt, array $messages, array $options = []): string
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

        $this->logUsage('chat', $model, $response->usage);

        return $text;
    }

    /**
     * Send a chat request with extended thinking enabled.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return array{text: string, thinking: string, usage: array}
     */
    public function chatWithThinking(string|array $systemPrompt, array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 16000;
        $budgetTokens = $options['budget_tokens'] ?? config('anthropic.thinking.scribe_budget', 10000);

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
            thinking: ThinkingConfigEnabled::with(budgetTokens: $budgetTokens),
        );

        $text = '';
        $thinking = '';
        foreach ($response->content as $block) {
            if ($block->type === 'thinking') {
                $thinking .= $block->thinking;
            } elseif ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $this->logUsage('chatWithThinking', $model, $response->usage);

        return [
            'text' => $text,
            'thinking' => $thinking,
            'usage' => $this->extractUsage($response->usage),
        ];
    }

    /**
     * Send a streaming chat request and yield tokens.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<string> Yields response text chunks
     */
    public function stream(string|array $systemPrompt, array $messages, array $options = []): Generator
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

    /**
     * Stream with extended thinking. Yields typed chunks.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<array{type: string, content: string}> Yields thinking/text chunks
     */
    public function streamWithThinking(string|array $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 16000;
        $budgetTokens = $options['budget_tokens'] ?? config('anthropic.thinking.chat_budget', 8000);

        $stream = $this->client->messages->createStream(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
            thinking: ThinkingConfigEnabled::with(budgetTokens: $budgetTokens),
        );

        foreach ($stream as $event) {
            if ($event->type === 'content_block_delta') {
                if ($event->delta->type === 'thinking_delta') {
                    yield ['type' => 'thinking', 'content' => $event->delta->thinking];
                } elseif ($event->delta->type === 'text_delta') {
                    yield ['type' => 'text', 'content' => $event->delta->text];
                }
            }
        }
    }

    /**
     * Build a cacheable system prompt block.
     *
     * @return TextBlockParam[]
     */
    public static function withCacheControl(string $text, string $ttl = '5m'): array
    {
        return [
            TextBlockParam::with(
                text: $text,
                cacheControl: CacheControlEphemeral::with(ttl: $ttl),
            ),
        ];
    }

    /**
     * Extract and parse JSON from an AI response that may contain markdown fences.
     *
     * @param  array<string, mixed>  $default  Fallback if parsing fails
     * @return array<string, mixed>
     */
    public static function parseJsonOutput(string $response, array $default = []): array
    {
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $response, $matches)) {
            $response = $matches[1];
        }

        $decoded = json_decode(trim($response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('AI JSON parse failed', [
                'error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 200),
            ]);

            return $default;
        }

        return $decoded;
    }

    private function logUsage(string $method, string $model, mixed $usage): void
    {
        $data = [
            'model' => $model,
            'input_tokens' => $usage->inputTokens ?? null,
            'output_tokens' => $usage->outputTokens ?? null,
        ];

        // Log cache stats if available
        if (isset($usage->cacheCreationInputTokens)) {
            $data['cache_creation_input_tokens'] = $usage->cacheCreationInputTokens;
        }
        if (isset($usage->cacheReadInputTokens)) {
            $data['cache_read_input_tokens'] = $usage->cacheReadInputTokens;
        }

        Log::channel('ai')->info("Anthropic {$method} request", $data);
    }

    private function extractUsage(mixed $usage): array
    {
        $data = [
            'input_tokens' => $usage->inputTokens ?? 0,
            'output_tokens' => $usage->outputTokens ?? 0,
        ];

        if (isset($usage->cacheCreationInputTokens)) {
            $data['cache_creation_input_tokens'] = $usage->cacheCreationInputTokens;
        }
        if (isset($usage->cacheReadInputTokens)) {
            $data['cache_read_input_tokens'] = $usage->cacheReadInputTokens;
        }

        return $data;
    }
}
