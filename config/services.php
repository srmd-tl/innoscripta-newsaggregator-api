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

    'newsapi' => [
        'key' => env('NEWSAPI_KEY'),
        'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org/v2'),
        'enabled' => env('NEWSAPI_ENABLED', true),
    ],

    'guardian' => [
        'key' => env('GUARDIAN_KEY'),
        'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
        'enabled' => env('GUARDIAN_ENABLED', true),
    ],

    'nyt' => [
        'key' => env('NYT_KEY'),
        'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com/svc/search/v2'),
        'enabled' => env('NYT_ENABLED', true),
    ],

];
