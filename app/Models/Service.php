<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Услуга исполнителя или магазина. Категории — конечные из дерева услуг (order_categories). */
class Service extends Model
{
    use HasFactory;

    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_REJECTED = 3;

    const STATUS_NAMES = [
        self::STATUS_MODERATE => 'На проверке',
        self::STATUS_ACTIVE => 'Активно',
        self::STATUS_REJECTED => 'Отклонено',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'city_id',
        'additional_phone',
        'status',
        'reject_reason',
    ];

    public function getStatusName(): string
    {
        return self::STATUS_NAMES[$this->status] ?? (string) $this->status;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function media()
    {
        return $this->morphMany(MediaFiles::class, 'mediable');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function categories()
    {
        return $this->belongsToMany(OrderCategory::class, 'service_categories', 'service_id', 'order_category_id');
    }
}
