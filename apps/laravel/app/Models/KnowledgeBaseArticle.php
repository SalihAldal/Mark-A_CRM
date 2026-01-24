<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    use BelongsToTenant;

    protected $table = 'knowledge_base_articles';

    protected $fillable = [
        'tenant_id',
        'type',
        'title',
        'content',
        'language',
        'tags_json',
    ];
}

