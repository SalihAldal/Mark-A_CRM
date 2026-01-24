<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class EnforceSuperPanel
{
    public function handle(Request $request, Closure $next)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        if (!$ctx->isSuperPanel()) {
            abort(404);
        }

        return $next($request);
    }
}

