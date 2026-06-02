<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function create()
    {
        $tenant = Auth::user()->tenant;
        $socialAccounts = SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        return view('posts.create', compact('socialAccounts', 'tenant'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'caption'          => ['required', 'string'],
            'social_accounts'  => ['required', 'array', 'min:1'],
            'social_accounts.*'=> ['exists:social_accounts,id'],
            'publish_type'     => ['required', 'in:now,schedule'],
            'scheduled_at'     => ['required_if:publish_type,schedule', 'nullable', 'date', 'after:now'],
            'hashtags'         => ['nullable', 'string'],
        ]);

        $tenant = Auth::user()->tenant;

        // Verify accounts belong to tenant
        $accounts = SocialAccount::whereIn('id', $request->social_accounts)
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        if ($accounts->isEmpty()) {
            return back()->with('error', 'Tidak ada akun sosial media yang dipilih atau akun tidak aktif.');
        }

        if ($request->publish_type === 'now') {
            // Publish immediately via Zernio API
            foreach ($accounts as $account) {
                Post::create([
                    'tenant_id'        => $tenant->id,
                    'social_account_id'=> $account->id,
                    'caption'          => $request->caption,
                    'hashtags'         => $request->hashtags,
                    'platform'         => $account->platform,
                    'published_at'     => now(),
                ]);
            }

            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => Auth::id(),
                'activity'    => 'publish_post',
                'description' => 'Post diterbitkan ke ' . $accounts->count() . ' akun',
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
            ]);

            return redirect()->route('posts.index')->with('success', 'Post berhasil diterbitkan!');
        } else {
            // Schedule post
            ScheduledPost::create([
                'tenant_id'        => $tenant->id,
                'caption'          => $request->caption,
                'hashtags'         => $request->hashtags,
                'platforms'        => $accounts->pluck('platform')->toArray(),
                'social_account_ids' => $accounts->pluck('id')->toArray(),
                'scheduled_at'     => $request->scheduled_at,
                'status'           => 'pending',
            ]);

            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => Auth::id(),
                'activity'    => 'schedule_post',
                'description' => 'Post dijadwalkan pada ' . $request->scheduled_at,
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
            ]);

            return redirect()->route('calendar.index')->with('success', 'Post berhasil dijadwalkan!');
        }
    }

    public function index()
    {
        $tenant = Auth::user()->tenant;
        $posts = Post::where('tenant_id', $tenant->id)
            ->with('socialAccount')
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('posts.index', compact('posts', 'tenant'));
    }

    public function show(Post $post)
    {
        $tenant = Auth::user()->tenant;

        if ($post->tenant_id !== $tenant->id) {
            abort(403);
        }

        return view('posts.show', compact('post', 'tenant'));
    }
}
