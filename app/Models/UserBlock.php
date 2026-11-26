<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    protected $fillable = ['user_id', 'blocked_user_id'];

    /** @var array<int, array{by_me: int[], me: int[]}> кэш на запрос, по id смотрящего */
    private static array $flagsCache = [];

    protected static function booted(): void
    {
        $reset = fn () => self::$flagsCache = [];
        static::saved($reset);
        static::deleted($reset);
    }

    /**
     * Кого смотрящий заблокировал и кто заблокировал его. Один запрос на
     * направление за HTTP-запрос: презентеры зовут это для каждого
     * пользователя в списке.
     *
     * @return array{by_me: int[], me: int[]}
     */
    public static function flagsFor(?int $viewerId): array
    {
        if (!$viewerId) {
            return ['by_me' => [], 'me' => []];
        }
        return self::$flagsCache[$viewerId] ??= [
            'by_me' => static::where('user_id', $viewerId)->pluck('blocked_user_id')->map('intval')->all(),
            'me' => static::where('blocked_user_id', $viewerId)->pluck('user_id')->map('intval')->all(),
        ];
    }

    public static function blockedByMe(?int $viewerId, int $userId): bool
    {
        return in_array($userId, self::flagsFor($viewerId)['by_me'], true);
    }

    public static function blockedMe(?int $viewerId, int $userId): bool
    {
        return in_array($userId, self::flagsFor($viewerId)['me'], true);
    }

    /** Блокировка в любую сторону между смотрящим и пользователем. */
    public static function between(?int $viewerId, int $userId): bool
    {
        return self::blockedByMe($viewerId, $userId) || self::blockedMe($viewerId, $userId);
    }

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
