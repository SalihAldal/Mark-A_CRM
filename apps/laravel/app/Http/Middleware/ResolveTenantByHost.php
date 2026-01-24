<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenantByHost
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost(); // port içermez

        $domain = Domain::query()
            ->where('host', $host)
            ->where('status', 'active')
            ->first();

        if (!$domain) {
            abort(404, 'Domain not found.');
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        $panel = $domain->panel === TenantContext::PANEL_SUPER
            ? TenantContext::PANEL_SUPER
            : TenantContext::PANEL_TENANT;

        $tenantId = $panel === TenantContext::PANEL_SUPER ? null : (int) $domain->tenant_id;
        $ctx->setResolved($tenantId, $panel, $host);

        return $next($request);
    }
}

