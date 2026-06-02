<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant) {
            $tenant = $user->tenant;

            if ($tenant->status === 'suspended' || $tenant->status === 'cancelled') {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Akun Anda telah ditangguhkan. Hubungi support untuk bantuan.');
            }
        }

        return $next($request);
    }
}
