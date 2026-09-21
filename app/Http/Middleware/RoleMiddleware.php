<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $isDevMode = Auth::check() && app()->environment('local', 'testing') && Setting::enabled('dev_mode');

        if (! Auth::check() || (! in_array(Auth::user()->role, $roles, true) && ! $isDevMode)) {
            abort(403);
        }

        return $next($request);
    }
}
