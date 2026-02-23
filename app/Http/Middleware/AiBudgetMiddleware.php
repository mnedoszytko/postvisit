<?php

namespace App\Http\Middleware;

use App\Services\SlackAlertService;
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
    /** Maximum AI API calls per calendar day (UTC). Post-judging public access mode. */
    private const DAILY_LIMIT = 200;

    /** Maximum AI API calls per single user/session per day. Post-judging public access mode. */
    private const PER_USER_DAILY_LIMIT = 15;

    /** Alert at these percentage thresholds. */
    private const ALERT_THRESHOLDS = [50, 80, 95];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip budget limits on local development or whitelisted IPs
        if (app()->environment('local') || in_array($request->ip(), config('anthropic.whitelist_ips', []), true)) {
            return $next($request);
        }

        $dailyKey = 'ai_budget:'.now()->format('Y-m-d');
        $currentCount = (int) Cache::get($dailyKey, 0);

        if ($currentCount >= self::DAILY_LIMIT) {
            SlackAlertService::budgetExhausted(self::DAILY_LIMIT);

            return response()->json([
                'error' => [
                    'message' => 'AI responses are temporarily limited due to heavy traffic. The limit resets every hour — please try again soon.',
                    'type' => 'budget_exceeded',
                ],
            ], 429);
        }

        // Per-user daily limit
        $userIdentifier = $request->user()?->id ?: $request->ip();
        $userKey = 'ai_budget_user:'.$userIdentifier.':'.now()->format('Y-m-d');
        $userCount = (int) Cache::get($userKey, 0);

        if ($userCount >= self::PER_USER_DAILY_LIMIT) {
            SlackAlertService::userBudgetExhausted((string) $userIdentifier, self::PER_USER_DAILY_LIMIT);

            return response()->json([
                'error' => [
                    'message' => 'You have reached the AI demo limit. The limit resets every hour — please try again soon.',
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

            // Check alert thresholds after increment
            $newCount = $currentCount + 1;
            foreach (self::ALERT_THRESHOLDS as $threshold) {
                $triggerAt = (int) ceil(self::DAILY_LIMIT * $threshold / 100);
                if ($newCount === $triggerAt) {
                    SlackAlertService::budgetWarning($newCount, self::DAILY_LIMIT, $threshold);
                }
            }
        }

        // Add budget headers so frontend can show remaining
        $response->headers->set('X-AI-Budget-Remaining', (string) max(0, self::DAILY_LIMIT - $currentCount - 1));
        $response->headers->set('X-AI-User-Remaining', (string) max(0, self::PER_USER_DAILY_LIMIT - $userCount - 1));

        return $response;
    }
}
