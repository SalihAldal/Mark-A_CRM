<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class IntegrationAccount extends Model
{
    use BelongsToTenant;

    protected $table = 'integration_accounts';

    protected $fillable = [
        'tenant_id',
        'provider',
        'name',
        'status',
        'config_json',
        'webhook_secret',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'config_json' => 'array',
    ];
}

