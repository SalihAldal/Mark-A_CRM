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

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Telegram can send this header when webhook set with secret_token
        $secret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        $acc = DB::table('integration_accounts')
            ->where('provider', 'telegram')
            ->where('status', 'active')
            ->when($secret !== '', function ($q) use ($secret) {
                $q->where('webhook_secret', $secret);
            })
            ->orderByDesc('id')
            ->first();

        if (!$acc) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = (int) $acc->tenant_id;
        $ctx->setResolved($tenantId, TenantContext::PANEL_TENANT, (string) $request->getHost());

        WebhookEvent::query()->create([
            'provider' => 'telegram',
            'integration_account_id' => (int) $acc->id,
            'direction' => 'in',
            'signature_valid' => $secret !== '' ? 1 : 0,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'received_at' => now(),
        ]);

        $text = (string) data_get($payload, 'message.text', '');
        $chatId = (string) data_get($payload, 'message.chat.id', '');
        if ($text === '' || $chatId === '') {
            return response()->json(['ok' => true]);
        }

        $name = trim((string) (
            data_get($payload, 'message.from.first_name', '') . ' ' . data_get($payload, 'message.from.last_name', '')
        ));
        if ($name === '') {
            $name = 'Telegram User ' . substr($chatId, -6);
        }

        DB::transaction(function () use ($tenantId, $acc, $chatId, $name, $text, $payload) {
            $contact = Contact::query()
                ->where('tenant_id', $tenantId)
                ->where('external_id', $chatId)
                ->first();
            if (!$contact) {
                $contact = Contact::query()->create([
                    'name' => $name,
                    'external_id' => $chatId,
                    'created_at' => now(),
                ]);
            }

            $thread = Thread::query()
                ->where('tenant_id', $tenantId)
                ->where('channel', 'telegram')
                ->where('integration_account_id', (int) $acc->id)
                ->where('contact_id', $contact->id)
                ->first();
            if (!$thread) {
                $thread = Thread::query()->create([
                    'contact_id' => $contact->id,
                    'channel' => 'telegram',
                    'integration_account_id' => (int) $acc->id,
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
                'metadata_json' => json_encode(['provider' => 'telegram', 'raw' => $payload], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);

            $thread->last_message_at = now();
            $thread->save();
        });

        return response()->json(['ok' => true]);
    }
}

