<?php

// Шлюз SMSC.kz — https://smsc.kz/api/http/
return [
    // API-ключ из кабинета. Если задан — заменяет пару логин+пароль.
    'api_key' => env('SMSC_API_KEY'),

    // Логин клиента SMSC либо логин API-подпользователя.
    'login' => env('SMSC_LOGIN', ''),

    // Пароль от кабинета. Может быть и MD5-хешем — SMSC принимает оба варианта,
    // флаг ниже нужен только для проверки формата при старте отправки.
    'password' => env('SMSC_PASSWORD', ''),
    'password_is_md5' => (bool) env('SMSC_PASSWORD_IS_MD5', false),

    // Имя отправителя, зарегистрированное в кабинете. Пусто — общий номер SMSC.
    'sender' => env('SMSC_SENDER'),

    'url' => rtrim(env('SMSC_URL', 'https://smsc.kz/sys/'), '/') . '/',
    'charset' => env('SMSC_CHARSET', 'utf-8'),
    'translit' => (int) env('SMSC_TRANSLIT', 0),
    'timeout' => (int) env('SMSC_TIMEOUT', 10),

    // true — SMS наружу не уходят, пользователю отдаётся статический debug_code.
    'no_send_sms' => (bool) env('NO_SEND_SMS', true),
    'debug_code' => (string) env('SMS_DEBUG_CODE', '101010'),

    // Длина случайного кода в боевом режиме, зажимается в диапазон 4-8.
    'code_length' => (int) env('SMS_CODE_LENGTH', 6),
];
