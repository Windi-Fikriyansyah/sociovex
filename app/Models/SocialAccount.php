<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'tenant_id', 'zernio_api_key_id', 'zernio_account_id', 'platform', 'username',
        'profile_name', 'avatar', 'access_token', 'refresh_token',
        'connected_at', 'status',        'is_ads_account',
        'parent_account_id', 
        'ads_scope',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'is_ads_account' => 'boolean',
    'ads_scope' => 'array',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function zernioApiKey(): BelongsTo
    {
        return $this->belongsTo(ZernioApiKey::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'instagram' => 'ti ti-brand-instagram',
            'facebook' => 'ti ti-brand-facebook',
            'linkedin' => 'ti ti-brand-linkedin',
            'tiktok' => 'ti ti-brand-tiktok',
            'threads' => 'ti ti-brand-threads',
            'twitter', 'x' => 'ti ti-brand-x',
            'youtube' => 'ti ti-brand-youtube',
            default => 'ti ti-share',
        };
    }

    public function getPlatformColorAttribute(): string
    {
        return match($this->platform) {
            'instagram' => '#E1306C',
            'facebook' => '#1877F2',
            'linkedin' => '#0A66C2',
            'tiktok' => '#000000',
            'threads' => '#000000',
            'twitter', 'x' => '#1DA1F2',
            'youtube' => '#FF0000',
            default => '#6c757d',
        };
    }


     public function getPlatformDisplayNameAttribute(): string
    {
        return match($this->platform) {
            'metaads' => 'Meta Ads',
            'linkedinads' => 'LinkedIn Ads',
            'tiktokads' => 'TikTok Ads',
            'xads' => 'X Ads',
            'pinterestads' => 'Pinterest Ads',
            'googleads' => 'Google Ads',
            default => ucfirst($this->platform),
        };
    }

    public function isAdsAccount(): bool
    {
        return $this->is_ads_account === true;
    }

    public function isPostingAccount(): bool
    {
        return $this->is_ads_account === false;
    }

    // ─── Scopes ───────────────────────────────────────────────────
    
    public function scopeAdsAccounts($query)
    {
        return $query->where('is_ads_account', true);
    }

    public function scopePostingAccounts($query)
    {
        return $query->where('is_ads_account', false);
    }
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
