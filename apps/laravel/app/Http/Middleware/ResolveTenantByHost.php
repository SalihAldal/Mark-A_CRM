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

        // Local/dev fallback:
        // If host is 127.0.0.1 / localhost and there is at least one active domain configured,
        // pick a sensible default so devs can open the app without editing hosts file.
        if (!$domain && in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            $wantedPanel = $request->is('super*')
                ? TenantContext::PANEL_SUPER
                : TenantContext::PANEL_TENANT;

            $domain = Domain::query()
                ->where('panel', $wantedPanel)
                ->where('status', 'active')
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->first();
        }

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

