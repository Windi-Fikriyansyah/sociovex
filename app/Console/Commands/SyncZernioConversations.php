<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\InboxMessage;
use App\Models\Tenant;
use App\Services\ZernioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncZernioConversations extends Command
{
    protected $signature = 'zernio:sync-conversations {--tenant= : Specific tenant ID} {--limit=50 : Max conversations per tenant}';

    protected $description = 'Sync conversations and messages from Zernio API to local database';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $limit    = (int) $this->option('limit');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::whereHas('zernioApiKeys', function ($query) {
                $query->whereNotNull('zernio_profile_id')->where('is_active', true);
            })->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants with active Zernio profile API keys found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $apiKeys = $tenant->zernioApiKeys()
                ->where('is_active', true)
                ->whereNotNull('zernio_profile_id')
                ->get();

            if ($apiKeys->isEmpty()) {
                continue;
            }

            foreach ($apiKeys as $apiKey) {
                $this->info("Syncing conversations for tenant: {$tenant->business_name} (ID: {$tenant->id}) | API Key: {$apiKey->label}");

                try {
                    $zernio = new ZernioService($apiKey->api_key);

                    $response = $zernio->getInboxConversations([
                        'profileId' => $apiKey->zernio_profile_id,
                        'limit'     => $limit,
                        'sortOrder' => 'desc',
                    ]);

                    $conversations = $response['data'] ?? [];

                    $synced = 0;
                    foreach ($conversations as $convData) {
                        $zernioConvId = $convData['id'] ?? null;
                        if (!$zernioConvId) continue;

                        // Find social account
                        $zernioAccountId = $convData['accountId'] ?? null;
                        $socialAccount = $zernioAccountId
                            ? \App\Models\SocialAccount::where('zernio_account_id', $zernioAccountId)->first()
                            : null;

                        // Upsert conversation
                        $conversation = Conversation::updateOrCreate(
                            ['zernio_conversation_id' => $zernioConvId],
                            [
                                'tenant_id'           => $tenant->id,
                                'social_account_id'   => $socialAccount?->id,
                                'participant_name'    => $convData['participantName'] ?? null,
                                'participant_picture' => $convData['participantPicture'] ?? null,
                                'platform'            => $convData['platform'] ?? null,
                                'account_username'    => $convData['accountUsername'] ?? null,
                                'zernio_account_id'   => $zernioAccountId,
                                'last_message'        => $convData['lastMessage'] ?? null,
                                'last_message_at'     => isset($convData['updatedTime'])
                                    ? $convData['updatedTime']
                                    : now(),
                                'unread_count'        => $convData['unreadCount'] ?? 0,
                                'status'              => 'active',
                            ]
                        );

                        // Sync messages for this conversation if it has an accountId
                        if ($zernioAccountId) {
                            $this->syncMessages($zernio, $conversation, $zernioConvId, $zernioAccountId);
                        }

                        $synced++;
                    }

                    $this->info("  Synced {$synced} conversations for API Key: {$apiKey->label}");

                } catch (\Throwable $e) {
                    $this->error("  Failed for API Key {$apiKey->label}: {$e->getMessage()}");
                    Log::error('SyncZernioConversations failed for API Key', [
                        'tenant_id'  => $tenant->id,
                        'api_key_id' => $apiKey->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }

        return self::SUCCESS;
    }

    private function syncMessages(ZernioService $zernio, Conversation $conversation, string $convId, string $accountId): void
    {
        try {
            $response = $zernio->getConversationMessages($convId, $accountId);
            $messages = $response['messages'] ?? $response['data'] ?? [];

            foreach ($messages as $msgData) {
                $zernioMsgId = $msgData['_id'] ?? $msgData['id'] ?? null;

                if (!$zernioMsgId) continue;

                // Skip if already exists
                if (InboxMessage::where('zernio_message_id', $zernioMsgId)->exists()) {
                    continue;
                }

                $isOut = ($msgData['direction'] ?? '') === 'outgoing'
                    || ($msgData['isMine'] ?? false) === true
                    || ($msgData['senderType'] ?? '') === 'business'
                    || ($msgData['from']['type'] ?? '') === 'page';

                InboxMessage::create([
                    'tenant_id'         => $conversation->tenant_id,
                    'conversation_id'   => $conversation->id,
                    'social_account_id' => $conversation->social_account_id,
                    'zernio_message_id' => $zernioMsgId,
                    'sender_name'       => $msgData['senderName'] ?? $msgData['sender_name'] ?? null,
                    'sender_id'         => $msgData['senderId'] ?? $msgData['sender_id'] ?? null,
                    'message_text'      => $msgData['message'] ?? $msgData['text'] ?? $msgData['content'] ?? '',
                    'platform'          => $conversation->platform,
                    'type'              => 'dm',
                    'direction'         => $isOut ? 'outgoing' : 'incoming',
                    'is_read'           => $isOut ? 1 : 0,
                    'received_at'       => $msgData['createdAt'] ?? $msgData['created_at'] ?? now(),
                    'sent_at'           => $isOut ? ($msgData['createdAt'] ?? now()) : null,
                ]);
            }
        } catch (\Throwable $e) {
            // Don't fail the whole sync if one conversation's messages fail
            $this->warn("  Failed to sync messages for conversation {$convId}: {$e->getMessage()}");
        }
    }
}
