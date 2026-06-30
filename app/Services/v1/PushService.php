<?php

namespace App\Services\v1;

use App\Models\User;
use App\Notifications\FcmPushNotification;
use App\Services\BaseService;

/**
 * Слой отправки push-уведомлений. Триггеры (новый отклик, сообщение в чате,
 * смена статуса заказа и т.п.) подключаются отдельно — здесь только сама
 * отправка.
 */
class PushService extends BaseService
{
    /**
     * Отправить пуш одному пользователю.
     * Молча пропускаем, если пуши выключены или нет токена устройства.
     */
    public function send(User $user, string $title, string $body, array $data = []): void
    {
        if (!$user->push_notifications || empty($user->device_token)) {
            return;
        }

        $user->notify(new FcmPushNotification($title, $body, $data));
    }

    /**
     * Отправить один и тот же пуш нескольким пользователям.
     *
     * @param iterable<User> $users
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->send($user, $title, $body, $data);
        }
    }
}
