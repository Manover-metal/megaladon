<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

/**
 * Универсальный push через FCM. Отправляется любому Notifiable, у которого
 * есть device_token и включён флаг push_notifications.
 *
 * ShouldQueue: с QUEUE_CONNECTION=sync выполняется inline; при настроенной
 * очереди отправка уходит в воркер и не блокирует запрос.
 */
class FcmPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $title;
    private string $body;
    private array $data;

    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Единая точка проверки: уважаем пользовательский флаг и наличие токена.
     */
    public function via($notifiable): array
    {
        if (!$notifiable->push_notifications || empty($notifiable->device_token)) {
            return [];
        }

        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->setData($this->stringifyData())
            ->setNotification(
                FcmNotification::create()
                    ->setTitle($this->title)
                    ->setBody($this->body)
            );
    }

    /**
     * FCM требует, чтобы значения в data были строками.
     */
    private function stringifyData(): array
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            $result[$key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $result;
    }
}
