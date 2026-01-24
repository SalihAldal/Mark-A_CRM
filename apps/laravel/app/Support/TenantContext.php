<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    public const PANEL_SUPER = 'super';
    public const PANEL_TENANT = 'tenant';

    private ?int $tenantId = null;
    private string $panel = self::PANEL_TENANT;
    private ?string $host = null;

    public function setResolved(?int $tenantId, string $panel, string $host): void
    {
        $this->tenantId = $tenantId;
        $this->panel = $panel;
        $this->host = $host;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function host(): ?string
    {
        return $this->host;
    }

    public function panel(): string
    {
        return $this->panel;
    }

    public function isSuperPanel(): bool
    {
        return $this->panel === self::PANEL_SUPER;
    }

    public function isTenantPanel(): bool
    {
        return $this->panel === self::PANEL_TENANT;
    }

    public function requireTenantId(): int
    {
        if ($this->tenantId === null) {
            abort(400, 'Tenant context not resolved.');
        }
        return $this->tenantId;
    }

    public function tenant(): ?Tenant
    {
        if ($this->tenantId === null) {
            return null;
        }
        return Tenant::query()->find($this->tenantId);
    }
}

