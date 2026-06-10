<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\InboxMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class InboxMessageReceived implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    public int $tenantId;
    public array $message;
    public array $conversation;

    /**
     * @param int $tenantId
     * @param InboxMessage $inboxMessage  The newly created InboxMessage
     * @param Conversation $conversation  The updated Conversation
     */
    public function __construct(int $tenantId, InboxMessage $inboxMessage, Conversation $conversation)
    {
        $this->tenantId = $tenantId;

        $this->message = [
            'id'                => $inboxMessage->id,
            'conversation_id'   => $inboxMessage->conversation_id,
            'sender_name'       => $inboxMessage->sender_name,
            'sender_id'         => $inboxMessage->sender_id,
            'message_text'      => $inboxMessage->message_text,
            'platform'          => $inboxMessage->platform,
            'direction'         => $inboxMessage->direction,
            'is_read'           => $inboxMessage->is_read,
            'received_at'       => $inboxMessage->received_at?->toIso8601String(),
            'sent_at'           => $inboxMessage->sent_at?->toIso8601String(),
        ];

        $this->conversation = [
            'id'                     => $conversation->id,
            'zernio_conversation_id' => $conversation->zernio_conversation_id,
            'participant_name'       => $conversation->participant_name,
            'participant_picture'    => $conversation->participant_picture,
            'platform'               => $conversation->platform,
            'account_username'       => $conversation->account_username,
            'zernio_account_id'      => $conversation->zernio_account_id,
            'last_message'           => $conversation->last_message,
            'last_message_at'        => $conversation->last_message_at?->toIso8601String(),
            'unread_count'           => $conversation->unread_count,
        ];
    }

    /**
     * Broadcast on a private channel scoped to the tenant.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.inbox'),
        ];
    }

    /**
     * The event name sent to the client.
     */
    public function broadcastAs(): string
    {
        return 'inbox.message.received';
    }

    /**
     * Data sent to the client.
     */
    public function broadcastWith(): array
    {
        return [
            'message'      => $this->message,
            'conversation' => $this->conversation,
        ];
    }
}
