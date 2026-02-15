<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global daily AI budget guard.
 *
 * Tracks total AI API calls per day and returns 429 when the budget
 * is exhausted. Protects against runaway token consumption when
 * the demo is exposed to the public internet.
 */
class AiBudgetMiddleware
{
    /** Maximum AI API calls per calendar day (UTC). */
    private const DAILY_LIMIT = 500;

    /** Maximum AI API calls per single user/session per day. */
    private const PER_USER_DAILY_LIMIT = 50;

    public function handle(Request $request, Closure $next): Response
    {
        $dailyKey = 'ai_budget:'.now()->format('Y-m-d');
        $currentCount = (int) Cache::get($dailyKey, 0);

        if ($currentCount >= self::DAILY_LIMIT) {
            return response()->json([
                'error' => [
                    'message' => 'AI demo budget reached for today. The demo resets daily — please try again tomorrow.',
                    'type' => 'budget_exceeded',
                ],
            ], 429);
        }

        // Per-user daily limit
        $userKey = 'ai_budget_user:'.($request->user()?->id ?: $request->ip()).':'.now()->format('Y-m-d');
        $userCount = (int) Cache::get($userKey, 0);

        if ($userCount >= self::PER_USER_DAILY_LIMIT) {
            return response()->json([
                'error' => [
                    'message' => 'You have reached the daily AI demo limit. Please try again tomorrow.',
                    'type' => 'user_budget_exceeded',
                ],
            ], 429);
        }

        $response = $next($request);

        // Only count successful requests (not validation errors, auth failures, etc.)
        if ($response->getStatusCode() < 400) {
            Cache::increment($dailyKey);
            Cache::put($dailyKey, (int) Cache::get($dailyKey, 0), now()->endOfDay());

            Cache::increment($userKey);
            Cache::put($userKey, (int) Cache::get($userKey, 0), now()->endOfDay());
        }

        // Add budget headers so frontend can show remaining
        $response->headers->set('X-AI-Budget-Remaining', (string) max(0, self::DAILY_LIMIT - $currentCount - 1));
        $response->headers->set('X-AI-User-Remaining', (string) max(0, self::PER_USER_DAILY_LIMIT - $userCount - 1));

        return $response;
    }
}
