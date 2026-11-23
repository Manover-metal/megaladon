<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;
    const EXECUTOR = 'executor';
    const STORE = 'store';

    /**
     * Платный доступ включён (настройка в админке). Выключен — подписка нигде
     * не требуется, и все исполнители и магазины считаются подписанными.
     */
    public static function enabled(): bool
    {
        return Setting::flag('subscriptions_enabled');
    }

    /**
     * Дата окончания доступа, которую видит приложение, когда подписки
     * выключены. Старые версии приложения закрывают отклики и контакты по
     * subscription_expired_at — бессрочная дата открывает их без обновления.
     */
    public const UNLIMITED_UNTIL = '2099-12-31';

    protected $fillable = [
        'type',
        'validity',
        'trial_months',
        'price',
        'apple_product_id',
        'google_product_id',
    ];

    public function getFullAttribute($value)
    {
        return '(' . ($this->type == self::EXECUTOR ? 'Исполнитель' : 'Магазин') . ') ' 
         . $this->validity . ' мес. ' . $this->price .  ' тг.';
    }
}
