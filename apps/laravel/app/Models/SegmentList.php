<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SegmentList extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'created_by_user_id',
        'created_at',
    ];
}

