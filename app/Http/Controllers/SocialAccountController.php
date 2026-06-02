<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialAccountController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;
        $accounts = SocialAccount::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $platforms = ['instagram', 'facebook', 'linkedin', 'tiktok', 'threads', 'x', 'youtube'];

        return view('social-accounts.index', compact('accounts', 'platforms', 'tenant'));
    }

    public function connect(Request $request)
    {
        $request->validate([
            'platform' => ['required', 'string', 'in:instagram,facebook,linkedin,tiktok,threads,x,youtube'],
        ]);

        $tenant = Auth::user()->tenant;

        // Check package limit
        $package = $tenant->package;
        $currentCount = SocialAccount::where('tenant_id', $tenant->id)->where('status', 'active')->count();

        if ($package && $currentCount >= $package->max_social_accounts) {
            return back()->with('error', 'Batas akun sosial media untuk paket Anda sudah tercapai. Upgrade paket untuk menambah lebih banyak akun.');
        }

        // In real implementation, this would generate OAuth URL via Zernio API
        // For now, we simulate the connection flow
        return redirect()->route('social-accounts.oauth-redirect', ['platform' => $request->platform]);
    }

    public function oauthRedirect(Request $request)
    {
        $platform = $request->platform;
        // Simulate OAuth redirect to Zernio
        // In production: call Zernio API to get OAuth URL then redirect
        return view('social-accounts.oauth-redirect', compact('platform'));
    }

    public function oauthCallback(Request $request)
    {
        $request->validate([
            'platform'     => ['required', 'string'],
            'username'     => ['required', 'string'],
            'profile_name' => ['nullable', 'string'],
            'account_id'   => ['nullable', 'string'],
        ]);

        $tenant = Auth::user()->tenant;

        $account = SocialAccount::create([
            'tenant_id'         => $tenant->id,
            'zernio_account_id' => $request->account_id ?? 'sim_' . uniqid(),
            'platform'          => $request->platform,
            'username'          => $request->username,
            'profile_name'      => $request->profile_name ?? $request->username,
            'connected_at'      => now(),
            'status'            => 'active',
        ]);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => Auth::id(),
            'activity'    => 'connect_social_account',
            'description' => "Akun {$request->platform} ({$request->username}) berhasil dihubungkan",
            'ip_address'  => $request->ip(),
            'created_at'  => now(),
        ]);

        return redirect()->route('social-accounts.index')
            ->with('success', "Akun {$request->platform} berhasil dihubungkan!");
    }

    public function disconnect(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $socialAccount->update(['status' => 'disconnected']);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => Auth::id(),
            'activity'    => 'disconnect_social_account',
            'description' => "Akun {$socialAccount->platform} ({$socialAccount->username}) diputus",
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);

        return back()->with('success', 'Akun berhasil diputus.');
    }

    public function destroy(SocialAccount $socialAccount)
    {
        $tenant = Auth::user()->tenant;

        if ($socialAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $socialAccount->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
