<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCategory extends Model
{
    use HasFactory, SoftDeletes, CrudTrait;

    protected $fillable = ['title', 'parent_id'];

    public function child()
    {
        return $this->hasMany(OrderCategory::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(OrderCategory::class, 'parent_id');
    }
}
