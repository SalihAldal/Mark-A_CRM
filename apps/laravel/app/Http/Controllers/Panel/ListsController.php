<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SegmentList;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $q = SegmentList::query()->orderByDesc('id');
        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';
            $q->where('name', 'like', $term);
        }
        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        $lists = $q->paginate(20)->appends($request->query());

        $role = (string) ($request->user()->role?->key ?? '');
        $uid = (int) $request->user()->id;

        // Customers list on Lists page should come from leads themselves (not "customer" user accounts).
        // Tenant admin: all leads in tenant. Staff: only assigned/owned leads.
        $leadCustomers = DB::table('leads as l')
            ->where('l.tenant_id', $tenantId)
            ->when($role === 'staff', function ($q) use ($uid) {
                $q->where(function ($qq) use ($uid) {
                    $qq->where('l.assigned_user_id', $uid)->orWhere('l.owner_user_id', $uid);
                });
            })
            ->leftJoin('users as au', function ($join) use ($tenantId) {
                $join->on('au.id', '=', 'l.assigned_user_id')
                    ->where('au.tenant_id', '=', $tenantId);
            })
            ->select([
                'l.id',
                'l.name',
                'l.email',
                'l.phone',
                'l.source',
                'l.status',
                'l.updated_at',
                'au.name as assigned_name',
            ])
            ->orderByDesc('l.updated_at')
            ->limit(200)
            ->get();

        return view('panel.lists.index', [
            'lists' => $lists,
            'leadCustomers' => $leadCustomers,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', 'in:lead,contact'],
        ]);

        SegmentList::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->to('/lists')->with('status', 'Liste oluşturuldu.');
    }
}

