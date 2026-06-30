<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes, CrudTrait;
    const EXECUTOR = 'executor';
    const STORE = 'store';

    protected $fillable = [
        'type',
        'validity',
        'price',
    ];

    public function getFullAttribute($value)
    {
        return '(' . ($this->type == self::EXECUTOR ? 'Исполнитель' : 'Магазин') . ') ' 
         . $this->validity . ' мес. ' . $this->price .  ' тг.';
    }
}
