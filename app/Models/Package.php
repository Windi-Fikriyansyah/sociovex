<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name', 'max_social_accounts', 'max_users', 'max_ai_replies',
        'price', 'has_ai_reply', 'has_analytics', 'has_inbox', 'has_multi_user',
    ];

    protected $casts = [
        'has_ai_reply' => 'boolean',
        'has_analytics' => 'boolean',
        'has_inbox' => 'boolean',
        'has_multi_user' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }
}
