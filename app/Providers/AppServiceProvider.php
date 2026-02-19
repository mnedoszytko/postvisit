<?php

namespace App\Providers;

use App\Services\Stt\SpeechToTextProvider;
use App\Services\Stt\WhisperProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SpeechToTextProvider::class, function () {
            return match (config('services.stt.provider')) {
                'whisper' => new WhisperProvider,
                default => new WhisperProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // AI endpoints: post-judging public access — tight limits
        RateLimiter::for('ai', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(5)->by('ai-min:'.$key),
                Limit::perHour(15)->by('ai-hour:'.$key),
            ];
        });

        // AI expensive endpoints (education, inquire): post-judging limits
        RateLimiter::for('ai-expensive', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(1)->by('ai-exp-min:'.$key),
                Limit::perHour(5)->by('ai-exp-hour:'.$key),
            ];
        });

        // Demo endpoints: 10 requests per hour per IP
        RateLimiter::for('demo', function (Request $request) {
            return Limit::perHour(10)->by('demo:'.$request->ip())
                ->response(function () use ($request) {
                    \App\Services\SlackAlertService::demoAbuse(
                        $request->ip(),
                        $request->path()
                    );

                    return response()->json([
                        'error' => ['message' => 'Demo rate limit reached. Please wait before trying again.'],
                    ], 429);
                });
        });
    }
}
