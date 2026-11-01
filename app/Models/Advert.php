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

    // category_id ссылается на дерево по типу: объявление — ad_categories,
    // услуга — категории услуг (order_categories), общие с заказами.
    public function category()
    {
        return $this->belongsTo(AdCategory::class, 'category_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(OrderCategory::class, 'category_id');
    }

    /** @return class-string<AdCategory|OrderCategory> */
    public static function categoryModel(?string $type): string
    {
        return $type === self::TYPE_SERVICE ? OrderCategory::class : AdCategory::class;
    }

    public function categoryRecord(): AdCategory|OrderCategory|null
    {
        return $this->type === self::TYPE_SERVICE ? $this->serviceCategory : $this->category;
    }
}
