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
    | AI Tier Override
    |--------------------------------------------------------------------------
    |
    | Force a specific AI tier via env. Overrides the cache/API setting.
    | Options: good, better, opus46
    |
    */
    'tier' => env('AI_TIER'),

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

    /*
    |--------------------------------------------------------------------------
    | Context Layers
    |--------------------------------------------------------------------------
    |
    | Toggle individual context layers for the QA assistant.
    | Disable heavy layers (FDA, guidelines, device data) to reduce latency.
    | Core layers (visit_data, patient_record) are always included.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Rate Limit Whitelisted IPs
    |--------------------------------------------------------------------------
    |
    | IPs excluded from AI rate limits and budget middleware.
    | Comma-separated list in env, e.g. AI_WHITELIST_IPS=1.2.3.4,5.6.7.8
    |
    */
    'whitelist_ips' => array_filter(explode(',', env('AI_WHITELIST_IPS', ''))),

    'context_layers' => [
        'health_history' => env('AI_CONTEXT_HEALTH_HISTORY', true),
        'recent_visits' => env('AI_CONTEXT_RECENT_VISITS', true),
        'device_data' => env('AI_CONTEXT_DEVICE_DATA', true),
        'medications' => env('AI_CONTEXT_MEDICATIONS', true),
        'fda_safety' => env('AI_CONTEXT_FDA_SAFETY', true),
        'library' => env('AI_CONTEXT_LIBRARY', true),
        'compaction' => env('AI_CONTEXT_COMPACTION', true),
    ],

];
