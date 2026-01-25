<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Thread;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $role = (string) ($request->user()->role?->key ?? '');
        $uid = (int) $request->user()->id;

        $leadsBase = DB::table('leads as l')->where('l.tenant_id', $tenantId);
        $threadsBase = DB::table('threads as t')->where('t.tenant_id', $tenantId);

        if ($role === 'staff') {
            $leadsBase->where(function ($q) use ($uid) {
                $q->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
            });

            $threadsBase
                ->leftJoin('leads as l', function ($join) use ($tenantId) {
                    $join->on('l.id', '=', 't.lead_id')->where('l.tenant_id', '=', $tenantId);
                })
                ->where(function ($q) use ($uid) {
                    $q->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
                });
        }

        $leadCount = (clone $leadsBase)->count();
        $wonCount = (clone $leadsBase)->where('l.status', 'won')->count();
        $lostCount = (clone $leadsBase)->where('l.status', 'lost')->count();

        $openThreadCount = (clone $threadsBase)->where('t.status', 'open')->count();

        $liveLeads = (clone $leadsBase)
            ->leftJoin('lead_stages as s', 's.id', '=', 'l.stage_id')
            ->select('l.id', 'l.name', 'l.email', 'l.phone', 'l.score', 's.name as stage_name', 's.color as stage_color')
            ->orderByDesc('l.updated_at')
            ->limit(10)
            ->get();

        $liveThreads = (clone $threadsBase)
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('integration_accounts as a', 'a.id', '=', 't.integration_account_id')
            ->select('t.id', 't.channel', 't.status', 't.last_message_at', 'c.name as contact_name', 'a.provider as provider')
            ->orderByDesc(DB::raw('COALESCE(t.last_message_at, t.created_at)'))
            ->limit(10)
            ->get();

        return view('panel.dashboard', [
            'leadCount' => $leadCount,
            'openThreadCount' => $openThreadCount,
            'wonCount' => $wonCount,
            'lostCount' => $lostCount,
            'liveLeads' => $liveLeads,
            'liveThreads' => $liveThreads,
        ]);
    }
}

