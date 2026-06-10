<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReply;
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

        if ($tenant->package && !$tenant->package->has_inbox) {
            return view('inbox.upgrade', compact('tenant'));
        }

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

    if ($tenant->package && !$tenant->package->has_inbox) {
        return view('inbox.upgrade', compact('tenant'));
    }

    $platform = $request->get('platform', 'all');
    $account  = $request->get('account', 'all');
    $filter   = $request->get('filter', 'all');
    $sort     = $request->get('sort', 'newest');

    $socialAccounts = \App\Models\SocialAccount::where(
        'tenant_id',
        $tenant->id
    )
    ->where('status', 'active')
    ->get();

    $platforms = $socialAccounts
        ->pluck('platform')
        ->unique()
        ->sort()
        ->values();

    $conversations = collect();

    try {

        $zernio = \App\Services\ZernioService::forTenant($tenant);

        $params = [
            'profileId' => $tenant->zernio_profile_id,
            'limit'     => 50,
            'sortOrder' => $sort === 'oldest'
                ? 'asc'
                : 'desc',
        ];

        if ($platform !== 'all') {
            $params['platform'] = $platform;
        }

        if ($account !== 'all') {

            $socialAccount = $socialAccounts
                ->firstWhere(
                    'zernio_account_id',
                    $account
                );

            if ($socialAccount) {
                $params['accountId'] =
                    $socialAccount->zernio_account_id;
            }
        }

        $response = $zernio->getInboxConversations($params);

        $conversations = collect(
            $response['data'] ?? []
        );

        // local fallback sort
        $conversations = $sort === 'oldest'
            ? $conversations->sortBy('updatedTime')->values()
            : $conversations->sortByDesc('updatedTime')->values();

        // read/unread
        if ($filter === 'read') {
            $conversations = $conversations->filter(
                fn ($m) =>
                    ($m['unreadCount'] ?? 0) === 0
            );
        }

        if ($filter === 'unread') {
            $conversations = $conversations->filter(
                fn ($m) =>
                    ($m['unreadCount'] ?? 0) > 0
            );
        }

    } catch (\Throwable $e) {

        \Log::error(
            'Failed get Zernio conversations',
            [
                'message' => $e->getMessage(),
            ]
        );
    }

    $stats = [
        'total_messages' => $conversations->count(),

        'unread_messages' => $conversations
            ->filter(
                fn ($m) =>
                    ($m['unreadCount'] ?? 0) > 0
            )
            ->count(),
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

        $conversations = collect();

        try {
            $zernio = \App\Services\ZernioService::forTenant($tenant);

            $params = [
                'profileId' => $tenant->zernio_profile_id,
                'limit'     => 50,
                'sortOrder' => 'desc',
            ];

            $response = $zernio->getInboxConversations($params);
            $conversations = collect($response['data'] ?? [])
                ->sortByDesc('updatedTime')
                ->values();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        $stats = [
            'total_messages'  => $conversations->count(),
            'unread_messages' => $conversations->filter(fn ($m) => ($m['unreadCount'] ?? 0) > 0)->count(),
        ];

        return response()->json([
            'success'       => true,
            'conversations' => $conversations,
            'stats'         => $stats,
        ]);
    }

    // -------------------------------------------------------------------------
    //  Real-time events endpoint — returns new events and deletes them
    // -------------------------------------------------------------------------

    public function inboxEvents(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $since  = $request->query('since');

        $query = \App\Models\InboxEvent::where('tenant_id', $tenant->id);

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $events = $query->orderBy('created_at')->limit(50)->get();

        // Delete consumed events (older than 30 seconds to avoid race conditions)
        \App\Models\InboxEvent::where('tenant_id', $tenant->id)
            ->where('created_at', '<', now()->subSeconds(30))
            ->delete();

        return response()->json([
            'success' => true,
            'events'  => $events->map(fn ($e) => [
                'type'      => $e->event_type,
                'payload'   => $e->payload,
                'timestamp' => $e->created_at->toIso8601String(),
            ]),
            'server_time' => now()->toIso8601String(),
        ]);
    }



public function conversationMessages(Request $request, string $id)
{
    try {
        $tenant    = Auth::user()->tenant;
        $accountId = $request->query('accountId');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'accountId is required',
            ], 400);
        }

        $zernio = \App\Services\ZernioService::forTenant($tenant);
        $response = $zernio->getConversationMessages($id, $accountId);

        return response()->json([
            'success' => true,
            'data' => $response['messages'] ?? $response['data'] ?? [],
        ]);

    } catch (\Throwable $e) {
        \Log::error('Failed get conversation messages', [
            'conversation_id' => $id,
            'account_id'      => $request->query('accountId'),
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

        if ($tenant->package && !$tenant->package->has_inbox) {
            return view('inbox.upgrade', compact('tenant'));
        }

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
            $zernio = \App\Services\ZernioService::forTenant($tenant);
            $result = $zernio->sendConversationMessage(
                $conversationId,
                $request->accountId,
                $request->message
            );

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
            $zernio = \App\Services\ZernioService::forTenant($tenant);
            $zernio->markConversationAsRead($conversationId, $request->accountId);

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
}
