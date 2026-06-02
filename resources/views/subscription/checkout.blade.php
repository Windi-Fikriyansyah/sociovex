@extends('layouts.mantis')

@section('title', 'Checkout Paket ' . $package->name)
@section('page_title', 'Checkout')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('subscription.index') }}">Langganan</a></li>
    <li class="breadcrumb-item active">Checkout</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header text-center">
                <h5 class="mb-0"><i class="ti ti-credit-card me-2 text-primary"></i>Ringkasan Pembayaran</h5>
            </div>
            <div class="card-body">
                <!-- Order Summary -->
                <div class="border rounded p-4 mb-4" style="background:#f8f9fa;">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Paket</span>
                        <strong>{{ $package->name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Durasi</span>
                        <strong>1 Bulan</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Invoice</span>
                        <code>{{ $payment->invoice_no }}</code>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-primary" style="font-size:20px;">
                            Rp{{ number_format($package->price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Payment Methods (Simulation) -->
                <div class="alert alert-info mb-4">
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Mode Demo:</strong> Integrasi Midtrans siap dikonfigurasi.
                    Gunakan tombol di bawah untuk simulasi pembayaran berhasil.
                </div>

                <div class="row g-3 mb-4">
                    @foreach(['Transfer Bank', 'QRIS', 'OVO', 'GoPay', 'Dana', 'Kartu Kredit'] as $method)
                    <div class="col-4">
                        <div class="border rounded p-2 text-center payment-method" style="cursor:pointer;">
                            <i class="ti ti-credit-card" style="font-size:24px;color:#6c757d;"></i>
                            <div style="font-size:11px;margin-top:4px;">{{ $method }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <form action="{{ route('subscription.simulate-payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                    <input type="hidden" name="package_id" value="{{ $package->id }}">

                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="ti ti-check me-2"></i>Simulasi Pembayaran Berhasil
                    </button>
                    <a href="{{ route('subscription.index') }}" class="btn btn-outline-secondary w-100">
                        Batal
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
