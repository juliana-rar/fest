<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catalan by default; Spanish when chosen via /idioma/es (stored in the session)
 * or requested explicitly with ?lang=es (used by the JavaScript API client).
 */
class SetLocale
{
    public const LOCALES = ['ca', 'es'];

    /** Fixed default: config('app.locale') is mutated by setLocale() itself. */
    public const DEFAULT = 'ca';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang');

        if (! in_array($locale, self::LOCALES, true) && $request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT);

        return $next($request);
    }
}
