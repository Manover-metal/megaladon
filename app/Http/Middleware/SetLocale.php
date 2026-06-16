<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ru', 'kk'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', config('app.fallback_locale'));
        $locale = in_array($locale, self::SUPPORTED_LOCALES) ? $locale : config('app.fallback_locale');

        App::setLocale($locale);

        return $next($request);
    }
}
