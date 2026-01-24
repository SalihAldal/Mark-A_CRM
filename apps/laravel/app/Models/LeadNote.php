<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LeadNote extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'lead_notes';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'user_id',
        'note_text',
        'created_at',
    ];
}

