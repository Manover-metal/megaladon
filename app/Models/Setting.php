<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

/** Настройки, переключаемые в админке. Строки заводятся миграциями. */
class Setting extends Model
{
    use CrudTrait;

    protected $fillable = ['value'];

    /** Строки нет — считаем выключенным. */
    public static function flag(string $key): bool
    {
        return (bool) static::where('key', $key)->value('value');
    }
}
