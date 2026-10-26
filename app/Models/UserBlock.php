<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    protected $fillable = ['user_id', 'blocked_user_id'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_user_id')->withTrashed();
    }

    // Блокировка в любую сторону — общение между двумя людьми закрыто.
    public static function existsBetween(int $a, int $b): bool
    {
        return static::where(fn ($q) => $q->where('user_id', $a)->where('blocked_user_id', $b))
            ->orWhere(fn ($q) => $q->where('user_id', $b)->where('blocked_user_id', $a))
            ->exists();
    }
}
