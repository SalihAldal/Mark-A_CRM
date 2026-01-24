<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LeadStageEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'lead_stage_events';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'from_stage_id',
        'to_stage_id',
        'moved_by_user_id',
        'reason',
        'created_at',
    ];
}

