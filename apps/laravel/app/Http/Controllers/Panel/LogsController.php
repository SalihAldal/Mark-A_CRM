<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $q = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id');

        $role = (string) ($request->user()->role?->key ?? '');
        if ($role === 'staff') {
            $q->where('actor_user_id', (int) $request->user()->id);
        }

        if ($request->filled('action')) {
            $q->where('action', $request->string('action')->toString());
        }

        $rows = $q->paginate(30)->appends($request->query());

        $actorIds = collect($rows->items())->pluck('actor_user_id')->filter()->unique()->values()->all();
        $actors = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $actorIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        return view('panel.logs.index', [
            'rows' => $rows,
            'actors' => $actors,
        ]);
    }
}

