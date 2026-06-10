<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
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

            // Zernio profile will be created on first social-account connect,
            // once the user has added at least one API key in Pengaturan Akun.

            // Create tenant
            $tenant = Tenant::create([
                'business_name'     => $request->business_name,
                'owner_name'        => $request->owner_name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'package_id'        => $basicPackage?->id,
                'status'            => 'active',
                'expired_at'        => now()->addDays(14), // 14-day trial
            ]);

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
