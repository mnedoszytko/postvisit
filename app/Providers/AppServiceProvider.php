<?php

namespace App\Providers;

use App\Services\Stt\SpeechToTextProvider;
use App\Services\Stt\WhisperProvider;
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
                'whisper' => new WhisperProvider(),
                default => new WhisperProvider(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
