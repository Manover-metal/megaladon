<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
    use HasFactory;

    const TYPE_SERVICE = 'service';
    const TYPE_ADVERT = 'advert';

    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_REJECTED = 3;

    const STATUS_NAMES = [
        self::STATUS_MODERATE => 'На проверке',
        self::STATUS_ACTIVE => 'Активно',
        self::STATUS_REJECTED => 'Отклонено',
    ];

    protected $fillable = [
        'type',
        'user_id',
        'title',
        'description',
        'price',
        'category_id',
        'city_id',
        'additional_phone',
        'type',
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

    public function category()
    {
        return $this->belongsTo(AdCategory::class, 'category_id');
    }
}
