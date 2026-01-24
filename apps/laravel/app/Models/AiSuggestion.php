<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'ai_suggestions';

    protected $fillable = [
        'tenant_id',
        'thread_id',
        'message_id',
        'user_id',
        'template_key',
        'input_snapshot_json',
        'output_text',
        'model',
        'tokens',
        'created_at',
    ];
}

