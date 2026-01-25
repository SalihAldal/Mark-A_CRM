<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AiRule;
use App\Models\IntegrationAccount;
use App\Models\LeadStage;
use App\Models\User;
use App\Services\TenantSettings;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $rules = AiRule::query()->orderByDesc('id')->paginate(20);
        $stages = LeadStage::query()->orderBy('sort_order')->orderBy('id')->get();
        $integrations = IntegrationAccount::query()->orderByDesc('id')->get();
        // Convenience map for Settings UI (prefill forms by provider). If multiple exist, pick the latest.
        $integrationsByProvider = $integrations
            ->groupBy('provider')
            ->map(fn ($g) => $g->first());
        $staff = DB::table('users')
            ->where('users.tenant_id', $tenantId)
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.created_at', 'roles.key as role_key', 'roles.name_tr as role_name_tr')
            ->orderBy('users.name')
            ->get();

        $ts = app(TenantSettings::class);
        $mail = [
            'from_email' => (string) $ts->get('mail.from_email', ''),
            'from_name' => (string) $ts->get('mail.from_name', 'Mark-A CRM'),
            'smtp_host' => (string) $ts->get('mail.smtp.host', ''),
            'smtp_port' => (string) $ts->get('mail.smtp.port', '587'),
            'smtp_encryption' => (string) $ts->get('mail.smtp.encryption', 'tls'),
            'smtp_username' => (string) $ts->get('mail.smtp.username', ''),
            'imap_host' => (string) $ts->get('mail.imap.host', ''),
            'imap_port' => (string) $ts->get('mail.imap.port', '993'),
            'imap_encryption' => (string) $ts->get('mail.imap.encryption', 'ssl'),
            'imap_username' => (string) $ts->get('mail.imap.username', ''),
            'imap_folder' => (string) $ts->get('mail.imap.folder', 'INBOX'),
        ];

        return view('panel.settings.index', [
            'rules' => $rules,
            'stages' => $stages,
            'integrations' => $integrations,
            'integrationsByProvider' => $integrationsByProvider,
            'staff' => $staff,
            'mail' => $mail,
        ]);
    }

    public function saveAiRules(Request $request)
    {
        $data = $request->validate([
            'sector' => ['required', 'string', 'max:120'],
            'tone' => ['required', 'string', 'max:64'],
            'language' => ['required', 'in:tr,en'],
            'sales_focus' => ['nullable', 'boolean'],
            'forbidden_phrases' => ['nullable', 'string', 'max:2000'],
        ]);

        AiRule::query()->create([
            'sector' => $data['sector'],
            'tone' => $data['tone'],
            'language' => $data['language'],
            'sales_focus' => (bool) ($data['sales_focus'] ?? false),
            'forbidden_phrases' => $data['forbidden_phrases'] ?? null,
            'created_at' => now(),
        ]);

        return redirect()->to('/settings')->with('status', 'AI rules kaydedildi.');
    }

    public function updateAiRule(Request $request, AiRule $rule)
    {
        // AiRule has tenant scope; tenant_admin only reaches here
        $data = $request->validate([
            'sector' => ['required', 'string', 'max:120'],
            'tone' => ['required', 'string', 'max:64'],
            'language' => ['required', 'in:tr,en'],
            'sales_focus' => ['nullable', 'boolean'],
            'forbidden_phrases' => ['nullable', 'string', 'max:2000'],
        ]);

        $rule->fill([
            'sector' => trim((string) $data['sector']),
            'tone' => trim((string) $data['tone']),
            'language' => (string) $data['language'],
            'sales_focus' => (bool) ($data['sales_focus'] ?? false),
            'forbidden_phrases' => $data['forbidden_phrases'] ? trim((string) $data['forbidden_phrases']) : null,
        ]);
        $rule->save();

        return redirect()->to('/settings')->with('status', 'AI rule güncellendi.');
    }

    public function deleteAiRule(Request $request, AiRule $rule)
    {
        $rule->delete();
        return redirect()->to('/settings')->with('status', 'AI rule silindi.');
    }

    public function storeStage(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'color' => ['nullable', 'string', 'max:32'],
            'is_won' => ['required', 'boolean'],
            'is_lost' => ['required', 'boolean'],
        ]);

        if ($data['is_won'] && $data['is_lost']) {
            return redirect()->to('/settings')->with('status', 'Stage hem WON hem LOST olamaz.');
        }

        LeadStage::query()->create([
            'name' => trim($data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'color' => $data['color'] ? trim((string) $data['color']) : null,
            'is_won' => (bool) $data['is_won'],
            'is_lost' => (bool) $data['is_lost'],
            'created_at' => now(),
        ]);

        return redirect()->to('/settings')->with('status', 'Stage eklendi.');
    }

    public function updateStage(Request $request, LeadStage $stage)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'color' => ['nullable', 'string', 'max:32'],
            'is_won' => ['required', 'boolean'],
            'is_lost' => ['required', 'boolean'],
        ]);

        if ($data['is_won'] && $data['is_lost']) {
            return redirect()->to('/settings')->with('status', 'Stage hem WON hem LOST olamaz.');
        }

        $stage->fill([
            'name' => trim($data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'color' => $data['color'] ? trim((string) $data['color']) : null,
            'is_won' => (bool) $data['is_won'],
            'is_lost' => (bool) $data['is_lost'],
        ]);
        $stage->save();

        return redirect()->to('/settings')->with('status', 'Stage güncellendi.');
    }

    public function deleteStage(Request $request, LeadStage $stage)
    {
        $stage->delete();
        return redirect()->to('/settings')->with('status', 'Stage silindi.');
    }

    public function storeIntegration(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $data = $request->validate([
            'provider' => ['required', 'in:instagram,whatsapp,telegram'],
            'name' => ['required', 'string', 'max:160'],
            'status' => ['required', 'in:active,disabled'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'config' => ['array'],
        ]);

        $cfg = $data['config'] ?? [];
        if (!is_array($cfg)) {
            $cfg = [];
        }

        // Normalize empty strings -> null
        foreach ($cfg as $k => $v) {
            if (is_string($v) && trim($v) === '') {
                $cfg[$k] = null;
            }
        }

        // Meta verify token is single-source in .env (META_VERIFY_TOKEN). Ignore any per-integration input.
        unset($cfg['verify_token']);

        // Keep secrets if record already exists and user left them empty
        $secretKeys = ['access_token', 'page_access_token', 'bot_token'];
        $existing = IntegrationAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('provider', $data['provider'])
            ->first();
        $baseCfg = is_array($existing?->config_json) ? $existing?->config_json : (json_decode((string) ($existing?->config_json ?? ''), true) ?: []);

        foreach ($secretKeys as $k) {
            if (array_key_exists($k, $cfg) && empty($cfg[$k]) && !empty($baseCfg[$k])) {
                unset($cfg[$k]); // don't overwrite existing secret with null
            }
        }

        $finalCfg = array_merge($baseCfg, $cfg);

        // Validate required fields when enabling integrations (prevents "active but cannot send/receive").
        $provider = (string) $data['provider'];
        $status = (string) $data['status'];
        if ($status === 'active') {
            if ($provider === 'instagram') {
                if (empty($finalCfg['page_id']) || empty($finalCfg['page_access_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'Instagram için active yapmadan önce IG Account ID (entry.id) ve Page Access Token girmen lazım.');
                }
            } elseif ($provider === 'whatsapp') {
                if (empty($finalCfg['phone_number_id']) || empty($finalCfg['access_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'WhatsApp için active yapmadan önce phone_number_id ve access_token girmen lazım.');
                }
            } elseif ($provider === 'telegram') {
                if (empty($finalCfg['bot_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'Telegram için active yapmadan önce bot_token girmen lazım.');
                }
            }
        }

        $values = [
            'name' => trim($data['name']),
            'status' => $data['status'],
            'config_json' => json_encode($finalCfg, JSON_UNESCAPED_UNICODE),
            'webhook_secret' => $data['webhook_secret'] ?? null,
            'updated_at' => now(),
        ];
        if (!$existing) {
            $values['created_at'] = now();
        }

        IntegrationAccount::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'provider' => $data['provider']],
            $values
        );

        return redirect()->to('/settings')->with('status', 'Entegrasyon kaydedildi.');
    }

    public function updateIntegration(Request $request, IntegrationAccount $acc)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'status' => ['required', 'in:active,disabled'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'config' => ['array'],
        ]);

        $cfg = $data['config'] ?? [];
        if (!is_array($cfg)) {
            $cfg = [];
        }
        foreach ($cfg as $k => $v) {
            if (is_string($v) && trim($v) === '') {
                $cfg[$k] = null;
            }
        }

        // Meta verify token is single-source in .env (META_VERIFY_TOKEN). Ignore any per-integration input.
        unset($cfg['verify_token']);

        // Don't wipe secrets if left blank
        $existingCfg = is_array($acc->config_json) ? $acc->config_json : (json_decode((string) ($acc->config_json ?? ''), true) ?: []);
        $secretKeys = ['access_token', 'page_access_token', 'bot_token'];
        foreach ($secretKeys as $k) {
            if (array_key_exists($k, $cfg) && empty($cfg[$k]) && !empty($existingCfg[$k])) {
                unset($cfg[$k]);
            }
        }
        $finalCfg = array_merge($existingCfg, $cfg);

        // Validate required fields when enabling integrations.
        $provider = (string) ($acc->provider ?? '');
        $status = (string) $data['status'];
        if ($status === 'active') {
            if ($provider === 'instagram') {
                if (empty($finalCfg['page_id']) || empty($finalCfg['page_access_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'Instagram için active yapmadan önce IG Account ID (entry.id) ve Page Access Token girmen lazım.');
                }
            } elseif ($provider === 'whatsapp' || $provider === 'wp') {
                if (empty($finalCfg['phone_number_id']) || empty($finalCfg['access_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'WhatsApp için active yapmadan önce phone_number_id ve access_token girmen lazım.');
                }
            } elseif ($provider === 'telegram') {
                if (empty($finalCfg['bot_token'])) {
                    return redirect()->to('/settings#settings-integrations')
                        ->with('status', 'Telegram için active yapmadan önce bot_token girmen lazım.');
                }
            }
        }

        $acc->fill([
            'name' => trim($data['name']),
            'status' => $data['status'],
            'config_json' => json_encode($finalCfg, JSON_UNESCAPED_UNICODE),
            'webhook_secret' => $data['webhook_secret'] ?? null,
        ]);
        $acc->save();

        return redirect()->to('/settings')->with('status', 'Entegrasyon güncellendi.');
    }

    public function deleteIntegration(Request $request, IntegrationAccount $acc)
    {
        $acc->delete();
        return redirect()->to('/settings')->with('status', 'Entegrasyon silindi.');
    }

    public function createIntegrationDemo(Request $request)
    {
        // Create demo integrations + demo threads/messages so you can SEE icons & channels immediately.
        $tenantId = app(\App\Support\TenantContext::class)->requireTenantId();

        DB::transaction(function () use ($tenantId) {
            // Create missing demo integrations (do NOT block if integrations already exist)
            $wa = IntegrationAccount::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'provider' => 'whatsapp'],
                [
                    'name' => 'WhatsApp Demo',
                    'status' => 'disabled',
                    'config_json' => json_encode(['phone_number_id' => 'YOUR_PHONE_NUMBER_ID', 'access_token' => 'YOUR_TOKEN'], JSON_UNESCAPED_UNICODE),
                    'webhook_secret' => null,
                    'created_at' => now(),
                ]
            );
            $ig = IntegrationAccount::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'provider' => 'instagram'],
                [
                    'name' => 'Instagram Demo',
                    'status' => 'disabled',
                    'config_json' => json_encode(['page_id' => 'YOUR_PAGE_ID', 'page_access_token' => 'YOUR_TOKEN'], JSON_UNESCAPED_UNICODE),
                    'webhook_secret' => null,
                    'created_at' => now(),
                ]
            );
            $tg = IntegrationAccount::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'provider' => 'telegram'],
                [
                    'name' => 'Telegram Demo',
                    'status' => 'disabled',
                    'config_json' => json_encode(['bot_token' => 'YOUR_BOT_TOKEN'], JSON_UNESCAPED_UNICODE),
                    'webhook_secret' => 'demo_secret_token',
                    'created_at' => now(),
                ]
            );

            // Demo contacts
            $cWaId = (int) (DB::table('contacts')->where('tenant_id', $tenantId)->where('external_id', '905555000000')->value('id') ?? 0);
            if ($cWaId <= 0) {
                $cWaId = (int) DB::table('contacts')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'WhatsApp Demo Müşteri',
                    'phone' => '905555000000',
                    'email' => null,
                    'external_id' => '905555000000',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $cIgId = (int) (DB::table('contacts')->where('tenant_id', $tenantId)->where('external_id', 'IG_DEMO_USER_ID')->value('id') ?? 0);
            if ($cIgId <= 0) {
                $cIgId = (int) DB::table('contacts')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'Instagram Demo Müşteri',
                    'phone' => null,
                    'email' => null,
                    'external_id' => 'IG_DEMO_USER_ID',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $cTgId = (int) (DB::table('contacts')->where('tenant_id', $tenantId)->where('external_id', '123456789')->value('id') ?? 0);
            if ($cTgId <= 0) {
                $cTgId = (int) DB::table('contacts')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'Telegram Demo Müşteri',
                    'phone' => null,
                    'email' => null,
                    'external_id' => '123456789',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Demo threads
            $tWaId = (int) (DB::table('threads')
                ->where('tenant_id', $tenantId)
                ->where('channel', 'whatsapp')
                ->where('integration_account_id', (int) $wa->id)
                ->where('contact_id', $cWaId)
                ->value('id') ?? 0);
            if ($tWaId <= 0) {
                $tWaId = (int) DB::table('threads')->insertGetId([
                    'tenant_id' => $tenantId,
                    'lead_id' => null,
                    'contact_id' => $cWaId,
                    'channel' => 'whatsapp',
                    'integration_account_id' => $wa->id,
                    'subject' => null,
                    'status' => 'open',
                    'last_message_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $tIgId = (int) (DB::table('threads')
                ->where('tenant_id', $tenantId)
                ->where('channel', 'instagram')
                ->where('integration_account_id', (int) $ig->id)
                ->where('contact_id', $cIgId)
                ->value('id') ?? 0);
            if ($tIgId <= 0) {
                $tIgId = (int) DB::table('threads')->insertGetId([
                    'tenant_id' => $tenantId,
                    'lead_id' => null,
                    'contact_id' => $cIgId,
                    'channel' => 'instagram',
                    'integration_account_id' => $ig->id,
                    'subject' => null,
                    'status' => 'open',
                    'last_message_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $tTgId = (int) (DB::table('threads')
                ->where('tenant_id', $tenantId)
                ->where('channel', 'telegram')
                ->where('integration_account_id', (int) $tg->id)
                ->where('contact_id', $cTgId)
                ->value('id') ?? 0);
            if ($tTgId <= 0) {
                $tTgId = (int) DB::table('threads')->insertGetId([
                    'tenant_id' => $tenantId,
                    'lead_id' => null,
                    'contact_id' => $cTgId,
                    'channel' => 'telegram',
                    'integration_account_id' => $tg->id,
                    'subject' => null,
                    'status' => 'open',
                    'last_message_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Demo messages (only if empty)
            if (!DB::table('messages')->where('tenant_id', $tenantId)->where('thread_id', $tWaId)->exists()) {
                DB::table('messages')->insert([
                    [
                        'tenant_id' => $tenantId,
                        'thread_id' => $tWaId,
                        'sender_type' => 'contact',
                        'sender_contact_id' => $cWaId,
                        'message_type' => 'text',
                        'body_text' => 'Merhaba, fiyat alabilir miyim?',
                        'created_at' => now(),
                    ],
                ]);
            }
            if (!DB::table('messages')->where('tenant_id', $tenantId)->where('thread_id', $tIgId)->exists()) {
                DB::table('messages')->insert([
                    [
                        'tenant_id' => $tenantId,
                        'thread_id' => $tIgId,
                        'sender_type' => 'contact',
                        'sender_contact_id' => $cIgId,
                        'message_type' => 'text',
                        'body_text' => 'DM’den yazıyorum, bilgi alabilir miyim?',
                        'created_at' => now(),
                    ],
                ]);
            }
            if (!DB::table('messages')->where('tenant_id', $tenantId)->where('thread_id', $tTgId)->exists()) {
                DB::table('messages')->insert([
                    [
                        'tenant_id' => $tenantId,
                        'thread_id' => $tTgId,
                        'sender_type' => 'contact',
                        'sender_contact_id' => $cTgId,
                        'message_type' => 'text',
                        'body_text' => 'Selam, müsait misiniz?',
                        'created_at' => now(),
                    ],
                ]);
            }

            DB::table('threads')->where('tenant_id', $tenantId)->whereIn('id', [$tWaId, $tIgId, $tTgId])->update(['last_message_at' => now()]);
        });

        return redirect()->to('/settings')->with('status', 'Demo entegrasyon + chat verisi oluşturuldu (disabled). Chats ekranında ikonları görürsün.');
    }

    public function saveMailSettings(Request $request, TenantSettings $ts)
    {
        $data = $request->validate([
            'smtp_host' => ['required', 'string', 'max:190'],
            'smtp_username' => ['required', 'string', 'max:190'],
            'smtp_password' => ['nullable', 'string', 'max:190'],
        ]);

        $fromEmail = trim((string) $data['smtp_username']);
        $ts->set('mail.from_email', $fromEmail);
        $ts->set('mail.from_name', 'Mark-A CRM');
        $ts->set('mail.smtp.host', trim($data['smtp_host']));
        $ts->set('mail.smtp.port', '587');
        $ts->set('mail.smtp.encryption', 'tls');
        $ts->set('mail.smtp.username', $fromEmail);
        if (!empty($data['smtp_password'])) {
            $ts->setSecret('mail.smtp.password', (string) $data['smtp_password']);
        }

        return redirect()->to('/settings')->with('status', 'Mail ayarları kaydedildi.');
    }

    public function storeStaff(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:190'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        $exists = DB::table('users')->where('email', $email)->exists();
        if ($exists) {
            return redirect()->to('/settings')->with('status', 'Bu e-posta zaten kayıtlı.');
        }

        $roleId = (int) (DB::table('roles')->where('tenant_id', $tenantId)->where('key', 'staff')->value('id') ?? 0);
        if ($roleId <= 0) {
            return redirect()->to('/settings')->with('status', 'Staff rolü bulunamadı (seed.sql).');
        }

        User::query()->create([
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'name' => trim((string) $data['name']),
            'email' => $email,
            'password' => Hash::make((string) $data['password']),
            'language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'status' => 'active',
        ]);

        return redirect()->to('/settings')->with('status', 'Çalışan eklendi.');
    }
}

