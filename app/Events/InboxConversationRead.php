<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class InboxConversationRead implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    public int $tenantId;
    public int $conversationId;
    public string $zernioConversationId;
    public int $unreadCount;

    public function __construct(
        int $tenantId,
        int $conversationId,
        string $zernioConversationId,
        int $unreadCount = 0
    ) {
        $this->tenantId              = $tenantId;
        $this->conversationId        = $conversationId;
        $this->zernioConversationId  = $zernioConversationId;
        $this->unreadCount           = $unreadCount;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inbox.conversation.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id'         => $this->conversationId,
            'zernio_conversation_id'  => $this->zernioConversationId,
            'unread_count'            => $this->unreadCount,
        ];
    }
}
