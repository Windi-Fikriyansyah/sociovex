<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\InboxMessage;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            return redirect()->route('login');
        }

        $stats = [
            'total_accounts'  => SocialAccount::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
            'total_posts'     => Post::where('tenant_id', $tenant->id)->count(),
            'total_comments'  => Comment::where('tenant_id', $tenant->id)->count(),
            'total_dm'        => InboxMessage::where('tenant_id', $tenant->id)->where('type', 'dm')->count(),
            'pending_replies' => Comment::where('tenant_id', $tenant->id)->where('is_replied', 0)->count(),
            'unread_messages' => InboxMessage::where('tenant_id', $tenant->id)->where('is_read', 0)->count(),
            'scheduled'       => ScheduledPost::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
        ];

        $recentPosts = Post::where('tenant_id', $tenant->id)
            ->with('socialAccount')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentComments = Comment::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $socialAccounts = SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        $upcomingScheduled = ScheduledPost::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'stats', 'recentPosts', 'recentComments', 'socialAccounts', 'upcomingScheduled', 'tenant'
        ));
    }
}
