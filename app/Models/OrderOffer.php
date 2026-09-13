<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderOffer extends Model
{
    use HasFactory, CrudTrait;

    // За что указана цена — колонка enum price_type, по умолчанию total.
    // PHP 8.0: нативных enum ещё нет, поэтому константы.
    const PRICE_TYPE_TOTAL = 'total';
    const PRICE_TYPE_PER_UNIT = 'per_unit';

    const PRICE_TYPES = [
        self::PRICE_TYPE_TOTAL,
        self::PRICE_TYPE_PER_UNIT,
    ];

    // Подписи для админки.
    const PRICE_TYPE_LABELS = [
        self::PRICE_TYPE_TOTAL => 'За всю работу',
        self::PRICE_TYPE_PER_UNIT => 'За шт.',
    ];

    protected $fillable = [
        'order_id',
        'user_id',
        'city_id',
        'price',
        'price_type',
        'date',
        'comment',
        'expired_at',
    ];

    // withTrashed: отклик остаётся видимым, даже если автор удалил аккаунт —
    // он отдаётся как «Удалённый аккаунт» (см. UserPresenter).
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
