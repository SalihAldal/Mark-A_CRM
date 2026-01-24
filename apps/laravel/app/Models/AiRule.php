<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiRule extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'ai_rules';

    protected $fillable = [
        'tenant_id',
        'sector',
        'tone',
        'forbidden_phrases',
        'sales_focus',
        'language',
        'created_at',
    ];

    protected $casts = [
        'sales_focus' => 'boolean',
    ];
}

