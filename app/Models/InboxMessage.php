<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    protected $fillable = [
        'tenant_id', 'social_account_id', 'sender_name', 'sender_id',
        'message_text', 'platform', 'type', 'is_read', 'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
