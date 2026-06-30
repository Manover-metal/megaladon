<?php

return [
    'login' => env('SMSC_LOGIN', 'login'),
    'password' => env('SMSC_PASSWORD', 'password'),
    'url' => env('SMSC_URL'),
    'no_send_sms' => env('NO_SEND_SMS', true),
];