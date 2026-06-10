<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ZernioApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'webhookUrl'    => url('/webhook/zernio'),
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
     */
    public function storeZernioKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'            => ['required', 'string', 'max:100'],
            'api_key'          => ['required', 'string', 'max:255'],
            'webhook_secret'   => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return Redirect::route('profile.edit')->with('error', 'Tenant tidak ditemukan.');
        }

        $tenant->zernioApiKeys()->create($validated);

        return Redirect::route('profile.edit')->with('success', 'API Key Zernio berhasil ditambahkan.');
    }

    /**
     * Delete a Zernio API key entry.
     */
    public function destroyZernioKey(Request $request, ZernioApiKey $zernioApiKey): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant || $zernioApiKey->tenant_id !== $tenant->id) {
            return Redirect::route('profile.edit')->with('error', 'API Key tidak ditemukan.');
        }

        $zernioApiKey->delete();

        return Redirect::route('profile.edit')->with('success', 'API Key Zernio berhasil dihapus.');
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
