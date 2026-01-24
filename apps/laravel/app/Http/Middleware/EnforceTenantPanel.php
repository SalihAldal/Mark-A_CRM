<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class EnforceTenantPanel
{
    public function handle(Request $request, Closure $next)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        if (!$ctx->isTenantPanel() || $ctx->tenantId() === null) {
            abort(404);
        }

        return $next($request);
    }
}

