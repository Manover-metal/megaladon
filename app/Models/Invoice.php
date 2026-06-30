<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, CrudTrait;

    const STATUS_CREATED = 'CREATED';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'subscription_id',
        'status',
        'meta',
        'expired_at',
    ];

    public function invoiceable()
    {
        return $this->morphTo('invoiceable');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'id', 'subscription_id');
    }
}
