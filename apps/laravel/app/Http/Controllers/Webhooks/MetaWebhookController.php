<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Message;
use App\Models\Thread;
use App\Models\WebhookEvent;
use App\Services\Integrations\InstagramGraphService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaWebhookController extends Controller
{
    /**
     * Meta Webhooks:
     *
     * Verification flow (GET):
     * - Meta calls your endpoint with query params:
     *   - hub.mode
     *   - hub.verify_token
     *   - hub.challenge
     * - If verify_token matches what you set in Meta, you MUST respond with:
     *   - HTTP 200
     *   - plain-text body that contains ONLY hub.challenge (no JSON, no extra text)
     * - Otherwise respond with HTTP 403.
     *
     * Event delivery flow (POST):
     * - Meta delivers Instagram/WhatsApp events as JSON.
     * - You MUST respond quickly with:
     *   - HTTP 200
     *   - plain-text "EVENT_RECEIVED"
     * - Do not auto-reply (this is CRM + human agent use-case).
     */
    public function handle(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        return $this->ingest($request);
    }

    private function verify(Request $request)
    {
        // NOTE:
        // Laravel treats dots as "array access" in helpers like Request::query('a.b').
        // Meta sends literal keys like "hub.mode". To read them reliably, use the raw parameter bag.
        $mode = (string) ($request->query->get('hub.mode') ?? $request->query->get('hub_mode') ?? '');
        $token = (string) ($request->query->get('hub.verify_token') ?? $request->query->get('hub_verify_token') ?? '');
        $challenge = (string) ($request->query->get('hub.challenge') ?? $request->query->get('hub_challenge') ?? '');

        $verifyToken = (string) config('services.meta.verify_token');

        if (strtolower($mode) !== 'subscribe' || $token === '' || $challenge === '' || $verifyToken === '') {
            return response('Forbidden', 403)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        // Single source of truth verification:
        // Meta will only accept the endpoint if verify_token matches what you set in the Meta dashboard.
        if (!hash_equals($verifyToken, $token)) {
            return response('Forbidden', 403)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        // We don't need to resolve tenant-by-host for verification response.
        // Keep response minimal as Meta expects.
        return response($challenge, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function ingest(Request $request)
    {
        // Always ACK fast (Meta will retry and may disable the webhook if you don't respond 200).
        // We still try to process and persist events, but failures must not change the response.
        try {
            $payload = $request->json()->all();
            if (!is_array($payload) || $payload === []) {
                $payload = $request->all();
            }

            // Log safely (full payload can be large; we truncate for file logs).
            $raw = (string) $request->getContent();
            Log::info('meta.webhook.received', [
                'method' => $request->method(),
                'path' => $request->path(),
                'host' => $request->getHost(),
                'content_type' => $request->headers->get('content-type'),
                'raw_truncated' => mb_substr($raw, 0, 20000),
            ]);

            // Try to determine provider/account from payload:
            // - WhatsApp Cloud: entry[0].changes[0].value.metadata.phone_number_id
            // - Instagram: entry[0].id (page id) OR entry[0].messaging[0].recipient.id
            $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');
            $pageId = data_get($payload, 'entry.0.id') ?: data_get($payload, 'entry.0.messaging.0.recipient.id');

            $acc = null;
            $provider = null;

            if ($phoneNumberId) {
                $provider = 'whatsapp';
                $acc = DB::table('integration_accounts')
                    ->whereIn('provider', ['whatsapp', 'wp'])
                    ->where('status', 'active')
                    ->get()
                    ->first(function ($row) use ($phoneNumberId) {
                        $cfg = json_decode((string) ($row->config_json ?? '{}'), true);
                        return is_array($cfg) && (string) ($cfg['phone_number_id'] ?? '') === (string) $phoneNumberId;
                    });
            } elseif ($pageId) {
                $provider = 'instagram';
                $acc = DB::table('integration_accounts')
                    ->where('provider', 'instagram')
                    ->where('status', 'active')
                    ->get()
                    ->first(function ($row) use ($pageId) {
                        $cfg = json_decode((string) ($row->config_json ?? '{}'), true);
                        return is_array($cfg) && (string) ($cfg['page_id'] ?? '') === (string) $pageId;
                    });

            // Production-safe fallback (helps initial setup mistakes):
            // If there is EXACTLY ONE active Instagram integration in the whole system and it has no page_id yet,
            // bind it automatically to the incoming entry.id so we can start ingesting messages.
            // This avoids a "webhook received but nothing visible in Chats" situation.
            if (!$acc) {
                $activeIg = DB::table('integration_accounts')
                    ->where('provider', 'instagram')
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->get();

                if ($activeIg->count() === 1) {
                    $only = $activeIg->first();
                    $cfg = json_decode((string) ($only->config_json ?? '{}'), true);
                    if (!is_array($cfg)) {
                        $cfg = [];
                    }
                    $existingPageId = (string) ($cfg['page_id'] ?? '');
                    if ($existingPageId === '') {
                        $cfg['page_id'] = (string) $pageId;
                        DB::table('integration_accounts')
                            ->where('id', (int) $only->id)
                            ->update([
                                'config_json' => json_encode($cfg, JSON_UNESCAPED_UNICODE),
                                'updated_at' => now(),
                            ]);
                        $acc = $only;

                        Log::warning('meta.webhook.auto_bound_instagram_page_id', [
                            'integration_account_id' => (int) $only->id,
                            'tenant_id' => (int) $only->tenant_id,
                            'page_id' => (string) $pageId,
                        ]);
                    }
                }
            }
            }

            if (!$acc) {
                Log::warning('meta.webhook.unmatched_account', [
                    'provider_guess' => $provider,
                    'phone_number_id' => $phoneNumberId ? (string) $phoneNumberId : null,
                    'page_id' => $pageId ? (string) $pageId : null,
                ]);
                // Still ACK; keep payload only in logs for debugging.
                return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            /** @var TenantContext $ctx */
            $ctx = app(TenantContext::class);
            $tenantId = (int) $acc->tenant_id;
            $ctx->setResolved($tenantId, TenantContext::PANEL_TENANT, (string) $request->getHost());

            Log::info('meta.webhook.matched_account', [
                'provider' => $provider,
                'tenant_id' => $tenantId,
                'integration_account_id' => (int) $acc->id,
            ]);

            // Persist raw event (DB holds the full payload).
            WebhookEvent::query()->create([
                'provider' => $provider ?: 'meta',
                'integration_account_id' => (int) $acc->id,
                'direction' => 'in',
                'signature_valid' => 0,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'received_at' => now(),
            ]);

            if ($provider === 'whatsapp') {
                $this->ingestWhatsApp($tenantId, (int) $acc->id, $payload);
            } elseif ($provider === 'instagram') {
                $cfg = json_decode((string) ($acc->config_json ?? '{}'), true);
                if (!is_array($cfg)) {
                    $cfg = [];
                }
                $pageAccessToken = (string) ($cfg['page_access_token'] ?? '');
                $this->ingestInstagram($tenantId, (int) $acc->id, $pageAccessToken, $payload);
            }

            return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        } catch (Throwable $e) {
            Log::error('meta.webhook.error', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    private function ingestWhatsApp(int $tenantId, int $integrationAccountId, array $payload): void
    {
        $value = data_get($payload, 'entry.0.changes.0.value', []);
        $messages = (array) ($value['messages'] ?? []);
        $contacts = (array) ($value['contacts'] ?? []);

        $profileName = (string) data_get($contacts, '0.profile.name', '');
        foreach ($messages as $m) {
            $from = (string) ($m['from'] ?? ''); // phone number (digits)
            $text = (string) data_get($m, 'text.body', '');
            if ($from === '' || $text === '') {
                continue;
            }

            DB::transaction(function () use ($tenantId, $integrationAccountId, $from, $profileName, $text, $m) {
                $contact = Contact::query()
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($from) {
                        $q->where('phone', $from)->orWhere('external_id', $from);
                    })
                    ->first();

                if (!$contact) {
                    $contact = Contact::query()->create([
                        'name' => $profileName !== '' ? $profileName : ('WhatsApp ' . $from),
                        'phone' => $from,
                        'external_id' => $from,
                        'created_at' => now(),
                    ]);
                }

                // Ensure a Lead exists for this contact so tenant_admin can see it and staff can "claim" it.
                $leadId = $this->ensureLeadForContact($tenantId, $contact, 'whatsapp');

                $thread = Thread::query()
                    ->where('tenant_id', $tenantId)
                    ->where('channel', 'whatsapp')
                    ->where('integration_account_id', $integrationAccountId)
                    ->where('contact_id', $contact->id)
                    ->first();

                if (!$thread) {
                    $thread = Thread::query()->create([
                        'contact_id' => $contact->id,
                        'lead_id' => $leadId,
                        'channel' => 'whatsapp',
                        'integration_account_id' => $integrationAccountId,
                        'status' => 'open',
                        'created_at' => now(),
                    ]);
                } elseif (!$thread->lead_id && $leadId) {
                    $thread->lead_id = $leadId;
                    $thread->save();
                }

                Message::query()->create([
                    'thread_id' => $thread->id,
                    'sender_type' => 'contact',
                    'sender_contact_id' => $contact->id,
                    'message_type' => 'text',
                    'body_text' => $text,
                    'metadata_json' => json_encode(['provider' => 'whatsapp', 'raw' => $m], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);

                $thread->last_message_at = now();
                $thread->save();
            });
        }
    }

    private function ingestInstagram(int $tenantId, int $integrationAccountId, string $pageAccessToken, array $payload): void
    {
        $entry = (array) data_get($payload, 'entry.0', []);
        $messaging = (array) ($entry['messaging'] ?? []);

        foreach ($messaging as $ev) {
            $senderId = (string) data_get($ev, 'sender.id', '');
            $text = (string) data_get($ev, 'message.text', '');
            if ($senderId === '' || $text === '') {
                continue;
            }

            // Caching:
            // - Fetch profile on first message (contact does not exist).
            // - Also fetch ONCE later if legacy contact exists but username/name not cached yet.
            //   (throttled using updated_at; we "touch" contact on failed attempt to avoid spamming Graph API)
            $existing = Contact::query()
                ->where('tenant_id', $tenantId)
                ->where('external_id', $senderId)
                ->first();

            $shouldFetch = false;
            if (!$existing) {
                $shouldFetch = true;
            } else {
                $isInstagram = ((string) ($existing->provider ?? '')) === 'instagram' || empty($existing->provider);
                $missingCache = empty($existing->username) && (empty($existing->profile_picture));
                $looksFallback = is_string($existing->name) && str_starts_with((string) $existing->name, 'Instagram User ');
                // throttle: at most once per 12 hours per contact
                $stale = !$existing->updated_at || $existing->updated_at->lt(now()->subHours(12));
                if ($isInstagram && ($missingCache || $looksFallback) && $stale) {
                    $shouldFetch = true;
                }
            }

            $profileRes = null;
            $attempted = false;
            if ($shouldFetch) {
                if (trim((string) $pageAccessToken) === '') {
                    Log::warning('meta.ig.profile_skip_no_token', [
                        'tenant_id' => $tenantId,
                        'integration_account_id' => $integrationAccountId,
                        'ig_user_id' => $senderId,
                    ]);
                } else {
                    $attempted = true;
                    $profileRes = app(InstagramGraphService::class)->fetchUserProfile($senderId, $pageAccessToken);
                    if (!$profileRes['ok']) {
                        Log::warning('meta.ig.profile_fetch_failed', [
                            'tenant_id' => $tenantId,
                            'integration_account_id' => $integrationAccountId,
                            'ig_user_id' => $senderId,
                            'error' => $profileRes['error'] ?? null,
                            'http_status' => $profileRes['http_status'] ?? null,
                        ]);
                    } else {
                        Log::info('meta.ig.profile_fetched', [
                            'tenant_id' => $tenantId,
                            'integration_account_id' => $integrationAccountId,
                            'ig_user_id' => $senderId,
                            'has_username' => !empty(data_get($profileRes, 'profile.username')),
                            'has_name' => !empty(data_get($profileRes, 'profile.name')),
                            'has_pic' => !empty(data_get($profileRes, 'profile.profile_picture')),
                        ]);
                    }
                }
            }

            DB::transaction(function () use ($tenantId, $integrationAccountId, $senderId, $text, $ev, $existing, $profileRes, $attempted) {
                $contact = $existing ?: Contact::query()
                    ->where('tenant_id', $tenantId)
                    ->where('external_id', $senderId)
                    ->first();

                $profile = (is_array($profileRes) && ($profileRes['ok'] ?? false)) ? ($profileRes['profile'] ?? null) : null;

                if (!$contact) {
                    $fallbackName = 'Instagram User ' . substr($senderId, -6);
                    $realName = $profile && !empty($profile['name']) ? (string) $profile['name'] : null;
                    $username = $profile && !empty($profile['username']) ? (string) $profile['username'] : null;
                    $pic = $profile && !empty($profile['profile_picture']) ? (string) $profile['profile_picture'] : null;

                    // Fallback display name:
                    // - If name exists -> use it
                    // - Else -> "Instagram User ..."
                    $displayName = $realName ?: $fallbackName;

                    $contact = Contact::query()->create([
                        'name' => $displayName,
                        'external_id' => $senderId,
                        'provider' => 'instagram',
                        'instagram_user_id' => $senderId,
                        'username' => $username,
                        'profile_picture' => $pic,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Backfill / update cache for legacy contacts if we fetched profile now.
                    $dirty = false;
                    if (empty($contact->provider)) {
                        $contact->provider = 'instagram';
                        $dirty = true;
                    }
                    if (empty($contact->instagram_user_id)) {
                        $contact->instagram_user_id = $senderId;
                        $dirty = true;
                    }
                    if ($profile) {
                        if (!empty($profile['username']) && empty($contact->username)) {
                            $contact->username = (string) $profile['username'];
                            $dirty = true;
                        }
                        if (!empty($profile['profile_picture']) && empty($contact->profile_picture)) {
                            $contact->profile_picture = (string) $profile['profile_picture'];
                            $dirty = true;
                        }
                        $looksFallback = is_string($contact->name) && str_starts_with((string) $contact->name, 'Instagram User ');
                        if (!empty($profile['name']) && ($looksFallback || empty($contact->name))) {
                            $contact->name = (string) $profile['name'];
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $contact->save();
                    } elseif ($attempted) {
                        // We attempted profile fetch but couldn't enrich anything (permissions etc).
                        // Touch contact to throttle retries (prevents calling Graph API on every message).
                        $contact->touch();
                    }
                }

                // Ensure a Lead exists for this contact so tenant_admin can see it and staff can "claim" it.
                $leadId = $this->ensureLeadForContact($tenantId, $contact, 'instagram');
                // If lead exists from legacy fallback name, keep it in sync with enriched contact name.
                if ($leadId && !empty($contact->name)) {
                    DB::table('leads')
                        ->where('tenant_id', $tenantId)
                        ->where('id', (int) $leadId)
                        ->where(function ($q) {
                            $q->whereNull('name')->orWhere('name', 'like', 'Instagram User %');
                        })
                        ->update([
                            'name' => (string) $contact->name,
                            'updated_at' => now(),
                        ]);
                }

                $thread = Thread::query()
                    ->where('tenant_id', $tenantId)
                    ->where('channel', 'instagram')
                    ->where('integration_account_id', $integrationAccountId)
                    ->where('contact_id', $contact->id)
                    ->first();
                if (!$thread) {
                    $thread = Thread::query()->create([
                        'contact_id' => $contact->id,
                        'lead_id' => $leadId,
                        'channel' => 'instagram',
                        'integration_account_id' => $integrationAccountId,
                        'status' => 'open',
                        'created_at' => now(),
                    ]);
                } elseif (!$thread->lead_id && $leadId) {
                    $thread->lead_id = $leadId;
                    $thread->save();
                }

                Message::query()->create([
                    'thread_id' => $thread->id,
                    'sender_type' => 'contact',
                    'sender_contact_id' => $contact->id,
                    'message_type' => 'text',
                    'body_text' => $text,
                    'metadata_json' => json_encode(['provider' => 'instagram', 'raw' => $ev], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);

                $thread->last_message_at = now();
                $thread->save();
            });
        }
    }

    /**
     * Create (or reuse) a lead for an inbound integration chat contact.
     *
     * Why:
     * - Tenant admins must see inbound conversations as Leads.
     * - Staff can claim the lead from Notifications and then see the chat (RBAC filters).
     *
     * NO auto-replies here (human agent CRM).
     */
    private function ensureLeadForContact(int $tenantId, Contact $contact, string $source): ?int
    {
        // Reuse existing lead if already linked to this contact.
        $existingLeadId = (int) (Lead::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', (int) $contact->id)
            ->orderByDesc('id')
            ->value('id') ?? 0);
        if ($existingLeadId > 0) {
            return $existingLeadId;
        }

        // Pick a default stage (first non-won/non-lost stage).
        $stageId = (int) (LeadStage::query()
            ->where('tenant_id', $tenantId)
            ->where('is_won', 0)
            ->where('is_lost', 0)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id') ?? 0);

        $lead = Lead::query()->create([
            'owner_user_id' => null,
            'assigned_user_id' => null,
            'contact_id' => (int) $contact->id,
            'stage_id' => $stageId > 0 ? $stageId : null,
            'source' => $source,
            'status' => 'open',
            'score' => 0,
            'name' => (string) ($contact->name ?? 'Yeni Lead'),
            'phone' => $contact->phone ? (string) $contact->phone : null,
            'email' => $contact->email ? (string) $contact->email : null,
            'company' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Notify staff so they can claim/assign and then access chats per RBAC rules.
        $staffIds = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->where('u.tenant_id', $tenantId)
            ->where('r.tenant_id', $tenantId)
            ->where('r.key', 'staff')
            ->pluck('u.id')
            ->map(fn ($x) => (int) $x)
            ->values()
            ->all();
        if (!empty($staffIds)) {
            $now = now();
            $rows = [];
            foreach ($staffIds as $uid) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'user_id' => $uid,
                    'type' => 'lead_created',
                    'title' => 'Yeni lead (entegrasyon)',
                    'body' => $lead->name . ' • ' . $lead->source . ' • Durum: ' . $lead->status,
                    'entity_type' => 'lead',
                    'entity_id' => (int) $lead->id,
                    'is_read' => 0,
                    'read_at' => null,
                    'created_at' => $now,
                ];
            }
            DB::table('notifications')->insert($rows);
        }

        // Audit (system actor)
        DB::table('audit_logs')->insert([
            'tenant_id' => $tenantId,
            'actor_user_id' => null,
            'action' => 'lead.create_webhook',
            'entity_type' => 'lead',
            'entity_id' => (int) $lead->id,
            'ip' => null,
            'user_agent' => null,
            'metadata_json' => json_encode([
                'source' => $source,
                'contact_id' => (int) $contact->id,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);

        return (int) $lead->id;
    }
}

