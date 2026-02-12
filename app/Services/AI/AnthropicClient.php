<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\TextBlockParam;
use Anthropic\Messages\ThinkingConfigEnabled;
use Generator;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    private const MAX_RETRIES = 2;

    private const BACKOFF_SECONDS = [1, 2];

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

        $response = $this->withRetry('chat', fn () => $this->client->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
        ));

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

        $response = $this->withRetry('chatWithThinking', fn () => $this->client->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
            thinking: ThinkingConfigEnabled::with(budgetTokens: $budgetTokens),
        ));

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
     * Retries only the initial connection — once streaming starts, chunks are not retried.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<string> Yields response text chunks
     */
    public function stream(string|array $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $stream = $this->withRetry('stream', fn () => $this->client->messages->createStream(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
        ));

        foreach ($stream as $event) {
            if ($event->type === 'content_block_delta' && $event->delta->type === 'text_delta') {
                yield $event->delta->text;
            }
        }
    }

    /**
     * Stream with extended thinking. Yields typed chunks.
     *
     * Retries only the initial connection — once streaming starts, chunks are not retried.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<array{type: string, content: string}> Yields thinking/text chunks
     */
    public function streamWithThinking(string|array $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 16000;
        $budgetTokens = $options['budget_tokens'] ?? config('anthropic.thinking.chat_budget', 8000);

        $stream = $this->withRetry('streamWithThinking', fn () => $this->client->messages->createStream(
            model: $model,
            maxTokens: $maxTokens,
            system: $systemPrompt,
            messages: $messages,
            thinking: ThinkingConfigEnabled::with(budgetTokens: $budgetTokens),
        ));

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

    /**
     * Execute a callable with exponential backoff retry on transient errors.
     *
     * Retries on 429 (rate limit), 5xx (server errors), and connection failures.
     * Does NOT retry on 400 (bad request), 401 (auth), 403, 404, etc.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws APIStatusException|APIConnectionException When all retries are exhausted
     */
    private function withRetry(string $method, callable $callback): mixed
    {
        $lastException = null;
        $maxAttempts = self::MAX_RETRIES + 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $callback();
            } catch (RateLimitException $e) {
                $lastException = $e;

                if ($attempt >= $maxAttempts) {
                    Log::warning("Anthropic {$method} rate limited (429), all {$maxAttempts} attempts exhausted", [
                        'status' => $e->status,
                    ]);

                    break;
                }

                $delay = $this->getRetryDelay($e, $attempt - 1);

                Log::warning("Anthropic {$method} rate limited (429), attempt {$attempt}/{$maxAttempts}, retrying in {$delay}s", [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay_seconds' => $delay,
                    'status' => $e->status,
                ]);

                $this->retrySleep($delay);
            } catch (InternalServerException $e) {
                $lastException = $e;

                if ($attempt >= $maxAttempts) {
                    Log::warning("Anthropic {$method} server error ({$e->status}), all {$maxAttempts} attempts exhausted", [
                        'status' => $e->status,
                    ]);

                    break;
                }

                $delay = $this->getRetryDelay($e, $attempt - 1);

                Log::warning("Anthropic {$method} server error ({$e->status}), attempt {$attempt}/{$maxAttempts}, retrying in {$delay}s", [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay_seconds' => $delay,
                    'status' => $e->status,
                ]);

                $this->retrySleep($delay);
            } catch (APIConnectionException $e) {
                $lastException = $e;

                if ($attempt >= $maxAttempts) {
                    Log::warning("Anthropic {$method} connection error, all {$maxAttempts} attempts exhausted", [
                        'message' => $e->getMessage(),
                    ]);

                    break;
                }

                $delay = self::BACKOFF_SECONDS[$attempt - 1] ?? 2;

                Log::warning("Anthropic {$method} connection error, attempt {$attempt}/{$maxAttempts}, retrying in {$delay}s", [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay_seconds' => $delay,
                    'message' => $e->getMessage(),
                ]);

                $this->retrySleep($delay);
            }
        }

        throw $lastException;
    }

    /**
     * Sleep for the specified number of seconds. Extracted for testability.
     */
    protected function retrySleep(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * Calculate retry delay, respecting Retry-After header for rate limit responses.
     */
    private function getRetryDelay(APIStatusException $exception, int $attempt): int
    {
        if ($exception->response !== null) {
            $retryAfter = $exception->response->getHeaderLine('Retry-After');

            if ($retryAfter !== '' && is_numeric($retryAfter)) {
                return min((int) $retryAfter, 10);
            }
        }

        return self::BACKOFF_SECONDS[$attempt] ?? 2;
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
