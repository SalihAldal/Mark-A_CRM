<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockIgnitionWhenDebugOff
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('app.debug') && $request->is('_ignition/*')) {
            abort(404);
        }

        return $next($request);
    }
}

