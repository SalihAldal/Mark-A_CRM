<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ListItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'list_items';

    protected $fillable = [
        'tenant_id',
        'list_id',
        'entity_id',
        'created_at',
    ];
}

