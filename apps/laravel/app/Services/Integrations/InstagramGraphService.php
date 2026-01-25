<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

class InstagramGraphService
{
    /**
     * Fetch Instagram user profile (best-effort).
     *
     * Uses Graph API:
     * GET https://graph.facebook.com/v19.0/{IG_USER_ID}?fields=username,name,profile_pic
     *
     * Performance:
     * - Short timeouts so webhook delivery stays fast.
     * - Called ONLY when user is not cached in DB yet.
     *
     * Safety:
     * - Returns null on missing permissions/errors.
     * - Never throws to caller (webhook must ACK 200 regardless).
     */
    public function fetchUserProfile(string $igUserId, string $pageAccessToken): array
    {
        $igUserId = trim($igUserId);
        $pageAccessToken = trim($pageAccessToken);
        if ($igUserId === '' || $pageAccessToken === '') {
            return ['ok' => false, 'error' => 'missing_params', 'http_status' => null, 'profile' => null];
        }

        $url = "https://graph.facebook.com/v19.0/{$igUserId}";

        try {
            $res = Http::timeout(2.0)
                ->connectTimeout(1.0)
                ->retry(1, 100)
                ->get($url, [
                    'fields' => 'username,name,profile_pic',
                    'access_token' => $pageAccessToken,
                ]);

            if (!$res->ok()) {
                return ['ok' => false, 'error' => 'http', 'http_status' => $res->status(), 'profile' => null];
            }

            $j = $res->json();
            if (!is_array($j)) {
                return ['ok' => false, 'error' => 'invalid_json', 'http_status' => $res->status(), 'profile' => null];
            }

            $profile = [
                'instagram_user_id' => $igUserId,
                'username' => isset($j['username']) && is_string($j['username']) ? trim($j['username']) : null,
                'name' => isset($j['name']) && is_string($j['name']) ? trim($j['name']) : null,
                'profile_picture' => isset($j['profile_pic']) && is_string($j['profile_pic']) ? trim($j['profile_pic']) : null,
            ];

            return ['ok' => true, 'error' => null, 'http_status' => $res->status(), 'profile' => $profile];
        } catch (\Throwable $e) {
            // Swallow errors to keep webhook fast and stable
            return ['ok' => false, 'error' => 'exception', 'http_status' => null, 'profile' => null];
        }
    }
}

