<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use BelongsToTenant;

    protected $table = 'calendar_events';

    protected $fillable = [
        'tenant_id',
        'owner_user_id',
        'title',
        'description',
        'location',
        'urgency',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}

