<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Разово: создаёт темы в группе TELEGRAM_CHAT_ID и печатает строки для .env.
Artisan::command('telegram:topics', function () {
    foreach (config('telegram.topics') as $key => [$name]) {
        $topic = \App\Services\TelegramService::call('createForumTopic', ['chat_id' => config('telegram.chat_id'), 'name' => $name]);
        $topic
            ? $this->line('TELEGRAM_TOPIC_' . strtoupper($key) . '=' . $topic['message_thread_id'])
            : $this->error("$name — не создана, см. storage/logs/laravel.log");
    }
})->purpose('Создать темы Telegram для уведомлений');
