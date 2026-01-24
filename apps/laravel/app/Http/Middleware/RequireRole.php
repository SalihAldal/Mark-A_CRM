<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $roleKeys)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $allowed = collect(explode('|', $roleKeys))
            ->map(fn ($x) => trim($x))
            ->filter()
            ->values()
            ->all();

        $key = $user->role?->key;
        if (!$key || !in_array($key, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}

