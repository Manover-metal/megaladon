<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderView extends Model
{
    use HasFactory;

    // seen_updated_at обязателен в списке: markSeen пишет через
    // updateOrCreate, то есть массовым присвоением, и без fillable поле
    // молча не сохранится.
    protected $fillable = [
        'user_id',
        'order_id',
        'seen_status',
        'seen_offers_count',
        'seen_updated_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
