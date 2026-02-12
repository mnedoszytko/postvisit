<?php

namespace Tests\Unit;

use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\AI\AnthropicClient;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Testable subclass that skips real sleep during tests.
 */
class TestableAnthropicClient extends AnthropicClient
{
    /** @var int[] Recorded sleep durations for assertions */
    public array $sleepLog = [];

    protected function retrySleep(int $seconds): void
    {
        $this->sleepLog[] = $seconds;
    }
}

class AnthropicClientRetryTest extends TestCase
{
    private TestableAnthropicClient $client;

    private ReflectionMethod $withRetry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new TestableAnthropicClient;
        $this->withRetry = new ReflectionMethod(AnthropicClient::class, 'withRetry');
        $this->withRetry->setAccessible(true);
    }

    public function test_returns_result_on_first_success(): void
    {
        $result = $this->withRetry->invoke($this->client, 'test', fn () => 'success');

        $this->assertEquals('success', $result);
        $this->assertEmpty($this->client->sleepLog, 'Should not sleep on first success');
    }

    public function test_retries_on_rate_limit_then_succeeds(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeRateLimitException();
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
        $this->assertEquals([1], $this->client->sleepLog, 'Should sleep 1s after first failure');
    }

    public function test_retries_on_internal_server_error_then_succeeds(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeInternalServerException(500);
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
        $this->assertEquals([1], $this->client->sleepLog);
    }

    public function test_retries_on_502_gateway_error(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeInternalServerException(502);
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
    }

    public function test_retries_on_503_service_unavailable(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeInternalServerException(503);
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
    }

    public function test_retries_on_529_overloaded(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeInternalServerException(529);
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
    }

    public function test_retries_on_connection_error_then_succeeds(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeConnectionException();
            }

            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(2, $attempt);
        $this->assertEquals([1], $this->client->sleepLog);
    }

    public function test_exhausts_all_retries_and_throws(): void
    {
        Log::shouldReceive('warning')->times(3);

        $attempt = 0;

        $this->expectException(RateLimitException::class);

        $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            throw $this->makeRateLimitException();
        });
    }

    public function test_exhausts_all_retries_uses_exponential_backoff(): void
    {
        Log::shouldReceive('warning')->times(3);

        $attempt = 0;

        try {
            $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
                $attempt++;
                throw $this->makeRateLimitException();
            });
        } catch (RateLimitException) {
            // Expected
        }

        $this->assertEquals(3, $attempt, 'Should attempt 3 times total (1 initial + 2 retries)');
        $this->assertEquals([1, 2], $this->client->sleepLog, 'Should sleep with exponential backoff');
    }

    public function test_does_not_retry_on_bad_request(): void
    {
        $attempt = 0;

        try {
            $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
                $attempt++;
                throw $this->makeBadRequestException();
            });
            $this->fail('Expected BadRequestException was not thrown');
        } catch (BadRequestException) {
            // Expected
        }

        $this->assertEquals(1, $attempt, 'Should not retry on 400 Bad Request');
        $this->assertEmpty($this->client->sleepLog, 'Should not sleep for non-retryable errors');
    }

    public function test_recovers_after_two_failures(): void
    {
        Log::shouldReceive('warning')->twice();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt <= 2) {
                throw $this->makeInternalServerException(502);
            }

            return 'final-success';
        });

        $this->assertEquals('final-success', $result);
        $this->assertEquals(3, $attempt);
        $this->assertEquals([1, 2], $this->client->sleepLog, 'Should use exponential backoff: 1s then 2s');
    }

    public function test_respects_retry_after_header(): void
    {
        Log::shouldReceive('warning')->once();

        $attempt = 0;
        $result = $this->withRetry->invoke($this->client, 'test', function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw $this->makeRateLimitException(retryAfter: '3');
            }

            return 'ok';
        });

        $this->assertEquals('ok', $result);
        $this->assertEquals([3], $this->client->sleepLog, 'Should use Retry-After header value');
    }

    public function test_caps_retry_after_at_ten_seconds(): void
    {
        $getRetryDelay = new ReflectionMethod(AnthropicClient::class, 'getRetryDelay');
        $getRetryDelay->setAccessible(true);

        $exception = $this->makeRateLimitException(retryAfter: '30');
        $delay = $getRetryDelay->invoke($this->client, $exception, 0);

        $this->assertEquals(10, $delay, 'Retry-After should be capped at 10 seconds');
    }

    public function test_uses_default_backoff_when_no_retry_after_header(): void
    {
        $getRetryDelay = new ReflectionMethod(AnthropicClient::class, 'getRetryDelay');
        $getRetryDelay->setAccessible(true);

        $exception = $this->makeRateLimitException();

        $this->assertEquals(1, $getRetryDelay->invoke($this->client, $exception, 0));
        $this->assertEquals(2, $getRetryDelay->invoke($this->client, $exception, 1));
    }

    private function makeRateLimitException(?string $retryAfter = null): RateLimitException
    {
        $request = new Psr7Request('POST', 'https://api.anthropic.com/v1/messages');
        $headers = $retryAfter !== null ? ['Retry-After' => $retryAfter] : [];
        $response = new Psr7Response(429, $headers, json_encode([
            'type' => 'error',
            'error' => ['type' => 'rate_limit_error', 'message' => 'Rate limited'],
        ]));

        return new RateLimitException($request, $response);
    }

    private function makeInternalServerException(int $status = 500): InternalServerException
    {
        $request = new Psr7Request('POST', 'https://api.anthropic.com/v1/messages');
        $response = new Psr7Response($status, [], json_encode([
            'type' => 'error',
            'error' => ['type' => 'api_error', 'message' => 'Internal server error'],
        ]));

        return new InternalServerException($request, $response);
    }

    private function makeConnectionException(): APIConnectionException
    {
        $request = new Psr7Request('POST', 'https://api.anthropic.com/v1/messages');

        return new APIConnectionException(request: $request, message: 'Connection refused');
    }

    private function makeBadRequestException(): BadRequestException
    {
        $request = new Psr7Request('POST', 'https://api.anthropic.com/v1/messages');
        $response = new Psr7Response(400, [], json_encode([
            'type' => 'error',
            'error' => ['type' => 'invalid_request_error', 'message' => 'Bad request'],
        ]));

        return new BadRequestException($request, $response);
    }
}
