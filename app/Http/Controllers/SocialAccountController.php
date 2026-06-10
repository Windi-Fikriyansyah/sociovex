<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\ZernioApiKey;
use App\Services\ZernioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SocialAccountController extends Controller
{
    public function index()
    {
        $tenant   = Auth::user()->tenant;
        $accounts = SocialAccount::where('tenant_id', $tenant->id)
            ->with('zernioApiKey')
            ->orderByDesc('created_at')
            ->get();

        return view('social-accounts.index', compact('accounts', 'tenant'));
    }

    public function connect(Request $request, string $platform)
{
    $validPlatforms = ['instagram', 'facebook', 'linkedin', 'tiktok', 'threads', 'youtube'];

    if (!in_array($platform, $validPlatforms, true)) {
        abort(404);
    }

    $user   = Auth::user();
    $tenant = $user->tenant;

    $apiKey = $tenant->getNextAvailableApiKey(2);

    if (!$apiKey) {
        return back()->with('error', 'Semua API Key Zernio sudah mencapai batas maksimal 2 koneksi per key. Tambahkan API Key baru di Pengaturan Akun.');
    }

    // CEK: Pastikan API key tidak kosong
    if (empty($apiKey->api_key)) {
        return back()->with('error', 'API Key Zernio tidak valid atau kosong. Silakan periksa kembali API Key Anda.');
    }

    $zernio = new ZernioService($apiKey->api_key);

    try {
        // CEK: Validasi API key terlebih dahulu sebelum create profile
        if (!$this->validateApiKey($zernio)) {
            return back()->with('error', 'API Key Zernio tidak valid. Silakan periksa kembali API Key Anda di pengaturan.');
        }

        if (empty($apiKey->zernio_profile_id)) {
            $profileName = $tenant->business_name . '_' . Str::random(6);
            
            try {
                $result      = $zernio->createProfile($profileName);
                $profileId   = $result['profile']['_id'] ?? null;
                
                if (!$profileId) {
                    throw new RuntimeException('Gagal mendapatkan profile ID dari Zernio');
                }

                $apiKey->update(['zernio_profile_id' => $profileId]);
                Log::info("Zernio profile created for API Key {$apiKey->id} of tenant {$tenant->id}", ['profile_id' => $profileId]);

                try {
                    $zernio->registerWebhook(
                        $profileId,
                        route('webhook.zernio'),
                        ['new_message', 'new_comment', 'post_published', 'post_failed']
                    );
                } catch (RuntimeException $e) {
                    Log::warning("Webhook registration failed for API Key {$apiKey->id}: {$e->getMessage()}");
                    // Jangan throw error, lanjutkan proses
                }
            } catch (RuntimeException $e) {
                Log::error("Failed to create Zernio profile: {$e->getMessage()}");
                return back()->with('error', 'Gagal membuat profile di Zernio: ' . $e->getMessage());
            }
        }

        // Pastikan profile_id sudah ada
        if (empty($apiKey->zernio_profile_id)) {
            return back()->with('error', 'Zernio Profile ID tidak ditemukan. Silakan coba lagi.');
        }

        $token = Str::random(40);
        Cache::put("oauth_token:{$token}", [
            'user_id'           => $user->id,
            'zernio_api_key_id' => $apiKey->id,
        ], now()->addMinutes(30));

        $callbackUrl = route('social-accounts.oauth-callback', [
            'platform' => $platform,
            'token'    => $token,
        ]);

        $result  = $zernio->getConnectUrl($platform, $apiKey->zernio_profile_id, $callbackUrl);
        $authUrl = $result['authUrl'] ?? $result['auth_url'] ?? null;

        if (!$authUrl) {
            Log::error('Zernio getConnectUrl response missing authUrl', ['response' => $result]);
            return back()->with('error', 'Gagal mendapatkan URL autentikasi dari Zernio: response tidak mengandung authUrl');
        }

        return redirect()->away($authUrl);
    } catch (RuntimeException $e) {
        Log::error("SocialAccountController@connect failed: {$e->getMessage()}", [
            'platform' => $platform,
            'api_key_id' => $apiKey->id,
            'trace' => $e->getTraceAsString()
        ]);
        return back()->with('error', 'Gagal menghubungkan ke Zernio: ' . $e->getMessage());
    }
}

// Tambahkan method helper untuk validasi API key
private function validateApiKey(ZernioService $zernio): bool
{
    try {
        // Coba get profiles untuk validasi API key
        $zernio->getProfiles();
        return true;
    } catch (RuntimeException $e) {
        Log::error('API Key validation failed: ' . $e->getMessage());
        return false;
    }
}
    public function callback(Request $request, string $platform)
    {
        $token     = $request->query('token');
        $tokenData = Cache::get("oauth_token:{$token}");

        $userId       = null;
        $apiKeyIdFromToken = null;

        if (is_array($tokenData)) {
            $userId       = $tokenData['user_id'] ?? null;
            $apiKeyIdFromToken = $tokenData['zernio_api_key_id'] ?? null;
        } elseif ($tokenData) {
            $userId = $tokenData;
        }

        if (!$userId) {
            $userId = $request->query('uid');
        }

        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Sesi OAuth tidak valid atau sudah kedaluwarsa. Silakan coba hubungkan akun lagi.');
        }

        Auth::login($user);

        $tenant = $user->tenant;

        if ($request->filled('error')) {
            Cache::forget("oauth_token:{$token}");
            return redirect()->route('social-accounts.index')
                ->with('error', 'Otorisasi ditolak: ' . $request->query('error'));
        }

        $apiKey = $apiKeyIdFromToken
            ? ZernioApiKey::find($apiKeyIdFromToken)
            : $tenant->zernioApiKeys()->where('is_active', true)->first();

        $zernio = $apiKey
            ? new ZernioService($apiKey->api_key)
            : new ZernioService();

        // Wait for account to be connected in Zernio
        $result = $this->waitForAccount($zernio, $apiKey->zernio_profile_id);
        
        Cache::forget("oauth_token:{$token}");

        return $this->syncAndRedirect($tenant, $platform, $request, $zernio, $apiKey, $result);
    }

    private function waitForAccount(ZernioService $zernio, string $profileId): array
{
    $maxAttempts = 10; // Tambahin jadi 10 attempts
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        $attempt++;
        sleep(2); // Tunggu 2 detik setiap kali
        
        try {
            Log::info("Checking Zernio accounts (attempt {$attempt}/{$maxAttempts})", ['profileId' => $profileId]);
            $result = $zernio->getAccounts($profileId);
            
            if (!empty($result['accounts']) && count($result['accounts']) > 0) {
                Log::info("Found accounts on attempt {$attempt}", ['count' => count($result['accounts'])]);
                return $result;
            }
            
            // Log jika belum ada akun
            Log::info("No accounts found yet on attempt {$attempt}", ['profileId' => $profileId]);
        } catch (RuntimeException $e) {
            Log::warning("Error fetching accounts on attempt {$attempt}: {$e->getMessage()}");
            
            // Jika error 404, mungkin profile tidak ditemukan
            if (str_contains($e->getMessage(), '404')) {
                Log::error("Profile not found: {$profileId}");
                break;
            }
        }
    }
    
    Log::warning("No accounts found after {$maxAttempts} attempts", ['profileId' => $profileId]);
    return ['accounts' => []];
}

    private function syncAndRedirect($tenant, string $platform, Request $request, ZernioService $zernio, ?ZernioApiKey $apiKey, array $result)
{
    try {
        $accounts = $result['accounts'] ?? [];
        $synced = 0;
        
        Log::info("Syncing social accounts", [
            'platform' => $platform,
            'accounts_count' => count($accounts),
            'has_api_key' => !is_null($apiKey)
        ]);
        
        // If no accounts found via getAccounts, try getAccount with specific ID from query
        if (empty($accounts) && $request->query('accountId')) {
            try {
                Log::info("Attempting to fetch specific account", ['accountId' => $request->query('accountId')]);
                $accountData = $zernio->getAccount($request->query('accountId'));
                if (!empty($accountData['account'])) {
                    $accounts = [$accountData['account']];
                    Log::info("Successfully fetched specific account");
                }
            } catch (RuntimeException $e) {
                Log::warning("Could not fetch specific account: {$e->getMessage()}");
            }
        }
        
        if (empty($accounts)) {
            Log::warning("No accounts to sync", [
                'profileId' => $apiKey?->zernio_profile_id,
                'result' => $result
            ]);
            return redirect()->route('social-accounts.index')
                ->with('warning', 'Tidak ada akun yang ditemukan. Pastikan Anda telah mengotorisasi aplikasi di platform ' . ucfirst($platform) . '.');
        }

        foreach ($accounts as $acc) {
            $existing = SocialAccount::where('tenant_id', $tenant->id)
                ->where('zernio_account_id', $acc['_id'])
                ->first();

            $accountData = [
                'platform'      => $acc['platform'] ?? $platform,
                'username'      => $acc['username'] ?? $acc['displayName'] ?? null,
                'profile_name'  => $acc['displayName'] ?? $acc['username'] ?? null,
                'avatar'        => $acc['profilePicture'] ?? $acc['avatar'] ?? null,
                'status'        => ($acc['isActive'] ?? true) ? 'active' : 'disconnected',
                'connected_at'  => now(),
                'zernio_api_key_id' => $apiKey?->id,
            ];

            if ($existing) {
                $existing->update($accountData);
                Log::info("Updated existing social account", ['id' => $existing->id, 'platform' => $accountData['platform']]);
                $synced++;
            } else {
                $accountData['tenant_id'] = $tenant->id;
                $accountData['zernio_account_id'] = $acc['_id'];
                
                SocialAccount::create($accountData);
                Log::info("Created new social account", ['platform' => $accountData['platform'], 'username' => $accountData['username']]);
                $synced++;
            }

            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => Auth::id(),
                'activity'    => 'connect_social_account',
                'description' => "Akun {$accountData['platform']} (@{$accountData['username']}) berhasil dihubungkan" .
                                ($apiKey ? " menggunakan API Key: {$apiKey->label}" : ''),
                'ip_address'  => $request->ip(),
            ]);
        }

        if ($synced > 0) {
            return redirect()->route('social-accounts.index')
                ->with('success', "{$synced} akun berhasil disinkronisasi dari Zernio.");
        } else {
            return redirect()->route('social-accounts.index')
                ->with('warning', 'Tidak ada akun baru yang ditemukan. Akun mungkin sudah terhubung sebelumnya.');
        }
    } catch (RuntimeException $e) {
        Log::error("syncAndRedirect failed: {$e->getMessage()}", [
            'platform' => $platform,
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->route('social-accounts.index')
            ->with('error', 'Gagal sinkronisasi akun dari Zernio: ' . $e->getMessage());
    }
}

    public function disconnect(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($socialAccount->zernio_account_id) {
            try {
                $zernio = $socialAccount->zernioApiKey
                    ? new ZernioService($socialAccount->zernioApiKey->api_key)
                    : ZernioService::forTenant($tenant);

                $zernio->deleteAccount($socialAccount->zernio_account_id);
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

    public function destroy(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($socialAccount->zernio_account_id) {
            try {
                $zernio = $socialAccount->zernioApiKey
                    ? new ZernioService($socialAccount->zernioApiKey->api_key)
                    : ZernioService::forTenant($tenant);

                $zernio->deleteAccount($socialAccount->zernio_account_id);
            } catch (RuntimeException $e) {
                Log::warning("Zernio delete failed for account {$socialAccount->id}: {$e->getMessage()}");
            }
        }

        $socialAccount->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }



    public function connectAds(Request $request, string $platform)
{
    $validAdsPlatforms = ['facebook', 'instagram', 'linkedin', 'tiktok', 'twitter', 'pinterest', 'googleads'];
    
    if (!in_array($platform, $validAdsPlatforms, true)) {
        abort(404);
    }
    
    $user = Auth::user();
    $tenant = $user->tenant;
    
    // Untuk platform yang memerlukan accountId (Twitter/X Ads)
    $accountId = $request->query('accountId');
    
    if ($platform === 'twitter' && !$accountId) {
        return back()->with('error', 'Account ID required for Twitter Ads. Please select a Twitter posting account first.');
    }
    
    // Cek apakah sudah ada akun ads yang terhubung
    $existingAdsAccount = SocialAccount::where('tenant_id', $tenant->id)
        ->where('platform', $this->getAdsPlatformName($platform))
        ->where('status', 'active')
        ->first();
    
    if ($existingAdsAccount) {
        return redirect()->route('social-accounts.index')
            ->with('info', "Akun Ads untuk {$platform} sudah terhubung.");
    }
    
    $apiKey = $tenant->getNextAvailableApiKey(2);
    
    if (!$apiKey) {
        return back()->with('error', 'Semua API Key Zernio sudah mencapai batas maksimal 2 koneksi per key. Tambahkan API Key baru di Pengaturan Akun.');
    }
    
    if (empty($apiKey->api_key)) {
        return back()->with('error', 'API Key Zernio tidak valid atau kosong.');
    }
    
    $zernio = new ZernioService($apiKey->api_key);
    
    try {
        // Validasi API key
        if (!$this->validateApiKey($zernio)) {
            return back()->with('error', 'API Key Zernio tidak valid.');
        }
        
        // Pastikan profile_id ada
        if (empty($apiKey->zernio_profile_id)) {
            $profileName = $tenant->business_name . '_' . Str::random(6);
            $result = $zernio->createProfile($profileName);
            $profileId = $result['profile']['_id'] ?? null;
            
            if (!$profileId) {
                throw new RuntimeException('Gagal mendapatkan profile ID dari Zernio');
            }
            
            $apiKey->update(['zernio_profile_id' => $profileId]);
        }
        
        $token = Str::random(40);
        
        // Simpan data untuk callback
        Cache::put("oauth_ads_token:{$token}", [
            'user_id' => $user->id,
            'zernio_api_key_id' => $apiKey->id,
            'platform' => $platform,
            'account_id' => $accountId,
            'ad_account_ids' => $request->query('adAccountIds'), // Untuk Meta Ads scope
        ], now()->addMinutes(30));
        
        $callbackUrl = route('social-accounts.ads-callback', [
            'platform' => $platform,
            'token' => $token,
        ]);
        
        // Siapkan adAccountIds untuk Meta Ads
        $adAccountIds = null;
        if ($request->query('adAccountIds')) {
            $adAccountIds = explode(',', $request->query('adAccountIds'));
        }
        
        $result = $zernio->getAdsConnectUrl(
            $platform,
            $apiKey->zernio_profile_id,
            $accountId,
            $callbackUrl,
            $adAccountIds
        );
        
        // Cek apakah sudah terhubung
        if (!empty($result['alreadyConnected'])) {
            // Sinkronisasi akun ads yang sudah ada
            return $this->syncAdsAccount($tenant, $platform, $apiKey, $result);
        }
        
        $authUrl = $result['authUrl'] ?? $result['auth_url'] ?? null;
        
        if (!$authUrl) {
            Log::error('Zernio getAdsConnectUrl response missing authUrl', ['response' => $result]);
            return back()->with('error', 'Gagal mendapatkan URL autentikasi dari Zernio.');
        }
        
        return redirect()->away($authUrl);
        
    } catch (RuntimeException $e) {
        Log::error("SocialAccountController@connectAds failed: {$e->getMessage()}", [
            'platform' => $platform,
            'api_key_id' => $apiKey->id,
            'trace' => $e->getTraceAsString()
        ]);
        return back()->with('error', 'Gagal menghubungkan Ads: ' . $e->getMessage());
    }
}

/**
 * Callback untuk OAuth Ads
 */
public function adsCallback(Request $request, string $platform)
{
    $token = $request->query('token');
    $tokenData = Cache::get("oauth_ads_token:{$token}");
    
    if (!$tokenData || !is_array($tokenData)) {
        return redirect()->route('login')
            ->with('error', 'Sesi OAuth tidak valid atau sudah kedaluwarsa.');
    }
    
    $userId = $tokenData['user_id'] ?? null;
    $apiKeyId = $tokenData['zernio_api_key_id'] ?? null;
    $originalPlatform = $tokenData['platform'] ?? $platform;
    
    $user = $userId ? User::find($userId) : null;
    
    if (!$user) {
        return redirect()->route('login')
            ->with('error', 'User tidak ditemukan.');
    }
    
    Auth::login($user);
    $tenant = $user->tenant;
    
    if ($request->filled('error')) {
        Cache::forget("oauth_ads_token:{$token}");
        return redirect()->route('social-accounts.index')
            ->with('error', 'Otorisasi ditolak: ' . $request->query('error'));
    }
    
    $apiKey = ZernioApiKey::find($apiKeyId);
    
    if (!$apiKey) {
        return redirect()->route('social-accounts.index')
            ->with('error', 'API Key tidak ditemukan.');
    }
    
    $zernio = new ZernioService($apiKey->api_key);
    
    // Tunggu hingga akun ads tersedia
    $result = $this->waitForAdsAccount($zernio, $apiKey->zernio_profile_id, $originalPlatform);
    
    Cache::forget("oauth_ads_token:{$token}");
    
    return $this->syncAdsAccount($tenant, $originalPlatform, $apiKey, $result);
}

/**
 * Tunggu hingga akun Ads tersedia di Zernio
 */
private function waitForAdsAccount(ZernioService $zernio, string $profileId, string $platform, int $maxAttempts = 15): array
{
    $adsPlatformName = $this->getAdsPlatformName($platform);
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        $attempt++;
        sleep(2);
        
        try {
            Log::info("Checking Zernio ads accounts (attempt {$attempt}/{$maxAttempts})", [
                'profileId' => $profileId,
                'platform' => $adsPlatformName
            ]);
            
            $result = $zernio->getAccounts($profileId);
            
            if (!empty($result['accounts'])) {
                // Cari akun dengan platform ads yang sesuai
                foreach ($result['accounts'] as $account) {
                    if ($account['platform'] === $adsPlatformName) {
                        Log::info("Found ads account on attempt {$attempt}", [
                            'account_id' => $account['_id'],
                            'platform' => $account['platform']
                        ]);
                        return ['accounts' => [$account]];
                    }
                }
            }
            
            Log::info("No ads account found yet on attempt {$attempt}", [
                'profileId' => $profileId,
                'looking_for' => $adsPlatformName
            ]);
            
        } catch (RuntimeException $e) {
            Log::warning("Error fetching ads accounts on attempt {$attempt}: {$e->getMessage()}");
        }
    }
    
    Log::warning("No ads account found after {$maxAttempts} attempts", [
        'profileId' => $profileId,
        'platform' => $adsPlatformName
    ]);
    
    return ['accounts' => []];
}

/**
 * Sinkronisasi akun Ads
 */
private function syncAdsAccount($tenant, string $platform, ZernioApiKey $apiKey, array $result)
{
    try {
        $accounts = $result['accounts'] ?? [];
        $adsPlatformName = $this->getAdsPlatformName($platform);
        $synced = 0;
        
        if (empty($accounts)) {
            return redirect()->route('social-accounts.index')
                ->with('warning', 'Tidak ada akun Ads yang ditemukan. Pastikan Anda telah mengotorisasi akses Ads di platform ' . ucfirst($platform) . '.');
        }
        
        foreach ($accounts as $acc) {
            // Hanya sync akun dengan platform yang sesuai
            if ($acc['platform'] !== $adsPlatformName) {
                continue;
            }
            
            $existing = SocialAccount::where('tenant_id', $tenant->id)
                ->where('zernio_account_id', $acc['_id'])
                ->first();
            
            $accountData = [
                'platform' => $adsPlatformName,
                'username' => $acc['username'] ?? $acc['displayName'] ?? null,
                'profile_name' => $acc['displayName'] ?? $acc['username'] ?? ($adsPlatformName . ' Ads'),
                'avatar' => $acc['profilePicture'] ?? $acc['avatar'] ?? null,
                'status' => ($acc['isActive'] ?? true) ? 'active' : 'disconnected',
                'connected_at' => now(),
                'zernio_api_key_id' => $apiKey->id,
                'is_ads_account' => true, // Anda perlu menambahkan kolom ini di migration
            ];
            
            if ($existing) {
                $existing->update($accountData);
                Log::info("Updated existing ads account", ['id' => $existing->id, 'platform' => $adsPlatformName]);
                $synced++;
            } else {
                $accountData['tenant_id'] = $tenant->id;
                $accountData['zernio_account_id'] = $acc['_id'];
                
                SocialAccount::create($accountData);
                Log::info("Created new ads account", ['platform' => $adsPlatformName, 'username' => $accountData['username']]);
                $synced++;
            }
            
            ActivityLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'activity' => 'connect_ads_account',
                'description' => "Akun Ads {$adsPlatformName} (@{$accountData['username']}) berhasil dihubungkan",
                'ip_address' => request()->ip(),
            ]);
        }
        
        if ($synced > 0) {
            return redirect()->route('social-accounts.index')
                ->with('success', "{$synced} akun Ads berhasil disinkronisasi dari Zernio.");
        } else {
            return redirect()->route('social-accounts.index')
                ->with('warning', 'Tidak ada akun Ads baru yang ditemukan.');
        }
        
    } catch (RuntimeException $e) {
        Log::error("syncAdsAccount failed: {$e->getMessage()}", [
            'platform' => $platform,
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->route('social-accounts.index')
            ->with('error', 'Gagal sinkronisasi akun Ads: ' . $e->getMessage());
    }
}

/**
 * Konversi platform name ke ads platform name
 */
private function getAdsPlatformName(string $platform): string
{
    $mapping = [
        'facebook' => 'metaads',
        'instagram' => 'metaads',
        'linkedin' => 'linkedinads',
        'tiktok' => 'tiktokads',
        'twitter' => 'xads',
        'pinterest' => 'pinterestads',
        'googleads' => 'googleads',
    ];
    
    return $mapping[$platform] ?? $platform . 'ads';
}

/**
 * Get connect ads route name for view
 */
public function getAdsConnectUrlForView(string $platform, ?string $accountId = null): string
{
    $params = ['platform' => $platform];
    
    if ($accountId) {
        $params['accountId'] = $accountId;
    }
    
    return route('social-accounts.connect-ads', $params);
}
}