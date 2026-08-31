<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $isDevMode = Auth::check() && app()->environment('local', 'testing') && Setting::enabled('dev_mode');

        if (! Auth::check() || (Auth::user()->role !== $role && ! $isDevMode)) {
            abort(403);
        }

        return $next($request);
    }
}
