<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ZernioService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private ZernioService $zernio) {}

    public function create(): View
    {
        $packages = Package::all();
        return view('auth.register', compact('packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name'    => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'         => ['nullable', 'string', 'max:50'],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request) {
            $basicPackage = Package::where('name', 'Basic')->first();

            // --- Create Zernio profile before saving the tenant ---
            // Append a short unique suffix so duplicate business names never collide.
            $zernioProfileId = null;
            $profileName     = $request->business_name . '_' . \Illuminate\Support\Str::random(6);
            try {
                $result          = $this->zernio->createProfile($profileName);
                $zernioProfileId = $result['profile']['_id'];
            } catch (\RuntimeException $e) {
                // Don't fail registration if Zernio is unreachable.
                // The profile will be created on first social-account connect.
                Log::error("Zernio createProfile failed during registration: {$e->getMessage()}", [
                    'business_name' => $request->business_name,
                    'email'         => $request->email,
                    'base_url'      => config('services.zernio.base_url'),
                ]);
            }

            // Create tenant
            $tenant = Tenant::create([
                'business_name'     => $request->business_name,
                'owner_name'        => $request->owner_name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'zernio_profile_id' => $zernioProfileId,
                'package_id'        => $basicPackage?->id,
                'status'            => 'active',
                'expired_at'        => now()->addDays(14), // 14-day trial
            ]);

            // Register webhook with Zernio for this profile (non-fatal)
            if ($zernioProfileId) {
                try {
                    $this->zernio->registerWebhook(
                        $zernioProfileId,
                        route('webhook.zernio'),
                        ['new_message', 'new_comment', 'post_published', 'post_failed']
                    );
                } catch (\RuntimeException $e) {
                    Log::warning("Gagal daftarkan webhook saat register tenant {$tenant->id}: {$e->getMessage()}");
                }
            }

            // Create the user and link to the tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->owner_name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'owner',
                'status'    => 1,
            ]);

            event(new Registered($user));

            Auth::login($user);
        });

        return redirect()->route('dashboard');
    }
}
