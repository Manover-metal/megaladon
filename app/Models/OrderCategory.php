<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Категории услуг: и заказы, и услуги исполнителя. */
class OrderCategory extends Model
{
    use HasFactory, SoftDeletes, CategoryTree;

    public const LABEL = 'title';

    protected $fillable = ['title', 'parent_id'];
}
