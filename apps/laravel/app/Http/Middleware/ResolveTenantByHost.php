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
        // IMPORTANT:
        // Meta (Facebook/Instagram/WhatsApp) webhook validation + delivery requests come from Meta servers
        // to your public HTTPS endpoint (often via ngrok / reverse proxy). Those requests MUST be publicly
        // reachable even if the host is not registered as a tenant/super domain in our multi-tenant table.
        //
        // We intentionally bypass tenant-domain resolution for webhook routes; the webhook controller
        // resolves the tenant context using the integration account / verify token inside the payload.
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

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

