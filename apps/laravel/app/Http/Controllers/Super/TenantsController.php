<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantsController extends Controller
{
    public function index(Request $request)
    {
        $q = Tenant::query()
            ->select([
                'tenants.*',
                DB::raw("(SELECT d.host FROM domains d WHERE d.tenant_id = tenants.id AND d.panel='tenant' AND d.is_primary=1 ORDER BY d.id DESC LIMIT 1) AS primary_host"),
                DB::raw("(SELECT COUNT(*) FROM leads l WHERE l.tenant_id = tenants.id) AS lead_total"),
                DB::raw("(SELECT COUNT(*) FROM leads l WHERE l.tenant_id = tenants.id AND l.status='won') AS lead_won"),
                DB::raw("(SELECT COUNT(*) FROM leads l WHERE l.tenant_id = tenants.id AND l.status='lost') AS lead_lost"),
            ])
            ->orderByDesc('tenants.id');

        if ($request->filled('q')) {
            $term = '%' . Str::of($request->string('q')->toString())->trim()->limit(80, '')->toString() . '%';
            $q->where(function ($qq) use ($term) {
                $qq->where('tenants.name', 'like', $term)->orWhere('tenants.slug', 'like', $term);
            });
        }
        if ($request->filled('status')) {
            $q->where('tenants.status', $request->string('status')->toString());
        }

        $tenants = $q->paginate(25)->appends($request->query());

        return view('super.tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:active,disabled'],
            'primary_host' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:160'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:6', 'max:72'],
        ]);

        $slug = Str::of($data['slug'])->lower()->trim()->replace(' ', '-')->toString();
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return redirect()->back()->with('status', 'Slug sadece a-z, 0-9 ve "-" içermeli.');
        }
        $existsSlug = DB::table('tenants')->where('slug', $slug)->exists();
        if ($existsSlug) {
            return redirect()->back()->with('status', 'Bu slug zaten kullanılıyor.');
        }

        $host = trim((string) ($data['primary_host'] ?? ''));
        if ($host === '') {
            $host = $slug . '.localhost';
        }

        DB::transaction(function () use ($data, $slug, $host) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => trim((string) $data['name']),
                'slug' => $slug,
                'status' => (string) $data['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('domains')->insert([
                'tenant_id' => $tenantId,
                'host' => $host,
                'panel' => 'tenant',
                'is_primary' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ensure system roles exist for tenant
            $this->ensureTenantRoles($tenantId);

            // Default lead stages
            $this->ensureDefaultLeadStages($tenantId);

            // Optional: create tenant admin user
            $adminEmail = (string) ($data['admin_email'] ?? '');
            if ($adminEmail !== '') {
                $roleId = (int) DB::table('roles')->where('tenant_id', $tenantId)->where('key', 'tenant_admin')->value('id');
                if ($roleId > 0) {
                    DB::table('users')->insert([
                        'tenant_id' => $tenantId,
                        'role_id' => $roleId,
                        'name' => trim((string) ($data['admin_name'] ?? 'Tenant Admin')),
                        'email' => $adminEmail,
                        'password' => password_hash((string) ($data['admin_password'] ?? 'password'), PASSWORD_BCRYPT),
                        'language' => 'tr',
                        'timezone' => 'Europe/Istanbul',
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->to('/super/tenants')->with('status', 'Tenant oluşturuldu.');
    }

    public function show(Request $request, Tenant $tenant)
    {
        $tenantId = (int) $tenant->id;

        $domains = Domain::query()
            ->where('tenant_id', $tenantId)
            ->where('panel', 'tenant')
            ->orderByDesc('is_primary')
            ->orderBy('host')
            ->get();

        $roles = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->orderByRaw("FIELD(`key`, 'tenant_admin','staff','customer')")
            ->orderBy('id')
            ->get();

        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'role_id', 'status', 'created_at']);

        $leadTotal = (int) DB::table('leads')->where('tenant_id', $tenantId)->count();
        $leadWon = (int) DB::table('leads')->where('tenant_id', $tenantId)->where('status', 'won')->count();
        $leadLost = (int) DB::table('leads')->where('tenant_id', $tenantId)->where('status', 'lost')->count();

        return view('super.tenants.show', [
            'tenant' => $tenant,
            'domains' => $domains,
            'roles' => $roles,
            'users' => $users,
            'leadTotal' => $leadTotal,
            'leadWon' => $leadWon,
            'leadLost' => $leadLost,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:active,disabled'],
        ]);

        $slug = Str::of($data['slug'])->lower()->trim()->replace(' ', '-')->toString();
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return redirect()->back()->with('status', 'Slug sadece a-z, 0-9 ve "-" içermeli.');
        }
        $existsSlug = DB::table('tenants')
            ->where('slug', $slug)
            ->where('id', '!=', (int) $tenant->id)
            ->exists();
        if ($existsSlug) {
            return redirect()->back()->with('status', 'Bu slug zaten kullanılıyor.');
        }

        DB::table('tenants')->where('id', (int) $tenant->id)->update([
            'name' => trim((string) $data['name']),
            'slug' => $slug,
            'status' => (string) $data['status'],
            'updated_at' => now(),
        ]);

        $this->ensureTenantRoles((int) $tenant->id);
        $this->ensureDefaultLeadStages((int) $tenant->id);

        return redirect()->back()->with('status', 'Tenant güncellendi.');
    }

    public function addDomain(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,disabled'],
            'is_primary' => ['nullable', 'in:1'],
        ]);

        $host = strtolower(trim((string) $data['host']));
        if (!preg_match('/^[a-z0-9\.\-]+$/', $host)) {
            return redirect()->back()->with('status', 'Host formatı geçersiz.');
        }
        $exists = DB::table('domains')->where('host', $host)->exists();
        if ($exists) {
            return redirect()->back()->with('status', 'Bu domain zaten kayıtlı.');
        }

        DB::transaction(function () use ($tenant, $host, $data) {
            if (!empty($data['is_primary'])) {
                DB::table('domains')
                    ->where('tenant_id', (int) $tenant->id)
                    ->where('panel', 'tenant')
                    ->update(['is_primary' => 0]);
            }

            DB::table('domains')->insert([
                'tenant_id' => (int) $tenant->id,
                'host' => $host,
                'panel' => 'tenant',
                'is_primary' => !empty($data['is_primary']) ? 1 : 0,
                'status' => (string) $data['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('status', 'Domain eklendi.');
    }

    public function setPrimaryDomain(Request $request, Domain $domain)
    {
        if ($domain->panel !== 'tenant' || !$domain->tenant_id) {
            abort(404);
        }

        DB::transaction(function () use ($domain) {
            DB::table('domains')
                ->where('tenant_id', (int) $domain->tenant_id)
                ->where('panel', 'tenant')
                ->update(['is_primary' => 0]);

            DB::table('domains')
                ->where('id', (int) $domain->id)
                ->update(['is_primary' => 1, 'updated_at' => now()]);
        });

        return redirect()->back()->with('status', 'Primary domain güncellendi.');
    }

    public function toggleDomain(Request $request, Domain $domain)
    {
        if ($domain->panel !== 'tenant' || !$domain->tenant_id) {
            abort(404);
        }

        $newStatus = $domain->status === 'active' ? 'disabled' : 'active';
        DB::table('domains')->where('id', (int) $domain->id)->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Domain durumu güncellendi.');
    }

    public function addUser(Request $request, Tenant $tenant)
    {
        $tenantId = (int) $tenant->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:72'],
            'role_key' => ['required', 'in:tenant_admin,staff,customer'],
            'status' => ['required', 'in:active,disabled'],
        ]);

        $exists = DB::table('users')->where('email', $data['email'])->exists();
        if ($exists) {
            return redirect()->back()->with('status', 'Bu email zaten kayıtlı.');
        }

        $this->ensureTenantRoles($tenantId);
        $roleId = (int) DB::table('roles')->where('tenant_id', $tenantId)->where('key', $data['role_key'])->value('id');
        if (!$roleId) {
            return redirect()->back()->with('status', 'Role bulunamadı.');
        }

        DB::table('users')->insert([
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'name' => trim((string) $data['name']),
            'email' => (string) $data['email'],
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'status' => (string) $data['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Kullanıcı eklendi.');
    }

    private function ensureTenantRoles(int $tenantId): void
    {
        $need = [
            ['key' => 'tenant_admin', 'tr' => 'Danışan (Admin)', 'en' => 'Tenant Admin'],
            ['key' => 'staff', 'tr' => 'Çalışan', 'en' => 'Staff'],
            ['key' => 'customer', 'tr' => 'Müşteri', 'en' => 'Customer'],
        ];
        foreach ($need as $r) {
            $exists = DB::table('roles')->where('tenant_id', $tenantId)->where('key', $r['key'])->exists();
            if ($exists) continue;
            DB::table('roles')->insert([
                'tenant_id' => $tenantId,
                'key' => $r['key'],
                'name_tr' => $r['tr'],
                'name_en' => $r['en'],
                'is_system' => 1,
                'created_at' => now(),
            ]);
        }
    }

    private function ensureDefaultLeadStages(int $tenantId): void
    {
        $hasAny = DB::table('lead_stages')->where('tenant_id', $tenantId)->exists();
        if ($hasAny) return;

        $rows = [
            ['name' => 'Yeni', 'sort_order' => 10, 'color' => '#ff7a00', 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'İletişimde', 'sort_order' => 20, 'color' => '#f59e0b', 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Teklif', 'sort_order' => 30, 'color' => '#60a5fa', 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Kazanıldı', 'sort_order' => 40, 'color' => '#34d399', 'is_won' => 1, 'is_lost' => 0],
            ['name' => 'Kaybedildi', 'sort_order' => 50, 'color' => '#f87171', 'is_won' => 0, 'is_lost' => 1],
        ];
        $now = now();
        foreach ($rows as $r) {
            DB::table('lead_stages')->insert([
                'tenant_id' => $tenantId,
                'name' => $r['name'],
                'sort_order' => $r['sort_order'],
                'color' => $r['color'],
                'is_won' => $r['is_won'],
                'is_lost' => $r['is_lost'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

