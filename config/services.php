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

    'kansor' => [
        'api_url' => env('KANSOR_API_URL'),
        'admin_email' => env('KANSOR_ADMIN_EMAIL'),
        'admin_password' => env('KANSOR_ADMIN_PASSWORD'),
        'legacy_spreadsheet_id' => env('KANSOR_LEGACY_SPREADSHEET_ID'),
        'timeout' => (int) env('KANSOR_TIMEOUT', 20),
        'connect_timeout' => (int) env('KANSOR_CONNECT_TIMEOUT', 10),
        'ca_bundle' => env('KANSOR_CA_BUNDLE'),
        'token_cache_key' => env('KANSOR_TOKEN_CACHE_KEY', 'kansor.service_account.token'),
        'device_label' => env('KANSOR_DEVICE_LABEL', 'KanSor Desktop'),
        'offline_login_days' => (int) env('KANSOR_OFFLINE_LOGIN_DAYS', 30),
        'offline_login_days_max' => (int) env('KANSOR_OFFLINE_LOGIN_DAYS_MAX', 30),
        'sync_interval_seconds' => (int) env('KANSOR_SYNC_INTERVAL_SECONDS', 60),
    ],

];

