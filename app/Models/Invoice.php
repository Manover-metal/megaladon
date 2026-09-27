<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, CrudTrait;

    const STATUS_CREATED = 'CREATED';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_EXPIRED = 'EXPIRED';

    const METHOD_APPLE = 'apple';
    const METHOD_GOOGLE = 'google';
    const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'subscription_id',
        'status',
        'meta',
        'expired_at',
        'payment_method',
        'uuid',
        'store_transaction_id',
    ];

    // Дефолт колонки БД после create() в модель не попадает.
    protected $attributes = [
        'payment_method' => self::METHOD_MANUAL,
    ];

    protected static function booted(): void
    {
        // uuid уходит в покупку магазина и возвращается в его вебхуках.
        static::creating(function (Invoice $invoice) {
            $invoice->uuid ??= (string) Str::uuid();
        });
    }

    public function invoiceable()
    {
        return $this->morphTo('invoiceable');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'id', 'subscription_id');
    }
}
