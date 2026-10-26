<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Только пользователи с role = admin и без бана (после auth:api). */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (!$u || $u->role !== 'admin' || $u->isBanned()) {
            abort(403, 'Нет доступа в админку');
        }

        return $next($request);
    }
}
