<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCategory extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = ['name', 'parent_id'];

    public function child()
    {
        return $this->hasMany(AdCategory::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(AdCategory::class, 'parent_id');
    }
}
