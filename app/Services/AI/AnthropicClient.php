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
     * Send a streaming chat request and yield tokens progressively.
     *
     * Uses raw curl with curl_multi to bypass PSR-18 buffering in the SDK,
     * delivering tokens to the caller the instant they arrive from the API.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<string> Yields response text chunks
     */
    public function stream(string|array $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'stream' => true,
            'system' => $this->serializeSystemPrompt($systemPrompt),
            'messages' => $messages,
        ];

        yield from $this->rawCurlStream($body, withThinking: false);
    }

    /**
     * Stream with extended thinking. Yields typed chunks progressively.
     *
     * Uses raw curl with curl_multi to bypass PSR-18 buffering in the SDK,
     * delivering tokens to the caller the instant they arrive from the API.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @return Generator<array{type: string, content: string}> Yields thinking/text chunks
     */
    public function streamWithThinking(string|array $systemPrompt, array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 16000;
        $budgetTokens = $options['budget_tokens'] ?? config('anthropic.thinking.chat_budget', 8000);

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'stream' => true,
            'system' => $this->serializeSystemPrompt($systemPrompt),
            'messages' => $messages,
            'thinking' => ['type' => 'enabled', 'budget_tokens' => $budgetTokens],
        ];

        yield from $this->rawCurlStream($body, withThinking: true);
    }

    /**
     * True progressive streaming via raw curl + curl_multi.
     *
     * The official SDK uses PSR-18 sendRequest() which downloads the entire
     * response body before iterating. This method uses CURLOPT_WRITEFUNCTION
     * with curl_multi_exec to yield SSE events the instant they arrive.
     *
     * @return Generator Yields string (text-only) or array{type, content} (with thinking)
     */
    private function rawCurlStream(array $body, bool $withThinking): Generator
    {
        $apiKey = config('anthropic.api_key');
        $buffer = '';

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: '.$apiKey,
                'anthropic-version: 2023-06-01',
                'Accept: text/event-stream',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer) {
                $buffer .= $data;

                return strlen($data);
            },
        ]);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $running = null;
        $httpError = null;

        do {
            curl_multi_exec($mh, $running);

            // Parse complete SSE events from the buffer
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $chunk = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                foreach (explode("\n", $chunk) as $line) {
                    if (! str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $payload = substr($line, 6);
                    $decoded = json_decode($payload, true);

                    if (! $decoded) {
                        continue;
                    }

                    $type = $decoded['type'] ?? '';

                    // Check for API errors (auth, rate limit, etc.)
                    if ($type === 'error') {
                        $errorMsg = $decoded['error']['message'] ?? 'Unknown API error';
                        $httpError = $errorMsg;
                        Log::error('Anthropic streaming API error', ['error' => $decoded['error'] ?? $decoded]);

                        break 3; // Exit all loops
                    }

                    if ($type !== 'content_block_delta') {
                        continue;
                    }

                    $deltaType = $decoded['delta']['type'] ?? '';

                    if ($withThinking) {
                        if ($deltaType === 'thinking_delta') {
                            yield ['type' => 'thinking', 'content' => $decoded['delta']['thinking']];
                        } elseif ($deltaType === 'text_delta') {
                            yield ['type' => 'text', 'content' => $decoded['delta']['text']];
                        }
                    } else {
                        if ($deltaType === 'text_delta') {
                            yield $decoded['delta']['text'];
                        }
                    }
                }
            }

            if ($running) {
                curl_multi_select($mh, 0.05);
            }
        } while ($running);

        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);

        if ($curlError) {
            Log::error('Anthropic raw stream curl error', ['error' => $curlError]);

            throw new \RuntimeException("Anthropic API connection error: {$curlError}");
        }

        if ($httpError) {
            throw new \RuntimeException("Anthropic API error: {$httpError}");
        }

        if ($httpCode >= 400) {
            Log::error('Anthropic raw stream HTTP error', ['code' => $httpCode, 'body' => substr($buffer, 0, 500)]);

            throw new \RuntimeException("Anthropic API returned HTTP {$httpCode}");
        }
    }

    /**
     * Chat with tool use support. Handles the agentic loop:
     * AI responds -> may call tools -> we execute -> send results back -> AI continues.
     *
     * @param  string|array  $systemPrompt  String or array of TextBlockParam
     * @param  array  $messages  Conversation messages
     * @param  array  $tools  Tool definitions (Anthropic format)
     * @param  callable(string, array): array  $toolExecutor  Executes a tool call
     * @param  (callable(string, array): void)|null  $onToolUse  Callback for UI updates when a tool is called
     * @param  array  $options  Model options (model, max_tokens, budget_tokens)
     * @return array{text: string, thinking: string, tools_used: array}
     */
    public function chatWithTools(
        string|array $systemPrompt,
        array $messages,
        array $tools,
        callable $toolExecutor,
        ?callable $onToolUse = null,
        array $options = [],
    ): array {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 16000;
        $budgetTokens = $options['budget_tokens'] ?? 8000;
        $maxIterations = 5;
        $toolsUsed = [];
        $thinking = '';

        for ($i = 0; $i < $maxIterations; $i++) {
            $body = [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $this->serializeSystemPrompt($systemPrompt),
                'messages' => $messages,
                'tools' => $tools,
            ];

            if ($budgetTokens > 0) {
                $body['thinking'] = ['type' => 'enabled', 'budget_tokens' => $budgetTokens];
            }

            $response = $this->rawCurlRequest($body);

            $stopReason = $response['stop_reason'] ?? 'end_turn';

            if ($stopReason === 'tool_use') {
                $assistantContent = $response['content'] ?? [];
                $messages[] = ['role' => 'assistant', 'content' => $assistantContent];

                // Collect thinking from this iteration
                foreach ($assistantContent as $block) {
                    if (($block['type'] ?? '') === 'thinking') {
                        $thinking .= $block['thinking'] ?? '';
                    }
                }

                $toolResultBlocks = [];
                foreach ($assistantContent as $block) {
                    if (($block['type'] ?? '') !== 'tool_use') {
                        continue;
                    }

                    $name = $block['name'];
                    $input = $block['input'] ?? [];
                    $toolUseId = $block['id'];

                    if ($onToolUse) {
                        $onToolUse($name, $input);
                    }

                    $result = $toolExecutor($name, $input);
                    $toolsUsed[] = ['name' => $name, 'input' => $input];

                    $toolResultBlocks[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $toolUseId,
                        'content' => json_encode($result),
                    ];
                }

                $messages[] = ['role' => 'user', 'content' => $toolResultBlocks];

                continue;
            }

            // stop_reason === 'end_turn' — extract final text
            $text = '';
            foreach ($response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'thinking') {
                    $thinking .= $block['thinking'] ?? '';
                }
                if (($block['type'] ?? '') === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }

            $this->logToolUseUsage($model, $response['usage'] ?? null, $toolsUsed);

            return ['text' => $text, 'thinking' => $thinking, 'tools_used' => $toolsUsed];
        }

        throw new \RuntimeException('Tool use loop exceeded max iterations ('.$maxIterations.')');
    }

    /**
     * Non-streaming API call via raw curl with retry on 429/5xx.
     * Used for tool use loop.
     *
     * @return array<string, mixed>
     */
    private function rawCurlRequest(array $body): array
    {
        $apiKey = config('anthropic.api_key');
        $maxAttempts = self::MAX_RETRIES + 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: '.$apiKey,
                    'anthropic-version: 2023-06-01',
                ],
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                if ($attempt < $maxAttempts) {
                    $delay = self::BACKOFF_SECONDS[$attempt - 1] ?? 2;
                    Log::warning("Anthropic tool use curl error, attempt {$attempt}/{$maxAttempts}, retrying in {$delay}s", ['error' => $curlError]);
                    $this->retrySleep($delay);

                    continue;
                }

                Log::error('Anthropic tool use curl error, all attempts exhausted', ['error' => $curlError]);

                throw new \RuntimeException("Anthropic API connection error: {$curlError}");
            }

            // Retry on 429 (rate limit) or 5xx (server error)
            if ($httpCode === 429 || $httpCode >= 500) {
                if ($attempt < $maxAttempts) {
                    $delay = self::BACKOFF_SECONDS[$attempt - 1] ?? 2;
                    Log::warning("Anthropic tool use HTTP {$httpCode}, attempt {$attempt}/{$maxAttempts}, retrying in {$delay}s");
                    $this->retrySleep($delay);

                    continue;
                }
            }

            if ($httpCode >= 400) {
                Log::error('Anthropic tool use API error', [
                    'code' => $httpCode,
                    'body' => substr($response, 0, 500),
                ]);

                throw new \RuntimeException("Anthropic API returned HTTP {$httpCode}");
            }

            $decoded = json_decode($response, true);

            if (! is_array($decoded)) {
                throw new \RuntimeException('Anthropic API returned invalid JSON');
            }

            return $decoded;
        }

        throw new \RuntimeException('Anthropic API: all retry attempts exhausted');
    }

    /**
     * Log usage for tool use calls.
     */
    private function logToolUseUsage(string $model, ?array $usage, array $toolsUsed): void
    {
        $data = [
            'model' => $model,
            'input_tokens' => $usage['input_tokens'] ?? null,
            'output_tokens' => $usage['output_tokens'] ?? null,
            'tools_used' => count($toolsUsed),
            'tool_names' => array_column($toolsUsed, 'name'),
        ];

        if (isset($usage['cache_creation_input_tokens'])) {
            $data['cache_creation_input_tokens'] = $usage['cache_creation_input_tokens'];
        }
        if (isset($usage['cache_read_input_tokens'])) {
            $data['cache_read_input_tokens'] = $usage['cache_read_input_tokens'];
        }

        Log::channel('ai')->info('Anthropic chatWithTools request', $data);
    }

    /**
     * Convert system prompt to API-compatible format.
     *
     * Handles both plain strings and TextBlockParam arrays (for cache control).
     */
    private function serializeSystemPrompt(string|array $systemPrompt): string|array
    {
        if (is_string($systemPrompt)) {
            return $systemPrompt;
        }

        // Array of TextBlockParam objects — serialize to plain arrays
        return array_map(fn ($block) => $block instanceof \JsonSerializable ? $block->jsonSerialize() : $block, $systemPrompt);
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
