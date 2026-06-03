<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Services\ZernioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PostController extends Controller
{
    public function __construct(private ZernioService $zernio) {}

    // -------------------------------------------------------------------------
    //  List
    // -------------------------------------------------------------------------

    public function index()
    {
        $tenant = Auth::user()->tenant;
        $posts  = Post::where('tenant_id', $tenant->id)
            ->with('socialAccount')
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('posts.index', compact('posts', 'tenant'));
    }

    // -------------------------------------------------------------------------
    //  Create form
    // -------------------------------------------------------------------------

    public function create()
    {
        $tenant        = Auth::user()->tenant;
        $socialAccounts = SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        return view('posts.create', compact('socialAccounts', 'tenant'));
    }

    // -------------------------------------------------------------------------
    //  Store (publish now or schedule)
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $request->validate([
            'caption'           => ['required', 'string'],
            'social_accounts'   => ['required', 'array', 'min:1'],
            'social_accounts.*' => ['exists:social_accounts,id'],
            'publish_type'      => ['required', 'in:now,schedule'],
            'scheduled_at'      => ['required_if:publish_type,schedule', 'nullable', 'date', 'after:now'],
            'hashtags'          => ['nullable', 'string'],
            'media_url'         => ['nullable', 'url'],
        ]);

        $tenant = Auth::user()->tenant;

        // Verify accounts belong to this tenant and are active
        $accounts = SocialAccount::whereIn('id', $request->social_accounts)
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        if ($accounts->isEmpty()) {
            return back()->with('error', 'Tidak ada akun sosial media yang dipilih atau akun tidak aktif.');
        }

        $caption   = $request->caption;
        $hashtags  = $request->hashtags;
        $mediaUrl  = $request->media_url;
        $fullText  = $hashtags ? "{$caption}\n{$hashtags}" : $caption;

        if ($request->publish_type === 'now') {
            return $this->publishNow($request, $tenant, $accounts, $fullText, $caption, $hashtags, $mediaUrl);
        }

        return $this->schedulePost($request, $tenant, $accounts, $fullText, $caption, $hashtags, $mediaUrl);
    }

    // -------------------------------------------------------------------------
    //  Show
    // -------------------------------------------------------------------------

    public function show(Post $post)
    {
        $tenant = Auth::user()->tenant;

        if ($post->tenant_id !== $tenant->id) {
            abort(403);
        }

        return view('posts.show', compact('post', 'tenant'));
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    private function publishNow(Request $request, $tenant, $accounts, string $fullText, string $caption, ?string $hashtags, ?string $mediaUrl)
    {
        $errors    = [];
        $published = 0;

        foreach ($accounts as $account) {
            try {
                $payload = [
                    'profileId'  => $tenant->zernio_profile_id,
                    'accountIds' => [$account->zernio_account_id],
                    'content'    => $fullText,
                ];

                if ($mediaUrl) {
                    $payload['mediaUrls'] = [$mediaUrl];
                }

                $zernioPostId = $this->zernio->publishPost($payload);

                Post::create([
                    'tenant_id'         => $tenant->id,
                    'social_account_id' => $account->id,
                    'zernio_post_id'    => $zernioPostId,
                    'caption'           => $caption,
                    'hashtags'          => $hashtags,
                    'media_url'         => $mediaUrl,
                    'platform'          => $account->platform,
                    'published_at'      => now(),
                ]);

                $published++;

            } catch (RuntimeException $e) {
                Log::error("Publish failed for account {$account->id}: {$e->getMessage()}");
                $errors[] = "Gagal publish ke {$account->platform} ({$account->username}): {$e->getMessage()}";
            }
        }

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => Auth::id(),
            'activity'    => 'publish_post',
            'description' => "Post diterbitkan ke {$published} akun" . (count($errors) ? ' dengan ' . count($errors) . ' gagal' : ''),
            'ip_address'  => $request->ip(),
        ]);

        if ($published === 0) {
            return back()->with('error', implode('<br>', $errors));
        }

        $message = "Post berhasil diterbitkan ke {$published} akun!";
        if (count($errors)) {
            $message .= ' ' . count($errors) . ' akun gagal: ' . implode('; ', $errors);
        }

        return redirect()->route('posts.index')->with('success', $message);
    }

    private function schedulePost(Request $request, $tenant, $accounts, string $fullText, string $caption, ?string $hashtags, ?string $mediaUrl)
    {
        $scheduledAt = $request->scheduled_at;
        $errors      = [];
        $scheduled   = 0;

        foreach ($accounts as $account) {
            try {
                $payload = [
                    'profileId'  => $tenant->zernio_profile_id,
                    'accountIds' => [$account->zernio_account_id],
                    'content'    => $fullText,
                    'scheduleAt' => (new \DateTime($scheduledAt))->format(\DateTime::ATOM),
                ];

                if ($mediaUrl) {
                    $payload['mediaUrls'] = [$mediaUrl];
                }

                $zernioPostId = $this->zernio->schedulePost($payload);

                ScheduledPost::create([
                    'tenant_id'          => $tenant->id,
                    'social_account_id'  => $account->id,
                    'zernio_post_id'     => $zernioPostId,
                    'caption'            => $caption,
                    'hashtags'           => $hashtags,
                    'media_url'          => $mediaUrl,
                    'platforms'          => [$account->platform],
                    'social_account_ids' => [$account->id],
                    'scheduled_at'       => $scheduledAt,
                    'status'             => 'pending',
                ]);

                $scheduled++;

            } catch (RuntimeException $e) {
                Log::error("Schedule failed for account {$account->id}: {$e->getMessage()}");
                $errors[] = "Gagal jadwalkan ke {$account->platform} ({$account->username}): {$e->getMessage()}";
            }
        }

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => Auth::id(),
            'activity'    => 'schedule_post',
            'description' => "Post dijadwalkan pada {$scheduledAt} ke {$scheduled} akun",
            'ip_address'  => $request->ip(),
        ]);

        if ($scheduled === 0) {
            return back()->with('error', implode('<br>', $errors));
        }

        $message = "Post berhasil dijadwalkan!";
        if (count($errors)) {
            $message .= ' ' . count($errors) . ' akun gagal: ' . implode('; ', $errors);
        }

        return redirect()->route('calendar.index')->with('success', $message);
    }
}
