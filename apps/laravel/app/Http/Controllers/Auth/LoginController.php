<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], true)) {
            throw ValidationException::withMessages([
                'email' => __('ui.auth_invalid'),
            ]);
        }

        $request->session()->regenerate();

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        $roleKey = (string) (Auth::user()?->role?->key ?? '');

        // Superadmin sadece Super Panel domaininde çalışır.
        if ($roleKey === 'superadmin') {
            if (!$ctx->isSuperPanel()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->to('/login')->with('status', 'Superadmin girişi için Super Panel domainini kullan: superadmin.localhost:8000');
            }
            return redirect()->to('/super');
        }

        // Tenant kullanıcıları (tenant_admin/staff/customer) tenant panel domaininde çalışır.
        if ($ctx->isSuperPanel()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->to('/login')->with('status', 'Bu kullanıcı Super Panelde kullanılamaz. Tenant domaini ile giriş yap (örn: tenant1.localhost:8000).');
        }

        // Audit: successful tenant login
        try {
            $tenantId = $ctx->requireTenantId();
            $uid = (int) (Auth::id() ?? 0);
            if ($tenantId && $uid) {
                DB::table('audit_logs')->insert([
                    'tenant_id' => $tenantId,
                    'actor_user_id' => $uid,
                    'action' => 'user.login',
                    'entity_type' => 'user',
                    'entity_id' => $uid,
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'metadata_json' => json_encode([
                        'email' => (string) (Auth::user()?->email ?? ''),
                        'host' => (string) ($ctx->host() ?? ''),
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Do not block login if audit insert fails
        }

        return redirect()->to('/panel');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('/login');
    }
}

