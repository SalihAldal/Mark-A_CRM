<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use BelongsToTenant;

    protected $table = 'threads';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'contact_id',
        'channel',
        'integration_account_id',
        'subject',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];
}

