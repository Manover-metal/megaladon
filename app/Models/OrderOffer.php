<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderOffer extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'order_id',
        'user_id',
        'city_id',
        'price',
        'date',
        'comment',
        'expired_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
