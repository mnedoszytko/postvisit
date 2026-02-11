<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic API Key
    |--------------------------------------------------------------------------
    |
    | Your Anthropic API key. This is used by the anthropic-ai/laravel SDK.
    |
    */
    'api_key' => env('ANTHROPIC_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default Claude model used for AI requests when no tier override.
    | Production/demo: claude-opus-4-6
    | Tests/development: claude-sonnet-4-5-20250929 (cost optimization)
    |
    */
    'default_model' => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),

    /*
    |--------------------------------------------------------------------------
    | AI Tier
    |--------------------------------------------------------------------------
    |
    | Model and feature selection is controlled by the AiTier system:
    |   - good: Sonnet, no thinking, no caching, no guidelines
    |   - better: Opus, thinking on chat/scribe, caching, no guidelines
    |   - opus46: Opus, full thinking (including escalation), caching, guidelines
    |
    | The tier is set via API (PUT /api/v1/settings/ai-tier) and stored in cache.
    | Default: opus46 (full Opus 4.6 experience)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Prompt Caching
    |--------------------------------------------------------------------------
    |
    | Cache control for system prompts and guidelines.
    | Reduces input token costs by ~90% on repeated requests.
    | Enabled/disabled per tier — this config only sets the TTL.
    |
    */
    'cache' => [
        'ttl' => env('ANTHROPIC_CACHE_TTL', '5m'),
    ],

];
