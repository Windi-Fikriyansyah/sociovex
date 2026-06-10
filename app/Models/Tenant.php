<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'uuid', 'business_name', 'owner_name', 'email', 'phone',
        'package_id', 'status', 'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::random(12);
            }
        });
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function aiSettings(): HasMany
    {
        return $this->hasMany(AiSetting::class);
    }

    public function knowledgeBases(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function zernioApiKeys(): HasMany
    {
        return $this->hasMany(ZernioApiKey::class);
    }

    /**
     * Get the next available API key that has fewer than $maxConnections social accounts.
     * Returns null if all keys are at capacity.
     */
    public function getNextAvailableApiKey(int $maxConnections = 2): ?ZernioApiKey
    {
        $keys = $this->zernioApiKeys()->where('is_active', true)->get();

        foreach ($keys as $key) {
            $connectionCount = SocialAccount::where('tenant_id', $this->id)
                ->where('zernio_api_key_id', $key->id)
                ->where('status', 'active')
                ->count();

            if ($connectionCount < $maxConnections) {
                return $key;
            }
        }

        return null;
    }
}
