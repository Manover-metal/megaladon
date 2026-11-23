<?php

namespace App\Services\v1;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Пара токенов приложения: access (Sanctum, живёт access_ttl секунд) и
 * refresh (живёт refresh_ttl, одноразовый — каждый refresh выдаёт новую пару
 * и гасит старую). Админка использует вечные токены 'admin' мимо этого класса.
 */
class TokenService
{
    /**
     * Клиент умеет обновлять токен — шлёт X-Auth-Refresh. Старые версии
     * приложения его не шлют и refresh не делают: короткий токен выкидывал бы
     * их из аккаунта каждый час, поэтому им access живёт столько же, сколько
     * refresh. Убрать, когда старых версий не останется.
     */
    public static function clientRefreshes(): bool
    {
        return request()->hasHeader('X-Auth-Refresh');
    }

    /** @return array{token: string, refresh_token: string, expires_in: int} */
    public function issue(User $user): array
    {
        $ttl = (int) config(self::clientRefreshes() ? 'sanctum.access_ttl' : 'sanctum.refresh_ttl');
        $access = $user->createToken('api');
        $access->accessToken->forceFill(['expires_at' => now()->addSeconds($ttl)])->save();

        $refresh = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refresh),
            'access_token_id' => $access->accessToken->id,
            'expires_at' => now()->addSeconds((int) config('sanctum.refresh_ttl')),
        ]);

        return [
            'token' => $access->plainTextToken,
            'refresh_token' => $refresh,
            'expires_in' => $ttl,
        ];
    }

    /**
     * Ротация. Null — refresh не принят (неизвестен, уже использован, истёк,
     * пользователь удалён или забанен): приложению пора на вход.
     */
    public function refresh(string $refreshToken): ?array
    {
        return DB::transaction(function () use ($refreshToken) {
            // lockForUpdate: два одновременных refresh одним токеном — второй
            // ждёт, видит удалённую строку и получает отказ.
            $row = RefreshToken::where('token', hash('sha256', $refreshToken))->lockForUpdate()->first();
            if (!$row) {
                return null;
            }
            $row->delete();
            PersonalAccessToken::whereKey($row->access_token_id)->delete();

            $user = $row->user;
            if ($row->expires_at->isPast() || !$user || $user->isBanned()) {
                return null;
            }

            return $this->issue($user);
        });
    }

    /** Выход с устройства: гасим его access-токен и выданный вместе с ним refresh. */
    public function revoke(PersonalAccessToken $token): void
    {
        RefreshToken::where('access_token_id', $token->id)->delete();
        $token->delete();
    }

    /** Выход отовсюду: бан, удаление аккаунта. */
    public function revokeAll(User $user): void
    {
        RefreshToken::where('user_id', $user->id)->delete();
        $user->tokens()->delete();
    }
}
