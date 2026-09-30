<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth()->user()?->locale ?? $request->session()->get('locale', config('app.locale'));

        if (! array_key_exists($locale, config('language.supported', []))) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        Date::setLocale($locale);

        return $next($request);
    }
}
