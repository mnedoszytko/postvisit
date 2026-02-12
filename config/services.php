<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stt' => [
        'provider' => env('STT_PROVIDER', 'whisper'),
        'whisper_api_key' => env('OPENAI_API_KEY'),
        'whisper_api_url' => env('WHISPER_API_URL', 'https://api.openai.com/v1/audio/transcriptions'),
        'whisper_model' => env('WHISPER_MODEL', 'whisper-1'),
    ],

    'pmc' => [
        'cache_ttl' => env('PMC_CACHE_TTL', 86400),
        'base_url' => 'https://www.ncbi.nlm.nih.gov/research/bionlp/RESTful/pmcoa.cgi',
        'eutils_url' => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils',
    ],

    'openfda' => [
        'timeout' => env('OPENFDA_TIMEOUT', 5),
    ],

];
