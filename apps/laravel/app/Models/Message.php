<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'messages';

    protected $fillable = [
        'tenant_id',
        'thread_id',
        'sender_type',
        'sender_user_id',
        'sender_contact_id',
        'message_type',
        'body_text',
        'file_path',
        'file_mime',
        'file_size',
        'voice_duration_ms',
        'metadata_json',
        'created_at',
    ];
}

