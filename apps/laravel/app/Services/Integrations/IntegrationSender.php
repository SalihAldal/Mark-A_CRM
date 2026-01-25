<?php

namespace App\Services\Integrations;

use App\Models\Contact;
use App\Models\IntegrationAccount;
use App\Models\Thread;
use Illuminate\Support\Facades\Http;

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
            // Instagram Messaging API (Meta Graph) - requires a Page access token
            $pageAccessToken = (string) ($cfg['page_access_token'] ?? '');
            $recipientId = (string) ($contact->external_id ?? '');
            if ($recipientId === '') {
                // legacy fallback (if external_id was not populated but instagram_user_id was)
                $recipientId = (string) ($contact->instagram_user_id ?? '');
            }
            if ($pageAccessToken === '' || $recipientId === '') {
                if ($pageAccessToken === '' && $recipientId === '') {
                    throw new \RuntimeException('Instagram gönderim hatası: Page Access Token ve recipient id eksik. Settings > Entegrasyonlar > Instagram alanını doldur ve sohbeti IG’den tekrar başlat.');
                }
                if ($pageAccessToken === '') {
                    throw new \RuntimeException('Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.');
                }
                throw new \RuntimeException('Instagram gönderim hatası: recipient id eksik (contact external_id/instagram_user_id boş).');
            }

            $url = "https://graph.facebook.com/v19.0/me/messages";
            Http::timeout(8)
                ->withToken($pageAccessToken)
                ->post($url, [
                    'messaging_type' => 'RESPONSE',
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $text],
                ])->throw();
            return;
        }

        throw new \RuntimeException('Desteklenmeyen provider: ' . $provider);
    }
}

