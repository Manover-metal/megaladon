<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Уведомления о событиях в темы Telegram-группы. Как и SmscService, не бросает:
 * лежащий Telegram не должен ронять регистрацию или создание заказа.
 */
class TelegramService
{
    public static function call(string $method, array $params): ?array
    {
        $token = config('telegram.bot_token');
        if (empty($token)) {
            Log::error(__METHOD__ . ": $method — TELEGRAM_BOT_TOKEN не задан");
            return null;
        }

        try {
            $body = Http::timeout(5)->post("https://api.telegram.org/bot$token/$method", $params)->json();
        } catch (Throwable $e) {
            Log::error(__METHOD__ . ": $method — " . $e->getMessage());
            return null;
        }

        if (!($body['ok'] ?? false)) {
            Log::error(__METHOD__ . ": $method — " . ($body['description'] ?? 'нет ответа'));
            return null;
        }

        return $body['result'];
    }

    // Отправка после ответа клиенту, чтобы при QUEUE_CONNECTION=sync запрос не ждал Telegram.
    public static function notify(string $topic, string $text, string $adminPath): void
    {
        if (empty(config('telegram.bot_token'))) {
            return;
        }

        $params = [
            'chat_id' => config('telegram.chat_id'),
            'text' => $text . "\n\n" . config('telegram.admin_url') . '/' . $adminPath,
            'disable_web_page_preview' => true,
        ];

        $threadId = config("telegram.topics.$topic.1");
        if (!empty($threadId)) {
            $params['message_thread_id'] = (int) $threadId;
        }

        dispatch(fn () => self::call('sendMessage', $params))->afterResponse();
    }
}
