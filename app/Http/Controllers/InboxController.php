<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        // Check if plan has inbox
        if ($tenant->package && !$tenant->package->has_inbox) {
            return view('inbox.upgrade', compact('tenant'));
        }

        $filter = $request->get('filter', 'all'); // all, unreplied, replied
        $type   = $request->get('type', 'all');   // all, dm, comment, mention

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

    public function replyComment(Request $request, Comment $comment)
    {
        $tenant = Auth::user()->tenant;

        if ($comment->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate(['reply_text' => ['required', 'string', 'max:2200']]);

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

    public function markRead(InboxMessage $message)
    {
        $tenant = Auth::user()->tenant;

        if ($message->tenant_id !== $tenant->id) {
            abort(403);
        }

        $message->update(['is_read' => 1]);

        return back()->with('success', 'Pesan ditandai sudah dibaca.');
    }
}
