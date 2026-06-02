<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;
        $packages = Package::all();
        $currentPackage = $tenant->package;
        $activeSubscription = Subscription::where('tenant_id', $tenant->id)
            ->where('payment_status', 'paid')
            ->orderByDesc('end_date')
            ->first();

        $paymentHistory = Payment::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('subscription.index', compact(
            'tenant', 'packages', 'currentPackage', 'activeSubscription', 'paymentHistory'
        ));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $tenant = Auth::user()->tenant;
        $package = Package::findOrFail($request->package_id);

        // Create pending subscription
        $subscription = Subscription::create([
            'tenant_id'      => $tenant->id,
            'package_id'     => $package->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'amount'         => $package->price,
            'payment_status' => 'pending',
        ]);

        // Create payment record
        $payment = Payment::create([
            'tenant_id'      => $tenant->id,
            'invoice_no'     => 'INV-' . strtoupper(uniqid()),
            'amount'         => $package->price,
            'payment_status' => 'pending',
        ]);

        // In production: integrate with Midtrans to get payment URL
        // For now redirect to a simulation page
        return view('subscription.checkout', compact('package', 'subscription', 'payment', 'tenant'));
    }

    public function simulatePayment(Request $request)
    {
        $request->validate([
            'payment_id'  => ['required', 'exists:payments,id'],
            'package_id'  => ['required', 'exists:packages,id'],
        ]);

        $tenant = Auth::user()->tenant;
        $payment = Payment::where('tenant_id', $tenant->id)->findOrFail($request->payment_id);
        $package = Package::findOrFail($request->package_id);

        // Update payment
        $payment->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'payment_method' => 'simulation',
        ]);

        // Update subscription
        Subscription::where('tenant_id', $tenant->id)
            ->where('payment_status', 'pending')
            ->latest()
            ->first()
            ?->update(['payment_status' => 'paid']);

        // Update tenant package
        $tenant->update([
            'package_id' => $package->id,
            'status'     => 'active',
            'expired_at' => now()->addMonth(),
        ]);

        return redirect()->route('subscription.index')
            ->with('success', "Berhasil berlangganan paket {$package->name}! Paket Anda aktif hingga " . now()->addMonth()->format('d M Y') . '.');
    }
}
