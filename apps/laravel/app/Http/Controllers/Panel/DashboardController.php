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

        $leadCount = Lead::query()->count();
        $openThreadCount = Thread::query()->where('status', 'open')->count();

        $byStage = DB::table('leads')
            ->select('stage_id', DB::raw('COUNT(*) as cnt'))
            ->where('tenant_id', $tenantId)
            ->groupBy('stage_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return view('panel.dashboard', [
            'leadCount' => $leadCount,
            'openThreadCount' => $openThreadCount,
            'byStage' => $byStage,
        ]);
    }
}

