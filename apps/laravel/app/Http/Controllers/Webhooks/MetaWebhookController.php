<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Thread;
use App\Models\WebhookEvent;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetaWebhookController extends Controller
{
    /**
     * Meta Webhooks:
     * - GET: verification (hub.challenge)
     * - POST: WhatsApp Cloud / Instagram Messaging events
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
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode'));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge'));

        if (strtolower($mode) !== 'subscribe' || $token === '' || $challenge === '') {
            return response('Bad Request', 400);
        }

        // Find any integration account that has this verify_token in config_json
        $acc = DB::table('integration_accounts')
            ->whereIn('provider', ['whatsapp', 'wp', 'instagram'])
            ->where('status', 'active')
            ->get()
            ->first(function ($row) use ($token) {
                $cfg = json_decode((string) ($row->config_json ?? '{}'), true);
                return is_array($cfg) && (string) ($cfg['verify_token'] ?? '') === $token;
            });

        if (!$acc) {
            return response('Forbidden', 403);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $ctx->setResolved((int) $acc->tenant_id, TenantContext::PANEL_TENANT, (string) $request->getHost());

        return response($challenge, 200);
    }

    private function ingest(Request $request)
    {
        $payload = $request->all();

        // Try to determine provider/account from payload:
        // - WhatsApp Cloud: value.metadata.phone_number_id
        // - Instagram: entry.messaging[].recipient.id (page id)
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
        }

        if (!$acc) {
            // Still return 200 so Meta doesn't disable the webhook; keep payload for debugging.
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = (int) $acc->tenant_id;
        $ctx->setResolved($tenantId, TenantContext::PANEL_TENANT, (string) $request->getHost());

        // Persist raw event
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
            $this->ingestInstagram($tenantId, (int) $acc->id, $payload);
        }

        return response()->json(['ok' => true]);
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

                $thread = Thread::query()
                    ->where('tenant_id', $tenantId)
                    ->where('channel', 'whatsapp')
                    ->where('integration_account_id', $integrationAccountId)
                    ->where('contact_id', $contact->id)
                    ->first();

                if (!$thread) {
                    $thread = Thread::query()->create([
                        'contact_id' => $contact->id,
                        'channel' => 'whatsapp',
                        'integration_account_id' => $integrationAccountId,
                        'status' => 'open',
                        'created_at' => now(),
                    ]);
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

    private function ingestInstagram(int $tenantId, int $integrationAccountId, array $payload): void
    {
        $entry = (array) data_get($payload, 'entry.0', []);
        $messaging = (array) ($entry['messaging'] ?? []);

        foreach ($messaging as $ev) {
            $senderId = (string) data_get($ev, 'sender.id', '');
            $text = (string) data_get($ev, 'message.text', '');
            if ($senderId === '' || $text === '') {
                continue;
            }

            DB::transaction(function () use ($tenantId, $integrationAccountId, $senderId, $text, $ev) {
                $contact = Contact::query()
                    ->where('tenant_id', $tenantId)
                    ->where('external_id', $senderId)
                    ->first();
                if (!$contact) {
                    $contact = Contact::query()->create([
                        'name' => 'Instagram User ' . substr($senderId, -6),
                        'external_id' => $senderId,
                        'created_at' => now(),
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
                        'channel' => 'instagram',
                        'integration_account_id' => $integrationAccountId,
                        'status' => 'open',
                        'created_at' => now(),
                    ]);
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
}

