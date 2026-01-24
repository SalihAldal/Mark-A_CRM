<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            /** @var TenantContext $ctx */
            $ctx = app(TenantContext::class);
            if ($ctx->isSuperPanel()) {
                // Super panel tenant verisi oluşturamaz (strict).
                abort(403, 'Super panel cannot create tenant-scoped rows.');
            }

            if (empty($model->tenant_id)) {
                $model->tenant_id = $ctx->requireTenantId();
            }
        });
    }
}

