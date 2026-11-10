<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'browsershot' => [
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 30),
    ],

    'qpdf' => [
        'binary' => env('QPDF_BINARY', 'qpdf'),
    ],

    'codex' => [
        'binary' => env('CODEX_BINARY', 'codex'),
        'node_binary' => env('CODEX_NODE_BINARY', 'node'),
        'model' => env('CODEX_MODEL', 'gpt-6-luna'),
        'reasoning_effort' => env('CODEX_REASONING_EFFORT', 'xhigh'),
        'timeout' => (int) env('CODEX_TIMEOUT', 300),
    ],

];
