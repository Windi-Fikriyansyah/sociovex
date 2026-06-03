<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    protected $fillable = [
        'tenant_id', 'social_account_id', 'zernio_post_id',
        'caption', 'media_url', 'hashtags',
        'platforms', 'social_account_ids', 'scheduled_at', 'status',
    ];

    protected $casts = [
        'platforms'          => 'array',
        'social_account_ids' => 'array',
        'scheduled_at'       => 'datetime',
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
