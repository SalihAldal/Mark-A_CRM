<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'webhook_events';

    protected $fillable = [
        'tenant_id',
        'provider',
        'integration_account_id',
        'direction',
        'signature_valid',
        'payload_json',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}

