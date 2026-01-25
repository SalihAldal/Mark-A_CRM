<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'body',
        'entity_type',
        'entity_id',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

