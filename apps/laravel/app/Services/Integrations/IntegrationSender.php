<?php

namespace App\Services\Integrations;

use App\Models\Contact;
use App\Models\IntegrationAccount;
use App\Models\Thread;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationSender
{
    /**
     * Send outbound text message to provider, based on integration account config.
     *
     * This is intentionally "thin" so when you put real keys in Settings,
     * it works immediately.
     */
    public function sendText(IntegrationAccount $acc, Thread $thread, Contact $contact, string $text): void
    {
        $provider = strtolower((string) $acc->provider);
        $cfg = is_array($acc->config_json) ? $acc->config_json : [];

        if ($provider === 'telegram') {
            $token = (string) ($cfg['bot_token'] ?? '');
            $chatId = (string) ($contact->external_id ?? '');
            if ($token === '' || $chatId === '') {
                throw new \RuntimeException('Telegram bot_token veya chat_id eksik.');
            }

            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            Http::timeout(6)->post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ])->throw();
            return;
        }

        if ($provider === 'whatsapp' || $provider === 'wp') {
            // WhatsApp Cloud API (Meta)
            $token = (string) ($cfg['access_token'] ?? '');
            $phoneNumberId = (string) ($cfg['phone_number_id'] ?? '');
            $to = preg_replace('/\D+/', '', (string) ($contact->phone ?? '')); // E.164 digits
            if ($token === '' || $phoneNumberId === '' || $to === '') {
                throw new \RuntimeException('WhatsApp access_token / phone_number_id / alıcı telefon eksik.');
            }

            $url = "https://graph.facebook.com/v19.0/{$phoneNumberId}/messages";
            Http::timeout(8)
                ->withToken($token)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $text],
                ])->throw();
            return;
        }

        if ($provider === 'instagram') {
            // Instagram Messaging API (Meta Graph) - MUST be sent via Facebook Page endpoint.
            // HARD RULES (policy):
            // - Use ONLY Page Access Token (no user/app token).
            // - Use ONLY /{FACEBOOK_PAGE_ID}/messages (no /me/messages, no /{IG_USER_ID}/messages).
            $pageAccessToken = (string) ($cfg['page_access_token'] ?? '');
            $pageId = (string) ($cfg['page_id'] ?? '');
            $igBusinessId = (string) ($cfg['ig_business_id'] ?? '');
            $recipientId = (string) ($contact->external_id ?? '');
            if ($recipientId === '') {
                // legacy fallback (if external_id was not populated but instagram_user_id was)
                $recipientId = (string) ($contact->instagram_user_id ?? '');
            }
            if ($pageAccessToken === '' || $pageId === '' || $recipientId === '') {
                if ($pageAccessToken === '' && $recipientId === '') {
                    throw new \RuntimeException('Instagram gönderim hatası: Page Access Token ve recipient id eksik. Settings > Entegrasyonlar > Instagram alanını doldur ve sohbeti IG’den tekrar başlat.');
                }
                if ($pageAccessToken === '') {
                    throw new \RuntimeException('Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.');
                }
                if ($pageId === '') {
                    throw new \RuntimeException('Instagram gönderim hatası: Facebook Page ID eksik. Settings > Entegrasyonlar > Instagram > Facebook Page ID girip Kaydet.');
                }
                throw new \RuntimeException('Instagram gönderim hatası: recipient id eksik (contact external_id/instagram_user_id boş).');
            }

            // Recipient id MUST be the Instagram user ID from webhook sender.id (the person you chat with),
            // NOT the business account id.
            if ($igBusinessId !== '' && $recipientId === $igBusinessId) {
                throw new \RuntimeException('Instagram gönderim hatası: recipient id yanlış (IG Business ID). Alıcı, müşterinin sender.id değeridir.');
            }
            if ($recipientId === $pageId) {
                throw new \RuntimeException('Instagram gönderim hatası: recipient id yanlış (Facebook Page ID). Alıcı, müşterinin sender.id değeridir.');
            }

            $url = "https://graph.facebook.com/v19.0/{$pageId}/messages";
            $res = Http::timeout(8)
                ->withToken($pageAccessToken)
                ->post($url, [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $text],
                ]);

            if (!$res->ok()) {
                $err = $this->formatMetaGraphError($res->json());
                Log::warning('instagram.send_failed', [
                    'status' => $res->status(),
                    'error' => $err,
                ]);
                throw new \RuntimeException($err);
            }
            return;
        }

        throw new \RuntimeException('Desteklenmeyen provider: ' . $provider);
    }

    /**
     * Convert Graph API error payload into a stable, human-readable message.
     * No retries here (human-agent CRM).
     */
    private function formatMetaGraphError($json): string
    {
        if (!is_array($json)) {
            return 'Instagram gönderim hatası: Graph API bilinmeyen hata (non-JSON).';
        }
        $e = $json['error'] ?? null;
        if (!is_array($e)) {
            return 'Instagram gönderim hatası: Graph API bilinmeyen hata.';
        }

        $msg = (string) ($e['message'] ?? '');
        $code = (int) ($e['code'] ?? 0);
        $sub = (int) ($e['error_subcode'] ?? 0);

        // Required special cases
        if ($code === 100 && str_contains($msg, 'Missing Permission')) {
            return 'Instagram gönderim hatası: (#100) Missing Permission. Meta App izinlerini kontrol et (instagram_manage_messages / pages_manage_metadata vb.).';
        }
        if (str_contains($msg, 'Invalid OAuth access token') || str_contains($msg, 'OAuthException')) {
            return 'Instagram gönderim hatası: Page Access Token geçersiz veya süresi dolmuş. Yeni Page token üretip Settings’e gir.';
        }
        if (str_contains($msg, 'Unsupported get request')) {
            return 'Instagram gönderim hatası: Unsupported get request. Page/Recipient ID yanlış veya erişim yok.';
        }
        if (str_contains($msg, 'Tried accessing nonexisting field') && str_contains($msg, 'messages')) {
            return 'Instagram gönderim hatası: IGUser üzerinde messages alanına erişim denendi (yanlış endpoint). Sadece /{PAGE_ID}/messages kullanılmalı.';
        }

        $suffix = ($code || $sub) ? " (code:{$code} sub:{$sub})" : '';
        return 'Instagram gönderim hatası: ' . ($msg !== '' ? $msg : 'Graph API error') . $suffix;
    }
}

