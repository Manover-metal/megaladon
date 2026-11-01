<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCategory extends Model
{
    use HasFactory, SoftDeletes, CategoryTree;

    public const LABEL = 'name';

    protected $fillable = ['name', 'parent_id'];
}
