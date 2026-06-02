<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    protected $fillable = [
        'tenant_id', 'caption', 'media_url', 'hashtags',
        'platforms', 'social_account_ids', 'scheduled_at', 'status',
    ];

    protected $casts = [
        'platforms' => 'array',
        'social_account_ids' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
