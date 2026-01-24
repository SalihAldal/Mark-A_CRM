<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RealtimeGateway
{
    public function broadcast(int $tenantId, array $rooms, string $event, array $payload): void
    {
        $base = rtrim((string) config('services.realtime.base_url'), '/');
        $key = (string) config('services.realtime.signing_key');
        if ($base === '' || $key === '') {
            return;
        }

        $body = json_encode([
            'tenant_id' => $tenantId,
            'rooms' => $rooms,
            'event' => $event,
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return;
        }

        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts . '.' . $body, $key);

        try {
            // Keep UI snappy even if gateway is down
            $res = Http::connectTimeout(0.25)->timeout(0.4)
                ->withHeaders([
                    'x-marka-timestamp' => $ts,
                    'x-marka-signature' => $sig,
                ])
                ->withBody($body, 'application/json')
                ->post($base . '/internal/broadcast');

            if (!$res->ok()) {
                Log::warning('Realtime broadcast non-2xx', [
                    'tenant_id' => $tenantId,
                    'event' => $event,
                    'status' => $res->status(),
                ]);
            }
        } catch (\Throwable $e) {
            // Gateway down/timeout should NOT break user actions (ex: kanban stage move)
            Log::warning('Realtime broadcast failed', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

