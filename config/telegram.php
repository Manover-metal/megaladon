<?php

// События в Telegram-группу с темами. Пустой токен — отправка выключена.
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'admin_url' => rtrim(env('ADMIN_URL', 'https://admin.manovermetal.kz'), '/'),

    // Ключ темы => [название темы, message_thread_id]. ID выдаёт `php artisan telegram:topics`.
    'topics' => [
        'user' => ['Регистрация пользователей', env('TELEGRAM_TOPIC_USER')],
        'store' => ['Регистрация магазинов', env('TELEGRAM_TOPIC_STORE')],
        'executor' => ['Регистрация исполнителей', env('TELEGRAM_TOPIC_EXECUTOR')],
        'order' => ['Заказы', env('TELEGRAM_TOPIC_ORDER')],
        'advert' => ['Объявления', env('TELEGRAM_TOPIC_ADVERT')],
        'service' => ['Услуги', env('TELEGRAM_TOPIC_SERVICE')],
        'user_block' => ['Блокировки пользователей', env('TELEGRAM_TOPIC_USER_BLOCK')],
    ],
];
