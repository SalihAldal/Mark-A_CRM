<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    public function index(Request $request)
    {
        $superDomains = Domain::query()
            ->where('panel', 'super')
            ->orderByDesc('is_primary')
            ->orderBy('host')
            ->get();

        $templates = DB::table('ai_prompt_templates')
            ->whereNull('tenant_id') // global
            ->orderBy('template_key')
            ->orderBy('id')
            ->get();

        $audit = DB::table('audit_logs as a')
            ->leftJoin('tenants as t', 't.id', '=', 'a.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')
            ->orderByDesc('a.id')
            ->limit(120)
            ->get([
                'a.id',
                'a.created_at',
                'a.action',
                'a.entity_type',
                'a.entity_id',
                'a.tenant_id',
                't.name as tenant_name',
                'u.email as actor_email',
                'u.name as actor_name',
                'a.metadata_json',
                'a.ip',
            ]);

        $health = [
            'php' => PHP_VERSION,
            'laravel_env' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'timezone' => config('app.timezone'),
            'extensions' => [
                'imap' => extension_loaded('imap'),
                'openssl' => extension_loaded('openssl'),
                'mbstring' => extension_loaded('mbstring'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
            ],
            'storage_link' => is_link(public_path('storage')),
        ];

        return view('super.settings.index', [
            'superDomains' => $superDomains,
            'templates' => $templates,
            'audit' => $audit,
            'health' => $health,
        ]);
    }

    public function addSuperDomain(Request $request)
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
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

        DB::transaction(function () use ($host, $data) {
            if (!empty($data['is_primary'])) {
                DB::table('domains')->where('panel', 'super')->update(['is_primary' => 0]);
            }
            DB::table('domains')->insert([
                'tenant_id' => null,
                'host' => $host,
                'panel' => 'super',
                'is_primary' => !empty($data['is_primary']) ? 1 : 0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('status', 'Super domain eklendi.');
    }

    public function toggleSuperDomain(Request $request, Domain $domain)
    {
        if ($domain->panel !== 'super') {
            abort(404);
        }
        $newStatus = $domain->status === 'active' ? 'disabled' : 'active';
        DB::table('domains')->where('id', (int) $domain->id)->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('status', 'Super domain durumu güncellendi.');
    }

    public function createTemplate(Request $request)
    {
        $data = $request->validate([
            'template_key' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:160'],
            'system_prompt' => ['required', 'string', 'max:5000'],
            'user_prompt' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'in:1'],
        ]);

        $key = Str::of($data['template_key'])->lower()->trim()->replace(' ', '_')->toString();
        if (!preg_match('/^[a-z0-9\_]+$/', $key)) {
            return redirect()->back()->with('status', 'template_key sadece a-z, 0-9, "_" içermeli.');
        }

        DB::table('ai_prompt_templates')->insert([
            'tenant_id' => null,
            'template_key' => $key,
            'title' => trim((string) $data['title']),
            'system_prompt' => (string) $data['system_prompt'],
            'user_prompt' => (string) $data['user_prompt'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Prompt şablonu eklendi.');
    }

    public function updateTemplate(Request $request, int $template)
    {
        $row = DB::table('ai_prompt_templates')->where('id', $template)->first();
        if (!$row || $row->tenant_id !== null) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'system_prompt' => ['required', 'string', 'max:5000'],
            'user_prompt' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'in:1'],
        ]);

        DB::table('ai_prompt_templates')->where('id', $template)->update([
            'title' => trim((string) $data['title']),
            'system_prompt' => (string) $data['system_prompt'],
            'user_prompt' => (string) $data['user_prompt'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);

        return redirect()->back()->with('status', 'Prompt şablonu güncellendi.');
    }
}

