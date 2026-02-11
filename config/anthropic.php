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
    | The default Claude model used for AI requests.
    | Production/demo: claude-opus-4-6
    | Tests/development: claude-sonnet-4-5-20250929 (cost optimization)
    |
    */
    'default_model' => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),

    /*
    |--------------------------------------------------------------------------
    | Escalation Model
    |--------------------------------------------------------------------------
    |
    | A faster/cheaper model used for escalation detection checks.
    |
    */
    'escalation_model' => env('ANTHROPIC_ESCALATION_MODEL', 'claude-sonnet-4-5-20250929'),

    /*
    |--------------------------------------------------------------------------
    | Extended Thinking
    |--------------------------------------------------------------------------
    |
    | Budget tokens for extended thinking in different AI subsystems.
    | budget_tokens must be >= 1024 and < max_tokens for the request.
    |
    */
    'thinking' => [
        'scribe_budget' => (int) env('ANTHROPIC_THINKING_SCRIBE_BUDGET', 10000),
        'chat_budget' => (int) env('ANTHROPIC_THINKING_CHAT_BUDGET', 8000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Caching
    |--------------------------------------------------------------------------
    |
    | Cache control for system prompts and guidelines.
    | Reduces input token costs by ~90% on repeated requests.
    |
    */
    'cache' => [
        'enabled' => env('ANTHROPIC_CACHE_ENABLED', true),
        'ttl' => env('ANTHROPIC_CACHE_TTL', '5m'),
    ],

];
