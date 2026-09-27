<?php

return [
    'apple' => [
        'bundle_id' => env('APPLE_BUNDLE_ID', 'com.bangertstudio.manover'),
    ],
    'google' => [
        'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME', 'com.bangertstudio.manover'),
        // Путь к JSON-ключу сервисного аккаунта.
        'service_account' => env('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON'),
        'webhook_secret' => env('GOOGLE_PLAY_WEBHOOK_SECRET'),
    ],
];
