<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use BelongsToTenant;

    protected $table = 'leads';

    protected $fillable = [
        'tenant_id',
        'owner_user_id',
        'assigned_user_id',
        'contact_id',
        'stage_id',
        'source',
        'status',
        'score',
        'name',
        'phone',
        'email',
        'company',
        'notes',
        'tags_json',
        'last_contact_at',
    ];

    protected $casts = [
        'last_contact_at' => 'datetime',
    ];
}

