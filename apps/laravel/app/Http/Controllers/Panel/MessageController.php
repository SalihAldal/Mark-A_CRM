<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\IntegrationAccount;
use App\Models\Message;
use App\Models\Thread;
use App\Services\LeadScoringService;
use App\Services\Integrations\IntegrationSender;
use App\Services\RealtimeGateway;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function sendText(Request $request, Thread $thread, RealtimeGateway $gateway, LeadScoringService $scoring)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $msg = Message::query()->create([
            'thread_id' => $thread->id,
            'sender_type' => 'user',
            'sender_user_id' => $request->user()->id,
            'message_type' => 'text',
            'body_text' => $data['text'],
            'created_at' => now(),
        ]);

        $thread->last_message_at = now();
        $thread->save();

        // Outbound integration send (WhatsApp/Instagram/Telegram) if this thread is linked to an integration account
        $deliveryError = null;
        try {
            if ($thread->integration_account_id && in_array((string) $thread->channel, ['whatsapp', 'wp', 'instagram', 'telegram'], true)) {
                $acc = IntegrationAccount::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', (int) $thread->integration_account_id)
                    ->where('status', 'active')
                    ->first();

                if ($acc && $thread->contact_id) {
                    $contact = Contact::query()->where('tenant_id', $tenantId)->find((int) $thread->contact_id);
                    if ($contact) {
                        app(IntegrationSender::class)->sendText($acc, $thread, $contact, (string) $data['text']);
                    }
                }
            }
        } catch (\Throwable $e) {
            $deliveryError = $e->getMessage();
            // Keep local CRM message even if provider send fails
            $msg->metadata_json = json_encode(['delivery_error' => $deliveryError], JSON_UNESCAPED_UNICODE);
            $msg->save();
        }

        if ($thread->lead_id) {
            $newScore = $scoring->recalcForLead($tenantId, (int) $thread->lead_id);
            $gateway->broadcast($tenantId, [
                ['type' => 'tenant', 'id' => $tenantId],
            ], 'lead.score_updated', [
                'lead_id' => (int) $thread->lead_id,
                'score' => $newScore,
            ]);
        }

        $gateway->broadcast($tenantId, [
            ['type' => 'thread', 'id' => (int) $thread->id],
        ], 'chat.message_created', [
            'thread_id' => (int) $thread->id,
            'message' => [
                'id' => (int) $msg->id,
                'type' => 'text',
                'text' => $msg->body_text,
                'sender_type' => $msg->sender_type,
                'created_at' => (string) $msg->created_at,
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message_id' => (int) $msg->id,
                'delivery_error' => $deliveryError,
            ]);
        }

        if ($deliveryError) {
            return redirect()->to('/chats?thread=' . $thread->id)->with('status', 'Mesaj kaydedildi ama entegrasyon gönderimi başarısız: ' . $deliveryError);
        }
        return redirect()->to('/chats?thread=' . $thread->id);
    }

    public function sendFile(Request $request, Thread $thread, RealtimeGateway $gateway, LeadScoringService $scoring)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:25600'], // 25MB
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $file = $data['file'];
        $mime = (string) ($file->getMimeType() ?? '');
        $origExt = strtolower($file->getClientOriginalExtension() ?: '');

        $blockedExt = ['php', 'phtml', 'phar', 'htaccess', 'js', 'html', 'htm', 'svg'];
        if ($origExt !== '' && in_array($origExt, $blockedExt, true)) {
            return response()->json(['ok' => false, 'error' => 'Dosya tipi izinli değil.'], 422);
        }
        if (str_contains($mime, 'php') || str_contains($mime, 'x-httpd-php')) {
            return response()->json(['ok' => false, 'error' => 'Dosya tipi izinli değil.'], 422);
        }

        $uuid = (string) Str::uuid();
        $allowedImageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowedFileExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar', '7z', 'mp3', 'wav', 'mp4', 'webm'];

        $ext = 'bin';
        if ($origExt !== '' && in_array($origExt, $allowedImageExt, true) && str_starts_with($mime, 'image/')) {
            $ext = $origExt;
        } elseif ($origExt !== '' && in_array($origExt, $allowedFileExt, true)) {
            $ext = $origExt;
        }

        $relative = "chat/files/{$tenantId}/{$thread->id}/{$uuid}.{$ext}";

        $file->storeAs(
            "chat/files/{$tenantId}/{$thread->id}",
            "{$uuid}.{$ext}",
            'public'
        );

        $publicPath = '/storage/' . $relative;

        $type = (str_starts_with($mime, 'image/') && in_array($ext, $allowedImageExt, true)) ? 'image' : 'file';

        $msg = Message::query()->create([
            'thread_id' => $thread->id,
            'sender_type' => 'user',
            'sender_user_id' => $request->user()->id,
            'message_type' => $type,
            'file_path' => $publicPath,
            'file_mime' => $mime,
            'file_size' => $file->getSize(),
            'created_at' => now(),
        ]);

        $thread->last_message_at = now();
        $thread->save();

        if ($thread->lead_id) {
            $newScore = $scoring->recalcForLead($tenantId, (int) $thread->lead_id);
            $gateway->broadcast($tenantId, [
                ['type' => 'tenant', 'id' => $tenantId],
            ], 'lead.score_updated', [
                'lead_id' => (int) $thread->lead_id,
                'score' => $newScore,
            ]);
        }

        $gateway->broadcast($tenantId, [
            ['type' => 'thread', 'id' => (int) $thread->id],
        ], 'chat.message_created', [
            'thread_id' => (int) $thread->id,
            'message' => [
                'id' => (int) $msg->id,
                'type' => $type,
                'file_path' => $publicPath,
                'file_mime' => $msg->file_mime,
                'file_size' => (int) ($msg->file_size ?? 0),
                'sender_type' => $msg->sender_type,
                'created_at' => (string) $msg->created_at,
            ],
        ]);

        return redirect()->to('/chats?thread=' . $thread->id);
    }

    public function sendVoice(Request $request, Thread $thread, RealtimeGateway $gateway, LeadScoringService $scoring)
    {
        $data = $request->validate([
            'voice' => ['required', 'file', 'max:25600', 'mimetypes:audio/webm,video/webm', 'mimes:webm'], // 25MB
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $file = $data['voice'];
        $uuid = (string) Str::uuid();
        $relative = "chat/voice/{$tenantId}/{$thread->id}/{$uuid}.webm";

        Storage::disk('public')->put($relative, file_get_contents($file->getRealPath()));
        $publicPath = '/storage/' . $relative; // İstenen: /storage/chat/voice/{tenant_id}/{thread_id}/{uuid}.webm

        $msg = Message::query()->create([
            'thread_id' => $thread->id,
            'sender_type' => 'user',
            'sender_user_id' => $request->user()->id,
            'message_type' => 'voice',
            'file_path' => $publicPath,
            'file_mime' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'voice_duration_ms' => $data['duration_ms'] ?? null,
            'created_at' => now(),
        ]);

        $thread->last_message_at = now();
        $thread->save();

        if ($thread->lead_id) {
            $newScore = $scoring->recalcForLead($tenantId, (int) $thread->lead_id);
            $gateway->broadcast($tenantId, [
                ['type' => 'tenant', 'id' => $tenantId],
            ], 'lead.score_updated', [
                'lead_id' => (int) $thread->lead_id,
                'score' => $newScore,
            ]);
        }

        $gateway->broadcast($tenantId, [
            ['type' => 'thread', 'id' => (int) $thread->id],
        ], 'chat.message_created', [
            'thread_id' => (int) $thread->id,
            'message' => [
                'id' => (int) $msg->id,
                'type' => 'voice',
                'file_path' => $publicPath,
                'duration_ms' => (int) ($msg->voice_duration_ms ?? 0),
                'sender_type' => $msg->sender_type,
                'created_at' => (string) $msg->created_at,
            ],
        ]);

        return redirect()->to('/chats?thread=' . $thread->id);
    }
}

