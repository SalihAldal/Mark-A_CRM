<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        // Super panelde tenant scope uygulanmaz.
        if ($ctx->isSuperPanel()) {
            return;
        }

        $tenantId = $ctx->tenantId();
        if ($tenantId === null) {
            // Tenant çözülmediyse strict davran: data leak olmasın.
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', '=', $tenantId);
    }
}

