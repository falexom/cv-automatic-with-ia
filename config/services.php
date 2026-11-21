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

    'pdftotext' => [
        'binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),
        'timeout' => (int) env('PDFTOTEXT_TIMEOUT', 30),
    ],

    'certification_import' => [
        'max_text_chars' => (int) env('CERTIFICATION_IMPORT_MAX_TEXT_CHARS', 60000),
        'max_pages' => (int) env('CERTIFICATION_IMPORT_MAX_PAGES', 30),
        'max_page_chars' => (int) env('CERTIFICATION_IMPORT_MAX_PAGE_CHARS', 20000),
    ],

    'ocr' => [
        'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
        'pdftoppm_binary' => env('PDFTOPPM_BINARY', 'pdftoppm'),
        'pdfimages_binary' => env('PDFIMAGES_BINARY', 'pdfimages'),
        'languages' => env('OCR_LANGUAGES', 'spa+eng'),
        'dpi' => (int) env('OCR_DPI', 300),
        'render_timeout' => (int) env('OCR_RENDER_TIMEOUT', 60),
        'timeout' => (int) env('OCR_TIMEOUT', 120),
    ],

    'codex' => [
        'binary' => env('CODEX_BINARY', 'codex'),
        'node_binary' => env('CODEX_NODE_BINARY', 'node'),
        'model' => env('CODEX_MODEL', 'gpt-6-luna'),
        'reasoning_effort' => env('CODEX_REASONING_EFFORT', 'xhigh'),
        'timeout' => (int) env('CODEX_TIMEOUT', 300),
    ],

];
