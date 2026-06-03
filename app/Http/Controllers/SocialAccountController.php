<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\ZernioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SocialAccountController extends Controller
{
    public function __construct(private ZernioService $zernio) {}

    // -------------------------------------------------------------------------
    //  List
    // -------------------------------------------------------------------------

    public function index()
    {
        $tenant   = Auth::user()->tenant;
        $accounts = SocialAccount::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return view('social-accounts.index', compact('accounts', 'tenant'));
    }

    // -------------------------------------------------------------------------
    //  Initiate OAuth connect
    //  GET /social-accounts/connect/{platform}
    // -------------------------------------------------------------------------

    public function connect(Request $request, string $platform)
    {
        $validPlatforms = ['instagram', 'facebook', 'linkedin', 'tiktok', 'threads', 'x', 'youtube'];

        if (!in_array($platform, $validPlatforms, true)) {
            abort(404);
        }

        $user   = Auth::user();
        $tenant = $user->tenant;

        // Check package limit
        $package      = $tenant->package;
        $currentCount = SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();

        if ($package && $currentCount >= $package->max_social_accounts) {
            return back()->with('error', 'Batas akun sosial media untuk paket Anda sudah tercapai. Upgrade paket untuk menambah lebih banyak akun.');
        }

        try {
            // --- 1. Ensure Zernio profile exists ---
            if (empty($tenant->zernio_profile_id)) {
                $profileName = $tenant->business_name . '_' . \Illuminate\Support\Str::random(6);
                $result      = $this->zernio->createProfile($profileName);
                $profileId   = $result['profile']['_id'];

                $tenant->update(['zernio_profile_id' => $profileId]);
                Log::info("Zernio profile created for tenant {$tenant->id}", ['profile_id' => $profileId]);

                // Register webhook (non-fatal)
                try {
                    $this->zernio->registerWebhook(
                        $profileId,
                        route('webhook.zernio'),
                        ['new_message', 'new_comment', 'post_published', 'post_failed']
                    );
                } catch (RuntimeException $e) {
                    Log::warning("Webhook registration failed for tenant {$tenant->id}: {$e->getMessage()}");
                }
            }

            // --- 2. Build callback URL with a short-lived token (30 min) ---
            // We store uid→token in cache so the callback can identify the user
            // without relying on session cookies or signed URLs (which break with
            // dynamic ngrok/proxy URLs where APP_URL changes between requests).
            $token = Str::random(40);
            Cache::put("oauth_token:{$token}", $user->id, now()->addMinutes(30));

            $callbackUrl = route('social-accounts.oauth-callback', [
                'platform' => $platform,
                'token'    => $token,
            ]);

            // --- 3. Get Zernio OAuth URL ---
            $result  = $this->zernio->getConnectUrl($platform, $tenant->zernio_profile_id, $callbackUrl);
            $authUrl = $result['authUrl'] ?? null;

            if (!$authUrl) {
                return back()->with('error', 'Gagal mendapatkan URL autentikasi dari Zernio.');
            }

            // --- 4. Redirect to Zernio OAuth ---
            return redirect()->away($authUrl);

        } catch (RuntimeException $e) {
            Log::error("SocialAccountController@connect failed: {$e->getMessage()}");
            return back()->with('error', 'Gagal menghubungkan ke Zernio: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    //  OAuth callback (GET) — outside auth middleware, uses signed URL
    //  GET /social-accounts/oauth-callback/{platform}?uid={userId}&signature=...
    //
    //  Zernio appends its own query params after our callback URL, e.g.:
    //    ?uid=4&...signature...&accountId=acc_xxx&connected=instagram
    // -------------------------------------------------------------------------

    public function callback(Request $request, string $platform)
    {
        // Resolve the user from the cache token (set during connect, valid 30 min).
        // This approach works regardless of APP_URL / ngrok URL changes.
        $token  = $request->query('token');
        $userId = $token ? Cache::pull("oauth_token:{$token}") : null;

        if (!$userId) {
            // Fallback: try the legacy uid param (in case of old links in flight)
            $userId = $request->query('uid');
        }

        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Sesi OAuth tidak valid atau sudah kedaluwarsa. Silakan coba hubungkan akun lagi.');
        }

        // Log the user in for this request so redirects work naturally
        Auth::login($user);

        $tenant = $user->tenant;

        if ($request->filled('error')) {
            return redirect()->route('social-accounts.index')
                ->with('error', 'Otorisasi ditolak: ' . $request->query('error'));
        }

        // Zernio may return accountId directly in query params
        $accountId = $request->query('accountId');

        if (!$accountId || !$tenant->zernio_profile_id) {
            // Try syncing all platforms from Zernio to find the newly added one
            return $this->syncAndRedirect($tenant, $platform, $request);
        }

        // Fetch the real account details from Zernio
        $username       = null;
        $profilePicture = null;

        try {
            $accountData    = $this->zernio->getAccount($tenant->zernio_profile_id, $accountId);
            $acc            = $accountData['account'] ?? [];
            $username       = $acc['username'] ?? $request->query('username');
            $profilePicture = $acc['profilePicture'] ?? null;
        } catch (RuntimeException $e) {
            Log::warning("Could not fetch account details from Zernio: {$e->getMessage()}");
            $username = $request->query('username');
        }

        // Upsert the social account
        $existing = SocialAccount::where('tenant_id', $tenant->id)
            ->where('zernio_account_id', $accountId)
            ->first();

        if ($existing) {
            $existing->update([
                'status'          => 'active',
                'connected_at'    => now(),
                'username'        => $username ?? $existing->username,
                'profile_name'    => $username ?? $existing->profile_name,
                'avatar'          => $profilePicture ?? $existing->avatar,
            ]);

            return redirect()->route('social-accounts.index')
                ->with('success', "Akun {$existing->platform} (@{$existing->username}) berhasil diperbarui.");
        }

        $account = SocialAccount::create([
            'tenant_id'         => $tenant->id,
            'zernio_account_id' => $accountId,
            'platform'          => $platform,
            'username'          => $username,
            'profile_name'      => $username,
            'avatar'            => $profilePicture,
            'connected_at'      => now(),
            'status'            => 'active',
        ]);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $user->id,
            'activity'    => 'connect_social_account',
            'description' => "Akun {$platform} (@{$account->username}) berhasil dihubungkan",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('social-accounts.index')
            ->with('success', "Akun {$platform} (@{$account->username}) berhasil dihubungkan!");
    }

    // -------------------------------------------------------------------------
    //  Sync all platforms from Zernio then redirect (fallback when no accountId)
    // -------------------------------------------------------------------------

    private function syncAndRedirect($tenant, string $platform, Request $request)
    {
        try {
            $result    = $this->zernio->getPlatforms($tenant->zernio_profile_id);
            $platforms = $result['platforms'] ?? [];

            $synced = 0;
            foreach ($platforms as $acc) {
                SocialAccount::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'zernio_account_id' => $acc['_id']],
                    [
                        'platform'      => $acc['platform'],
                        'username'      => $acc['username']      ?? null,
                        'profile_name'  => $acc['username']      ?? null,
                        'avatar'        => $acc['profilePicture'] ?? null,
                        'connected_at'  => now(),
                        'status'        => 'active',
                    ]
                );
                $synced++;
            }

            return redirect()->route('social-accounts.index')
                ->with('success', "{$synced} akun berhasil disinkronisasi dari Zernio.");

        } catch (RuntimeException $e) {
            Log::error("syncAndRedirect failed: {$e->getMessage()}");
            return redirect()->route('social-accounts.index')
                ->with('error', 'Gagal sinkronisasi akun dari Zernio: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    //  Disconnect (soft)
    // -------------------------------------------------------------------------

    public function disconnect(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($socialAccount->zernio_account_id && $tenant->zernio_profile_id) {
            try {
                $this->zernio->disconnectAccount(
                    $socialAccount->platform,
                    $socialAccount->zernio_account_id,
                    $tenant->zernio_profile_id
                );
            } catch (RuntimeException $e) {
                Log::warning("Zernio disconnect failed for account {$socialAccount->id}: {$e->getMessage()}");
            }
        }

        $socialAccount->update(['status' => 'disconnected']);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => Auth::id(),
            'activity'    => 'disconnect_social_account',
            'description' => "Akun {$socialAccount->platform} (@{$socialAccount->username}) diputus",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', 'Akun berhasil diputus.');
    }

    // -------------------------------------------------------------------------
    //  Destroy (hard delete)
    // -------------------------------------------------------------------------

    public function destroy(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($socialAccount->zernio_account_id && $tenant->zernio_profile_id) {
            try {
                $this->zernio->disconnectAccount(
                    $socialAccount->platform,
                    $socialAccount->zernio_account_id,
                    $tenant->zernio_profile_id
                );
            } catch (RuntimeException $e) {
                Log::warning("Zernio delete failed for account {$socialAccount->id}: {$e->getMessage()}");
            }
        }

        $socialAccount->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
