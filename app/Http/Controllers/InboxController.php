<?php

namespace App\Http\Controllers;

use App\Events\InboxConversationRead;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Conversation;
use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{

    // -------------------------------------------------------------------------
    //  Index
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $filter = $request->get('filter', 'all'); // all | unreplied | replied
        $type   = $request->get('type', 'all');   // all | dm | comment

        $commentsQuery = Comment::where('tenant_id', $tenant->id)
            ->with('socialAccount', 'replies');

        $messagesQuery = InboxMessage::where('tenant_id', $tenant->id)
            ->with('socialAccount');

        if ($filter === 'unreplied') {
            $commentsQuery->where('is_replied', 0);
            $messagesQuery->where('is_read', 0);
        } elseif ($filter === 'replied') {
            $commentsQuery->where('is_replied', 1);
            $messagesQuery->where('is_read', 1);
        }

        $comments = ($type === 'all' || $type === 'comment')
            ? $commentsQuery->orderByDesc('commented_at')->limit(50)->get()
            : collect();

        $messages = ($type === 'all' || $type === 'dm')
            ? $messagesQuery->orderByDesc('received_at')->limit(50)->get()
            : collect();

        $stats = [
            'total_comments'  => Comment::where('tenant_id', $tenant->id)->count(),
            'unreplied'       => Comment::where('tenant_id', $tenant->id)->where('is_replied', 0)->count(),
            'total_messages'  => InboxMessage::where('tenant_id', $tenant->id)->count(),
            'unread_messages' => InboxMessage::where('tenant_id', $tenant->id)->where('is_read', 0)->count(),
        ];

        return view('inbox.index', compact('comments', 'messages', 'stats', 'filter', 'type', 'tenant'));
    }

    // -------------------------------------------------------------------------
    //  Messages (DM)
    // -------------------------------------------------------------------------

    public function messages(Request $request)
{
    $tenant = Auth::user()->tenant;

    $platform = $request->get('platform', 'all');
    $account  = $request->get('account', 'all');
    $filter   = $request->get('filter', 'all');
    $sort     = $request->get('sort', 'newest');

    $socialAccounts = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
        ->where('status', 'active')
        ->get();

    $platforms = $socialAccounts
        ->pluck('platform')
        ->unique()
        ->sort()
        ->values();

    // ── Try local DB first, fallback to Zernio API ────────────
    $conversations = $this->getConversationsHybrid($tenant, $platform, $account, $filter, $sort);

    $stats = [
        'total_messages'  => $conversations->count(),
        'unread_messages' => $conversations->filter(
            fn ($c) => ($c->unread_count ?? $c['unreadCount'] ?? 0) > 0
        )->count(),
    ];

    return view('inbox.messages', [
        'messages'       => $conversations,
        'socialAccounts' => $socialAccounts,
        'platforms'      => $platforms,
        'stats'          => $stats,
        'platform'       => $platform,
        'account'        => $account,
        'filter'         => $filter,
        'sort'           => $sort,
        'tenant'         => $tenant,
    ]);
}

    // -------------------------------------------------------------------------
    //  JSON endpoint for polling — returns conversations + stats
    // -------------------------------------------------------------------------

    public function conversationsJson(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $conversations = $this->getConversationsHybrid($tenant, 'all', 'all', 'all', 'newest');

        $stats = [
            'total_messages'  => $conversations->count(),
            'unread_messages' => $conversations->filter(
                fn ($c) => ($c->unread_count ?? $c['unreadCount'] ?? 0) > 0
            )->count(),
        ];

        return response()->json([
            'success'       => true,
            'conversations' => $conversations->map(fn ($c) => [
                'id'                 => $c->zernio_conversation_id ?? $c['id'] ?? null,
                'local_id'           => $c->id ?? $c['local_id'] ?? null,
                'participantName'    => $c->participant_name ?? $c['participantName'] ?? null,
                'participantPicture' => $c->participant_picture ?? $c['participantPicture'] ?? null,
                'lastMessage'       => $c->last_message ?? $c['lastMessage'] ?? null,
                'platform'          => $c->platform ?? $c['platform'] ?? null,
                'accountUsername'   => $c->account_username ?? $c['accountUsername'] ?? null,
                'accountId'         => $c->zernio_account_id ?? $c['accountId'] ?? null,
                'unreadCount'       => $c->unread_count ?? $c['unreadCount'] ?? 0,
                'updatedTime'       => $c->last_message_at?->toIso8601String() ?? $c['updatedTime'] ?? null,
            ]),
            'stats' => $stats,
        ]);
    }

    // -----------------------------------------------------------------
    //  Messages for a specific conversation (from local DB)
    // -----------------------------------------------------------------

    public function conversationMessages(Request $request, string $id)
    {
        $tenant    = Auth::user()->tenant;
        $accountId = $request->query('accountId');

        // ── Try local DB first ──────────────────────────────────
        $conversation = Conversation::where('tenant_id', $tenant->id)
            ->where('zernio_conversation_id', $id)
            ->first();

        if ($conversation) {
            $messages = InboxMessage::where('conversation_id', $conversation->id)
                ->orderBy('received_at')
                ->limit(200)
                ->get()
                ->map(fn ($m) => [
                    'id'           => $m->id,
                    'message'      => $m->message_text,
                    'senderName'   => $m->sender_name,
                    'direction'    => $m->direction,
                    'platform'     => $m->platform,
                    'isRead'       => $m->is_read,
                    'createdAt'    => $m->received_at?->toIso8601String(),
                ]);

            // If we have local messages, return them
            if ($messages->isNotEmpty()) {
                return response()->json([
                    'success'       => true,
                    'conversation'  => [
                        'id'                  => $conversation->zernio_conversation_id,
                        'local_id'            => $conversation->id,
                        'participantName'     => $conversation->participant_name,
                        'participantPicture'  => $conversation->participant_picture,
                        'platform'           => $conversation->platform,
                        'accountUsername'    => $conversation->account_username,
                        'accountId'          => $conversation->zernio_account_id,
                    ],
                    'data' => $messages,
                ]);
            }
        }

        // ── Fallback: fetch from Zernio API ─────────────────────
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'accountId is required',
            ], 400);
        }

        try {
            $apiKey = null;
            if ($conversation && $conversation->socialAccount) {
                $apiKey = $conversation->socialAccount->zernioApiKey;
            }
            if (!$apiKey && $accountId) {
                $socialAccount = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
                    ->where('zernio_account_id', $accountId)
                    ->first();
                if ($socialAccount) {
                    $apiKey = $socialAccount->zernioApiKey;
                }
            }
            if (!$apiKey) {
                $apiKey = $tenant->zernioApiKeys()->where('is_active', true)->first();
            }

            $zernio = new \App\Services\ZernioService($apiKey?->api_key);
            $response = $zernio->getConversationMessages($id, $accountId);

            return response()->json([
                'success' => true,
                'data'    => $response['messages'] ?? $response['data'] ?? [],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed get conversation messages', [
                'conversation_id' => $id,
                'account_id'      => $accountId,
                'message'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed load messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    //  Comments
    // -------------------------------------------------------------------------

    public function comments(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $platform = $request->get('platform', 'all');
        $account  = $request->get('account', 'all');
        $filter   = $request->get('filter', 'all'); // all | unreplied | replied

        $commentsQuery = Comment::where('tenant_id', $tenant->id)
            ->with('socialAccount', 'replies', 'post');

        // Filter by platform
        if ($platform !== 'all') {
            $commentsQuery->whereHas('socialAccount', function ($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        // Filter by account
        if ($account !== 'all') {
            $commentsQuery->where('social_account_id', $account);
        }

        // Filter by reply status
        if ($filter === 'unreplied') {
            $commentsQuery->where('is_replied', 0);
        } elseif ($filter === 'replied') {
            $commentsQuery->where('is_replied', 1);
        }

        $comments = $commentsQuery->orderByDesc('commented_at')->paginate(20);

        // Get platforms and accounts for filters
        $socialAccounts = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();
        $platforms = $socialAccounts->pluck('platform')->unique()->sort();

        $stats = [
            'total_comments' => Comment::where('tenant_id', $tenant->id)->count(),
            'unreplied'      => Comment::where('tenant_id', $tenant->id)->where('is_replied', 0)->count(),
        ];

        return view('inbox.comments', compact('comments', 'socialAccounts', 'platforms', 'stats', 'platform', 'account', 'filter', 'tenant'));
    }

    public function replyComment(Request $request, Comment $comment)
    {
        $tenant = Auth::user()->tenant;

        if ($comment->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate(['reply_text' => ['required', 'string', 'max:2200']]);

        // Send reply via Zernio inbox API only for DMs (not comments —
        // comment replies go through the social platform directly and are
        // not exposed via the Zernio API at this time).

        CommentReply::create([
            'comment_id' => $comment->id,
            'tenant_id'  => $tenant->id,
            'reply_text' => $request->reply_text,
            'source'     => 'manual',
            'replied_at' => now(),
        ]);

        $comment->update(['is_replied' => 1]);

        return back()->with('success', 'Balasan berhasil dikirim.');
    }

    // -------------------------------------------------------------------------
    //  Mark DM as read
    // -------------------------------------------------------------------------

    public function markRead(InboxMessage $message)
    {
        $tenant = Auth::user()->tenant;

        if ($message->tenant_id !== $tenant->id) {
            abort(403);
        }

        $message->update(['is_read' => 1]);

        return back()->with('success', 'Pesan ditandai sudah dibaca.');
    }

    // -------------------------------------------------------------------------
    //  Send reply to a Zernio conversation
    // -------------------------------------------------------------------------

    public function sendConversationReply(Request $request, string $conversationId)
    {
        $request->validate([
            'message'   => ['required', 'string', 'max:2200'],
            'accountId' => ['required', 'string'],
        ]);

        $tenant = Auth::user()->tenant;

        try {
            $conversation = Conversation::where('tenant_id', $tenant->id)
                ->where('zernio_conversation_id', $conversationId)
                ->first();

            $apiKey = null;
            if ($conversation && $conversation->socialAccount) {
                $apiKey = $conversation->socialAccount->zernioApiKey;
            }
            if (!$apiKey && $request->accountId) {
                $socialAccount = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
                    ->where('zernio_account_id', $request->accountId)
                    ->first();
                if ($socialAccount) {
                    $apiKey = $socialAccount->zernioApiKey;
                }
            }
            if (!$apiKey) {
                $apiKey = $tenant->zernioApiKeys()->where('is_active', true)->first();
            }

            $zernio = new \App\Services\ZernioService($apiKey?->api_key);
            $result = $zernio->sendConversationMessage(
                $conversationId,
                $request->accountId,
                $request->message
            );

            // Store outgoing message in local DB
            $conversation = Conversation::where('tenant_id', $tenant->id)
                ->where('zernio_conversation_id', $conversationId)
                ->first();

            if ($conversation) {
                InboxMessage::create([
                    'tenant_id'         => $tenant->id,
                    'conversation_id'   => $conversation->id,
                    'social_account_id' => $conversation->social_account_id,
                    'zernio_message_id' => $result['data']['_id'] ?? $result['data']['id'] ?? null,
                    'sender_name'       => $conversation->account_username ?? 'You',
                    'message_text'      => $request->message,
                    'platform'          => $conversation->platform,
                    'type'              => 'dm',
                    'direction'         => 'outgoing',
                    'is_read'           => 1,
                    'received_at'       => now(),
                    'sent_at'           => now(),
                ]);

                // Update conversation's last message
                $conversation->update([
                    'last_message'    => $request->message,
                    'last_message_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => $result['data'] ?? null,
                'message' => 'Pesan berhasil dikirim.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to send conversation reply', [
                'conversation_id' => $conversationId,
                'account_id'      => $request->accountId,
                'message'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    //  Mark a Zernio conversation as read
    // -------------------------------------------------------------------------

    public function markConversationRead(Request $request, string $conversationId)
    {
        $request->validate([
            'accountId' => ['required', 'string'],
        ]);

        $tenant = Auth::user()->tenant;

        try {
            $conversation = Conversation::where('tenant_id', $tenant->id)
                ->where('zernio_conversation_id', $conversationId)
                ->first();

            $apiKey = null;
            if ($conversation && $conversation->socialAccount) {
                $apiKey = $conversation->socialAccount->zernioApiKey;
            }
            if (!$apiKey && $request->accountId) {
                $socialAccount = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
                    ->where('zernio_account_id', $request->accountId)
                    ->first();
                if ($socialAccount) {
                    $apiKey = $socialAccount->zernioApiKey;
                }
            }
            if (!$apiKey) {
                $apiKey = $tenant->zernioApiKeys()->where('is_active', true)->first();
            }

            // Mark as read via Zernio API
            $zernio = new \App\Services\ZernioService($apiKey?->api_key);
            $zernio->markConversationAsRead($conversationId, $request->accountId);

            // Update local conversation
            $conversation = Conversation::where('tenant_id', $tenant->id)
                ->where('zernio_conversation_id', $conversationId)
                ->first();

            if ($conversation) {
                $conversation->resetUnread();

                // Also mark all messages in this conversation as read
                InboxMessage::where('conversation_id', $conversation->id)
                    ->where('is_read', 0)
                    ->update(['is_read' => 1]);

                // Broadcast the read status to other sessions
                broadcast(new InboxConversationRead(
                    $tenant->id,
                    $conversation->id,
                    $conversation->zernio_conversation_id,
                    0
                ));
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to mark conversation as read', [
                'conversation_id' => $conversationId,
                'message'         => $e->getMessage(),
            ]);

            // Still return success — marking as read is non-critical
            return response()->json(['success' => true]);
        }
    }

    // -------------------------------------------------------------------------
    //  Hybrid: local DB first, Zernio API fallback + auto-sync
    // -------------------------------------------------------------------------

    /**
     * Get conversations from local DB. If empty, fetch from Zernio API,
     * persist to local DB, then return the local records.
     */
    private function getConversationsHybrid($tenant, string $platform, string $account, string $filter, string $sort)
    {
        // ── Try local DB ────────────────────────────────────────
        $query = Conversation::where('tenant_id', $tenant->id)
            ->where('status', 'active');

        if ($platform !== 'all') {
            $query->where('platform', $platform);
        }
        if ($account !== 'all') {
            $query->where('zernio_account_id', $account);
        }
        if ($filter === 'read') {
            $query->where('unread_count', 0);
        } elseif ($filter === 'unread') {
            $query->where('unread_count', '>', 0);
        }
        $sort === 'oldest'
            ? $query->orderBy('last_message_at')
            : $query->orderByDesc('last_message_at');

        $local = $query->limit(50)->get();

        if ($local->isNotEmpty()) {
            return $local;
        }

        // ── Fallback: Zernio API + sync to local DB ─────────────
        return $this->syncConversationsFromZernio($tenant, $platform, $account, $filter, $sort);
    }

    /**
     * Fetch conversations from Zernio API, upsert to local DB,
     * then return the local Eloquent collection.
     */
    private function syncConversationsFromZernio($tenant, string $platform, string $account, string $filter, string $sort)
    {
        try {
            // Get the API keys we need to sync for
            $apiKeys = collect();
            if ($account !== 'all') {
                $socialAccount = \App\Models\SocialAccount::where('tenant_id', $tenant->id)
                    ->where('zernio_account_id', $account)
                    ->first();
                if ($socialAccount && $socialAccount->zernioApiKey) {
                    $apiKeys->push($socialAccount->zernioApiKey);
                }
            }

            if ($apiKeys->isEmpty()) {
                $apiKeys = $tenant->zernioApiKeys()
                    ->where('is_active', true)
                    ->whereNotNull('zernio_profile_id')
                    ->get();
            }

            foreach ($apiKeys as $apiKey) {
                if (!$apiKey->zernio_profile_id) {
                    continue;
                }

                $zernio = new \App\Services\ZernioService($apiKey->api_key);

                $params = [
                    'profileId' => $apiKey->zernio_profile_id,
                    'limit'     => 50,
                    'sortOrder' => $sort === 'oldest' ? 'asc' : 'desc',
                ];

                if ($platform !== 'all') {
                    $params['platform'] = $platform;
                }
                if ($account !== 'all') {
                    $params['accountId'] = $account;
                }

                try {
                    $response   = $zernio->getInboxConversations($params);
                    $apiResults = collect($response['data'] ?? []);

                    // Upsert each conversation to local DB
                    foreach ($apiResults as $convData) {
                        $zernioConvId  = $convData['id'] ?? null;
                        $zernioAcctId  = $convData['accountId'] ?? null;
                        if (!$zernioConvId) continue;

                        $socialAccount = $zernioAcctId
                            ? \App\Models\SocialAccount::where('zernio_account_id', $zernioAcctId)->first()
                            : null;

                        Conversation::updateOrCreate(
                            ['zernio_conversation_id' => $zernioConvId],
                            [
                                'tenant_id'           => $tenant->id,
                                'social_account_id'   => $socialAccount?->id,
                                'participant_name'    => $convData['participantName'] ?? null,
                                'participant_picture' => $convData['participantPicture'] ?? null,
                                'platform'            => $convData['platform'] ?? null,
                                'account_username'    => $convData['accountUsername'] ?? null,
                                'zernio_account_id'   => $zernioAcctId,
                                'last_message'        => $convData['lastMessage'] ?? null,
                                'last_message_at'     => $convData['updatedTime'] ?? now(),
                                'unread_count'        => $convData['unreadCount'] ?? 0,
                                'status'              => 'active',
                            ]
                        );
                    }
                } catch (\Throwable $e) {
                    \Log::error("Failed to sync conversations for API Key {$apiKey->id}: " . $e->getMessage());
                }
            }

            // Now re-query from local DB (with filters applied)
            $query = Conversation::where('tenant_id', $tenant->id)
                ->where('status', 'active');

            if ($platform !== 'all') {
                $query->where('platform', $platform);
            }
            if ($account !== 'all') {
                $query->where('zernio_account_id', $account);
            }
            if ($filter === 'read') {
                $query->where('unread_count', 0);
            } elseif ($filter === 'unread') {
                $query->where('unread_count', '>', 0);
            }
            $sort === 'oldest'
                ? $query->orderBy('last_message_at')
                : $query->orderByDesc('last_message_at');

            return $query->limit(50)->get();

        } catch (\Throwable $e) {
            \Log::error('Failed get Zernio conversations (hybrid)', [
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }
}
