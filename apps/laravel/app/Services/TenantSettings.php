<?php

namespace App\Services;

use App\Models\TenantSetting;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Crypt;

class TenantSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->tenantId();
        if (!$tenantId) {
            return $default;
        }

        $row = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        if (!$row) {
            return $default;
        }

        $val = (string) ($row->value ?? '');
        $trim = trim($val);
        if ($trim === '') {
            return $default;
        }

        if ((str_starts_with($trim, '{') && str_ends_with($trim, '}')) || (str_starts_with($trim, '[') && str_ends_with($trim, ']'))) {
            $j = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $j;
            }
        }

        return $val;
    }

    public function set(string $key, mixed $value): void
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $val = $value;
        if (is_array($value) || is_object($value)) {
            $val = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        TenantSetting::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $val, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function setSecret(string $key, ?string $plain): void
    {
        if ($plain === null || trim($plain) === '') {
            $this->set($key, null);
            return;
        }
        $this->set($key, Crypt::encryptString($plain));
    }

    public function getSecret(string $key, ?string $default = null): ?string
    {
        $val = $this->get($key, null);
        if (!is_string($val) || trim($val) === '') {
            return $default;
        }
        try {
            return Crypt::decryptString($val);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

