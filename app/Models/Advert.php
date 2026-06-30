<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
    use HasFactory, CrudTrait;

    const TYPE_SERVICE = 'service';
    const TYPE_ADVERT = 'advert';

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
        return $this->belongsTo(AdCategory::class, 'category_id');
    }

    public function chatable()
    {
        return $this->morphOne(Chat::class, 'chatable');
    }
}
