<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'tenant_id', 'social_account_id', 'zernio_comment_id',
        'post_id', 'username', 'comment_text', 'platform', 'commented_at', 'is_replied',
    ];

    protected $casts = [
        'commented_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommentReply::class);
    }
}
