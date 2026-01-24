<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MailMessage extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'mail_messages';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'contact_id',
        'direction',
        'status',
        'provider',
        'subject',
        'body',
        'meta_json',
        'created_at',
        'sent_at',
    ];
}

