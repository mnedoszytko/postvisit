<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight Slack webhook alerts for abuse monitoring.
 *
 * Sends alerts with cooldown to avoid spamming the channel.
 */
class SlackAlertService
{
    /** Minimum seconds between alerts of the same type. */
    private const COOLDOWN_SECONDS = 300;

    public static function send(string $message, string $alertType = 'general'): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (! $webhookUrl) {
            return;
        }

        // Cooldown: don't spam the same alert type
        $cooldownKey = "slack_alert_cooldown:{$alertType}";
        if (Cache::has($cooldownKey)) {
            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'text' => $message,
            ]);

            Cache::put($cooldownKey, true, self::COOLDOWN_SECONDS);
        } catch (\Throwable $e) {
            Log::warning('Slack alert failed: '.$e->getMessage());
        }
    }

    public static function budgetWarning(int $used, int $limit, int $percent): void
    {
        self::send(
            ":warning: *PostVisit AI Budget* — {$percent}% used ({$used}/{$limit} calls today)",
            "budget_{$percent}"
        );
    }

    public static function budgetExhausted(int $limit): void
    {
        self::send(
            ":rotating_light: *PostVisit AI Budget EXHAUSTED* — {$limit}/{$limit} calls reached. All AI endpoints returning 429.",
            'budget_100'
        );
    }

    public static function userBudgetExhausted(string $identifier, int $limit): void
    {
        self::send(
            ":no_entry: *User budget exhausted* — `{$identifier}` hit {$limit} AI calls today",
            "user_budget_{$identifier}"
        );
    }

    public static function rateLimitHit(string $ip, string $endpoint): void
    {
        self::send(
            ":shield: *Rate limit triggered* — IP `{$ip}` on `{$endpoint}`",
            "ratelimit_{$ip}"
        );
    }

    public static function demoAbuse(string $ip, string $endpoint): void
    {
        self::send(
            ":robot_face: *Demo endpoint spam* — IP `{$ip}` hitting `{$endpoint}` repeatedly",
            "demo_abuse_{$ip}"
        );
    }

    public static function demoStarted(string $scenario, string $ip): void
    {
        // Unique key per scenario+IP+minute — shows every switch, dedupes only rapid double-clicks
        $key = "demo_started_{$ip}_{$scenario}_".now()->format('Y-m-d_H:i');
        self::send(
            ":eyes: *Demo started* — `{$scenario}` scenario from IP `{$ip}`",
            $key
        );
    }

    public static function resetAttempt(string $ip): void
    {
        self::send(
            ":rotating_light: *BLOCKED: Demo reset attempt* — IP `{$ip}` tried to wipe the database via /demo/reset",
            "reset_{$ip}"
        );
    }
}
