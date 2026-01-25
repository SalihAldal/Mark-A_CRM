<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToTenant;

    protected $table = 'contacts';

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'external_id',
        'provider',
        'instagram_user_id',
        'username',
        'profile_picture',
        'created_at',
        'updated_at',
    ];
}

