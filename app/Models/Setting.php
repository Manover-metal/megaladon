<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Настройки, переключаемые в админке. Строки заводятся миграциями. */
class Setting extends Model
{
    protected $fillable = ['value'];

    /** Строки нет — считаем выключенным. */
    public static function flag(string $key): bool
    {
        return (bool) static::where('key', $key)->value('value');
    }
}
