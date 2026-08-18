<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Read the interface in the language the signed-in user picked. A user
     * without one, or with a language the app no longer ships, keeps the
     * application default.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale !== null && array_key_exists($locale, config('dofit.locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
