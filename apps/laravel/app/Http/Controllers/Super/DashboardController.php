<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantCount = Tenant::query()->count();

        $tenantRows = DB::table('tenants as t')
            ->leftJoin('leads as l', 'l.tenant_id', '=', 't.id')
            ->select(
                't.id',
                't.name',
                DB::raw('COUNT(l.id) as total'),
                DB::raw("SUM(CASE WHEN l.status='won' THEN 1 ELSE 0 END) as won"),
                DB::raw("SUM(CASE WHEN l.status='lost' THEN 1 ELSE 0 END) as lost")
            )
            ->groupBy('t.id', 't.name')
            ->orderByDesc('total')
            ->get();

        $totals = [
            'total' => (int) $tenantRows->sum('total'),
            'won' => (int) $tenantRows->sum('won'),
            'lost' => (int) $tenantRows->sum('lost'),
        ];

        $liveLeads = DB::table('leads as l')
            ->leftJoin('lead_stages as s', 's.id', '=', 'l.stage_id')
            ->leftJoin('tenants as t', 't.id', '=', 'l.tenant_id')
            ->select('l.id', 'l.name', 'l.email', 'l.phone', 'l.score', 's.name as stage_name', 's.color as stage_color', 't.name as tenant_name')
            ->orderByDesc('l.updated_at')
            ->limit(10)
            ->get();

        $liveThreads = DB::table('threads as th')
            ->leftJoin('contacts as c', 'c.id', '=', 'th.contact_id')
            ->leftJoin('integration_accounts as a', 'a.id', '=', 'th.integration_account_id')
            ->leftJoin('tenants as t', 't.id', '=', 'th.tenant_id')
            ->select('th.id', 'th.channel', 'th.status', 'th.last_message_at', 'c.name as contact_name', 'a.provider as provider', 't.name as tenant_name')
            ->orderByDesc(DB::raw('COALESCE(th.last_message_at, th.created_at)'))
            ->limit(10)
            ->get();

        return view('super.dashboard', [
            'tenantCount' => $tenantCount,
            'tenantRows' => $tenantRows,
            'totals' => $totals,
            'liveLeads' => $liveLeads,
            'liveThreads' => $liveThreads,
        ]);
    }
}

