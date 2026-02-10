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

];
