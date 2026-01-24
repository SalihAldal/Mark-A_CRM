<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\Request;

class RealtimeTokenController extends Controller
{
    public function issue(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $now = time();
        $exp = $now + 60 * 10; // 10 dk

        $payload = [
            'iss' => 'marka-crm-laravel',
            'iat' => $now,
            'exp' => $exp,
            'tenant_id' => (int) $tenantId,
            'user_id' => (int) $user->id,
            'role' => (string) ($user->role?->key ?? ''),
        ];

        $token = $this->signToken($payload);

        return response()->json([
            'token' => $token,
            'expires_at' => $exp,
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
        ]);
    }

    private function signToken(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $b64Header = $this->b64url(json_encode($header, JSON_UNESCAPED_UNICODE));
        $b64Payload = $this->b64url(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $data = $b64Header . '.' . $b64Payload;

        $key = (string) config('services.realtime.signing_key', env('GATEWAY_SIGNING_KEY', ''));
        if ($key === '') {
            abort(500, 'GATEWAY_SIGNING_KEY missing.');
        }

        $sig = hash_hmac('sha256', $data, $key, true);
        return $data . '.' . $this->b64url($sig);
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

