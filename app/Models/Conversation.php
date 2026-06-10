<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'tenant_id',
        'social_account_id',
        'zernio_conversation_id',
        'participant_name',
        'participant_picture',
        'participant_id',
        'platform',
        'account_username',
        'zernio_account_id',
        'last_message',
        'last_message_at',
        'unread_count',
        'status',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count'    => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class)->orderByDesc('received_at');
    }

    /**
     * Update or create a conversation from a Zernio webhook payload.
     */
    public static function upsertFromWebhook(array $payload, int $tenantId, int $socialAccountId): self
    {
        $zernioConvId = $payload['conversation_id'] ?? null;

        if (!$zernioConvId) {
            // If no conversation_id, generate one from sender_id + account_id
            $zernioConvId = ($payload['sender_id'] ?? 'unknown') . '_' . ($payload['account_id'] ?? 'unknown');
        }

        return static::updateOrCreate(
            [
                'zernio_conversation_id' => $zernioConvId,
            ],
            [
                'tenant_id'           => $tenantId,
                'social_account_id'   => $socialAccountId,
                'participant_name'    => $payload['sender_name'] ?? null,
                'participant_picture' => $payload['sender_picture'] ?? null,
                'participant_id'      => $payload['sender_id'] ?? null,
                'platform'            => $payload['platform'] ?? null,
                'account_username'    => $payload['account_username'] ?? null,
                'zernio_account_id'   => $payload['account_id'] ?? null,
                'last_message'        => $payload['text'] ?? '',
                'last_message_at'     => now(),
                'status'              => 'active',
            ]
        );
    }

    /**
     * Update or create a conversation from a normalized webhook payload.
     * This accepts already-normalized fields from the WebhookController.
     */
    public static function upsertFromNormalized(array $data, int $tenantId, int $socialAccountId): self
    {
        $zernioConvId = $data['zernio_conversation_id']
            ?? $data['conversation_id']
            ?? null;

        if (!$zernioConvId) {
            $zernioConvId = ($data['sender_id'] ?? 'unknown') . '_' . ($data['account_id'] ?? 'unknown');
        }

        return static::updateOrCreate(
            [
                'zernio_conversation_id' => $zernioConvId,
            ],
            [
                'tenant_id'           => $tenantId,
                'social_account_id'   => $socialAccountId,
                'participant_name'    => $data['participant_name'] ?? $data['sender_name'] ?? null,
                'participant_picture' => $data['participant_picture'] ?? $data['sender_picture'] ?? null,
                'participant_id'      => $data['sender_id'] ?? $data['participant_id'] ?? null,
                'platform'            => $data['platform'] ?? null,
                'account_username'    => $data['account_username'] ?? null,
                'zernio_account_id'   => $data['account_id'] ?? $data['zernio_account_id'] ?? null,
                'last_message'        => $data['last_message'] ?? $data['text'] ?? '',
                'last_message_at'     => now(),
                'status'              => 'active',
            ]
        );
    }

    /**
     * Increment unread count.
     */
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    /**
     * Reset unread count to zero.
     */
    public function resetUnread(): void
    {
        $this->update(['unread_count' => 0]);
    }
}
