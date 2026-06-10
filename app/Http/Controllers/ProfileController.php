<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ZernioApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        $zernioApiKeys = $tenant
            ? $tenant->zernioApiKeys()->latest()->get()
            : collect();

        return view('profile.edit', [
            'user'          => $user,
            'tenant'        => $tenant,
            'webhookUrl'    => rtrim(config('app.url'), '/') . '/webhook/zernio',
            'zernioApiKeys' => $zernioApiKeys,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Store a new Zernio API key entry for the tenant.
     * Automatically creates a Zernio profile and stores its ID.
     */
    public function storeZernioKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'            => ['required', 'string', 'max:100'],
            'api_key'          => ['required', 'string', 'max:255'],
            'webhook_secret'   => ['nullable', 'string', 'max:255'],
            'profile_name'     => ['nullable', 'string', 'max:100'], // Optional custom profile name
        ]);

        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return Redirect::route('profile.edit')->with('error', 'Tenant tidak ditemukan.');
        }

        // Prepare profile name (use label as default if not specified)
        $profileName = $validated['profile_name'] ?? $validated['label'];
        
        // Create profile in Zernio API
        $profileId = $this->createZernioProfile($validated['api_key'], $profileName);
        
        if (!$profileId) {
            return Redirect::route('profile.edit')->with('error', 'Gagal membuat profile di Zernio. Periksa API key Anda.');
        }

        // Save API key with profile_id
        $tenant->zernioApiKeys()->create([
            'label'            => $validated['label'],
            'api_key'          => $validated['api_key'],
            'webhook_secret'   => $validated['webhook_secret'] ?? Str::random(64),
            'zernio_profile_id' => $profileId,
        ]);

        return Redirect::route('profile.edit')->with('success', 'API Key Zernio berhasil ditambahkan dengan profile ID: ' . $profileId);
    }

    /**
     * Create a profile in Zernio API.
     * 
     * @param string $apiKey Zernio API key
     * @param string $profileName Name for the profile
     * @return string|null Profile ID or null if failed
     */
    /**
 * Create a profile in Zernio API with detailed debugging.
 */
/**
 * Create a profile in Zernio API.
 * 
 * @param string $apiKey Zernio API key
 * @param string $profileName Name for the profile
 * @return string|null Profile ID or null if failed
 */
private function createZernioProfile(string $apiKey, string $profileName): ?string
{
    try {
        // First, get all profiles to check if name exists
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->get('https://zernio.com/api/v1/profiles');

        if ($response->successful()) {
            $data = $response->json();
            // Structure: { "profiles": [...] }
            $profiles = $data['profiles'] ?? [];
            
            // Check if profile with same name already exists
            foreach ($profiles as $profile) {
                if (isset($profile['name']) && $profile['name'] === $profileName) {
                    // Return existing profile ID (using _id field)
                    $existingId = $profile['_id'] ?? null;
                    \Log::info('Using existing Zernio profile', [
                        'profile_name' => $profileName,
                        'profile_id' => $existingId
                    ]);
                    return $existingId;
                }
            }
        }

        // If no existing profile found, create new one
        $createResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://zernio.com/api/v1/profiles', [
            'name' => $profileName,
            'description' => 'Created automatically from application',
        ]);

        if ($createResponse->successful()) {
            $data = $createResponse->json();
            // Return the new profile ID (using _id field)
            return $data['_id'] ?? $data['profile']['_id'] ?? null;
        }

        // Handle error
        $errorBody = $createResponse->json();
        $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? 'Unknown error';
        
        \Log::error('Failed to create Zernio profile', [
            'api_key' => substr($apiKey, 0, 10) . '...',
            'profile_name' => $profileName,
            'status' => $createResponse->status(),
            'error' => $errorMessage,
        ]);

        return null;
    } catch (\Exception $e) {
        \Log::error('Exception when creating Zernio profile', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return null;
    }
}

    /**
     * Delete a Zernio API key entry.
     * Also deletes the corresponding profile in Zernio if needed.
     */
    public function destroyZernioKey(Request $request, ZernioApiKey $zernioApiKey): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant || $zernioApiKey->tenant_id !== $tenant->id) {
            return Redirect::route('profile.edit')->with('error', 'API Key tidak ditemukan.');
        }

        // Optional: Delete profile in Zernio [citation:8][citation:9]
        if ($zernioApiKey->zernio_profile_id && $zernioApiKey->api_key) {
            $this->deleteZernioProfile($zernioApiKey->api_key, $zernioApiKey->zernio_profile_id);
        }

        $zernioApiKey->delete();

        return Redirect::route('profile.edit')->with('success', 'API Key Zernio berhasil dihapus.');
    }

    /**
     * Delete a profile in Zernio API.
     * 
     * @param string $apiKey Zernio API key
     * @param string $profileId Profile ID to delete
     * @return bool True if successful
     */
    private function deleteZernioProfile(string $apiKey, string $profileId): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->delete('https://zernio.com/api/v1/profiles/' . $profileId);

            if ($response->successful()) {
                \Log::info('Zernio profile deleted', ['profile_id' => $profileId]);
                return true;
            }

            \Log::warning('Failed to delete Zernio profile', [
                'profile_id' => $profileId,
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            \Log::error('Exception when deleting Zernio profile', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync/refresh profile data from Zernio API.
     * Useful for updating profile information or verifying API key.
     */
    public function syncZernioProfile(Request $request, ZernioApiKey $zernioApiKey): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant || $zernioApiKey->tenant_id !== $tenant->id) {
            return Redirect::route('profile.edit')->with('error', 'API Key tidak ditemukan.');
        }

        // Fetch profile from Zernio [citation:8][citation:9]
        $profileData = $this->getZernioProfile($zernioApiKey->api_key, $zernioApiKey->zernio_profile_id);

        if ($profileData) {
            // You can update local data here if needed
            return Redirect::route('profile.edit')->with('success', 'Profile berhasil disinkronkan: ' . ($profileData['name'] ?? 'N/A'));
        }

        return Redirect::route('profile.edit')->with('error', 'Gagal menyinkronkan profile. Periksa API key Anda.');
    }

    /**
     * Get a profile from Zernio API.
     * 
     * @param string $apiKey Zernio API key
     * @param string $profileId Profile ID to fetch
     * @return array|null Profile data or null if failed
     */
    private function getZernioProfile(string $apiKey, string $profileId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get('https://zernio.com/api/v1/profiles/' . $profileId);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Exception when fetching Zernio profile', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate a new webhook secret for a specific Zernio API key entry.
     */
    public function regenerateZernioSecret(Request $request, ZernioApiKey $zernioApiKey): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant || $zernioApiKey->tenant_id !== $tenant->id) {
            return Redirect::route('profile.edit')->with('error', 'API Key tidak ditemukan.');
        }

        $zernioApiKey->update([
            'webhook_secret' => Str::random(64),
        ]);

        return Redirect::route('profile.edit')->with('success', 'Webhook secret berhasil dibuat ulang.');
    }

    /**
     * List all profiles for an API key from Zernio.
     * Useful for selection/dropdown in UI.
     */
    public function listZernioProfiles(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'api_key' => ['required', 'string'],
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $request->api_key,
                'Accept' => 'application/json',
            ])->get('https://zernio.com/api/v1/profiles');

            if ($response->successful()) {
                $data = $response->json();
                $profiles = $data['data'] ?? $data;
                return response()->json(['success' => true, 'profiles' => $profiles]);
            }

            return response()->json(['success' => false, 'error' => 'Failed to fetch profiles'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}