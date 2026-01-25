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

        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 14, 30, 60, 90], true)) {
            $days = 30;
        }

        $now = now();
        $start = $now->copy()->subDays($days - 1)->startOfDay();

        $role = (string) ($request->user()->role?->key ?? '');
        $uid = (int) $request->user()->id;

        $threadsBase = DB::table('threads as t')
            ->where('t.tenant_id', $tenantId)
            ->where('t.created_at', '>=', $start);

        $messagesBase = DB::table('messages as m')
            ->join('threads as t', 't.id', '=', 'm.thread_id')
            ->where('m.tenant_id', $tenantId)
            ->where('t.tenant_id', $tenantId)
            ->where('m.created_at', '>=', $start);

        $leadsBase = DB::table('leads as l')
            ->where('l.tenant_id', $tenantId);

        if ($role === 'staff') {
            $threadsBase->leftJoin('leads as l', function ($join) use ($tenantId) {
                $join->on('l.id', '=', 't.lead_id')->where('l.tenant_id', '=', $tenantId);
            })->where(function ($qq) use ($uid) {
                $qq->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
            });

            $messagesBase->leftJoin('leads as l', function ($join) use ($tenantId) {
                $join->on('l.id', '=', 't.lead_id')->where('l.tenant_id', '=', $tenantId);
            })->where(function ($qq) use ($uid) {
                $qq->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
            });

            $leadsBase->where(function ($qq) use ($uid) {
                $qq->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
            });
        }

        $dailyThreads = (clone $threadsBase)
            ->select(DB::raw('DATE(t.created_at) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('DATE(t.created_at)'))
            ->orderBy('d')
            ->get();

        $dailyIn = (clone $messagesBase)
            ->where('m.sender_type', 'contact')
            ->select(DB::raw('DATE(m.created_at) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('DATE(m.created_at)'))
            ->orderBy('d')
            ->get();

        $dailyOut = (clone $messagesBase)
            ->where('m.sender_type', 'user')
            ->select(DB::raw('DATE(m.created_at) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('DATE(m.created_at)'))
            ->orderBy('d')
            ->get();

        $mapThreads = collect($dailyThreads)->keyBy('d');
        $mapIn = collect($dailyIn)->keyBy('d');
        $mapOut = collect($dailyOut)->keyBy('d');

        $traffic = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->toDateString();
            $traffic[] = (object) [
                'd' => $d,
                'threads' => (int) data_get($mapThreads->get($d), 'cnt', 0),
                'in' => (int) data_get($mapIn->get($d), 'cnt', 0),
                'out' => (int) data_get($mapOut->get($d), 'cnt', 0),
            ];
        }

        $kpiThreads = (clone $threadsBase)->count();
        $kpiMsgTotal = (clone $messagesBase)->count();
        $kpiMsgIn = (clone $messagesBase)->where('m.sender_type', 'contact')->count();
        $kpiMsgOut = (clone $messagesBase)->where('m.sender_type', 'user')->count();

        $leadsByStage = DB::table('lead_stages as s')
            ->where('s.tenant_id', $tenantId)
            ->leftJoin('leads as l', function ($join) use ($tenantId) {
                $join->on('l.stage_id', '=', 's.id')->where('l.tenant_id', '=', $tenantId);
            })
            ->when($role === 'staff', function ($q) use ($uid) {
                $q->where(function ($qq) use ($uid) {
                    $qq->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
                });
            })
            ->select('s.name', 's.color', DB::raw('COUNT(l.id) as cnt'))
            ->groupBy('s.id', 's.name', 's.color')
            ->orderBy('s.sort_order')
            ->get();

        return view('panel.stats.index', [
            'days' => $days,
            'traffic' => $traffic,
            'kpi' => [
                'threads' => (int) $kpiThreads,
                'messages_total' => (int) $kpiMsgTotal,
                'messages_in' => (int) $kpiMsgIn,
                'messages_out' => (int) $kpiMsgOut,
            ],
            'leadsByStage' => $leadsByStage,
        ]);
    }
}

