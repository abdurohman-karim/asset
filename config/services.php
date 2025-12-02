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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],

    'groq' => [
        'key'   => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL'),
        'base_uri' => env('GROQ_BASE_URI', 'https://api.groq.com/openai/v1'),
    ],

    'deepseek' => [
        'key'   => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'base_uri' => env('DEEPSEEK_BASE_URI', 'https://api.deepseek.com'),
    ],

    'openrouter' => [
        'url' => env('OPENROUTER_URL'),
        'model' => env('OPENROUTER_MODEL'),
        'key' => env('OPENROUTER_API_KEY'),
    ],

    'models_llm' => [
        [
            'name'  => 'openai',
            'limit' => 20,
        ],
        [
            'name'  => 'groq',
            'limit' => 20,
        ],
        [
            'name'  => 'deepseek',
            'limit' => 0,
        ],
        [
            'name'  => 'openrouter',
            'limit' => 20,
        ],
    ],
];
