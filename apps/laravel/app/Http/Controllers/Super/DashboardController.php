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
        $leadCount = (int) DB::table('leads')->count();

        return view('super.dashboard', [
            'tenantCount' => $tenantCount,
            'leadCount' => $leadCount,
        ]);
    }
}

