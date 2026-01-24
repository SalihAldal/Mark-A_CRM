<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    public $timestamps = false;

    protected $table = 'ai_prompt_templates';

    protected $fillable = [
        'tenant_id',
        'template_key',
        'title',
        'system_prompt',
        'user_prompt',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

