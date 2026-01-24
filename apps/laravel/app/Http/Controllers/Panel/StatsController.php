<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $period = $request->string('period')->toString();
        if (!in_array($period, ['day', 'week', 'month'], true)) {
            $period = 'week';
        }

        $now = now();
        $start = match ($period) {
            'day' => $now->copy()->startOfDay(),
            'month' => $now->copy()->subDays(29)->startOfDay(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };

        $kpiToday = DB::table('leads')->where('tenant_id', $tenantId)->whereDate('created_at', $now->toDateString())->count();
        $kpiWeek = DB::table('leads')->where('tenant_id', $tenantId)->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())->count();
        $kpiMonth = DB::table('leads')->where('tenant_id', $tenantId)->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())->count();

        $kpiWon = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->where('status', 'won')
            ->count();
        $kpiLost = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->where('status', 'lost')
            ->count();
        $kpiTotal = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->count();

        $seriesStart = $now->copy()->subDays(13)->startOfDay();
        $daily = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $seriesStart)
            ->select(
                DB::raw('DATE(created_at) as d'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status='won' THEN 1 ELSE 0 END) as won"),
                DB::raw("SUM(CASE WHEN status='lost' THEN 1 ELSE 0 END) as lost")
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get();

        // Fill missing days for chart continuity
        $dailyMap = [];
        foreach ($daily as $row) {
            $dailyMap[(string) $row->d] = $row;
        }
        $dailyFilled = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->toDateString();
            $row = $dailyMap[$d] ?? (object) ['d' => $d, 'total' => 0, 'won' => 0, 'lost' => 0];
            $dailyFilled[] = $row;
        }

        $statusDist = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->get();

        $wonLeads = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->where('status', 'won')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get(['id', 'name', 'company', 'created_at', 'score']);
        $lostLeads = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $start)
            ->where('status', 'lost')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get(['id', 'name', 'company', 'created_at', 'score']);

        $leadsByStage = DB::table('lead_stages')
            ->where('lead_stages.tenant_id', $tenantId)
            ->leftJoin('leads', function ($join) use ($tenantId) {
                $join->on('leads.stage_id', '=', 'lead_stages.id')
                    ->where('leads.tenant_id', '=', $tenantId);
            })
            ->select('lead_stages.name', DB::raw('COUNT(leads.id) as cnt'))
            ->groupBy('lead_stages.id', 'lead_stages.name')
            ->orderBy('lead_stages.sort_order')
            ->get();

        $messagesByType = DB::table('messages')
            ->select('message_type', DB::raw('COUNT(*) as cnt'))
            ->where('tenant_id', $tenantId)
            ->groupBy('message_type')
            ->orderByDesc('cnt')
            ->get();

        $staffPerformance = DB::table('users')
            ->where('users.tenant_id', $tenantId)
            ->leftJoin('messages', function ($join) use ($tenantId) {
                $join->on('messages.sender_user_id', '=', 'users.id')
                    ->where('messages.tenant_id', '=', $tenantId);
            })
            ->select('users.name', DB::raw('COUNT(messages.id) as cnt'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return view('panel.stats.index', [
            'period' => $period,
            'kpi' => [
                'today' => $kpiToday,
                'week' => $kpiWeek,
                'month' => $kpiMonth,
                'range_total' => $kpiTotal,
                'range_won' => $kpiWon,
                'range_lost' => $kpiLost,
            ],
            'dailySeries' => $dailyFilled,
            'statusDist' => $statusDist,
            'wonLeads' => $wonLeads,
            'lostLeads' => $lostLeads,
            'leadsByStage' => $leadsByStage,
            'messagesByType' => $messagesByType,
            'staffPerformance' => $staffPerformance,
        ]);
    }
}

