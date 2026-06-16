<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'category_id',
        'city_id',
        'additional_phone',
        'type',
    ];

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
        return $this->belongsTo(AdvertCategory::class, 'category_id');
    }

    public function chatable()
    {
        return $this->morphOne(Chat::class, 'chatable');
    }
}
