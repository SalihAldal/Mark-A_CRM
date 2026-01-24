<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LeadStage extends Model
{
    use BelongsToTenant;

    protected $table = 'lead_stages';

    protected $fillable = [
        'tenant_id',
        'name',
        'sort_order',
        'color',
        'is_won',
        'is_lost',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];
}

