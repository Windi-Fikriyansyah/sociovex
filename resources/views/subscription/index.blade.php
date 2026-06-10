@extends('layouts.mantis')

@section('title', 'Paket Berlangganan')
@section('page_title', 'Paket Berlangganan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Langganan</li>
@endsection

@section('content')
<!-- Current Plan Status -->
<div class="card mb-4" style="border-left: 4px solid #4680ff;">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="text-muted mb-1">Paket Aktif Anda</h6>
                <h4 class="mb-0">{{ $currentPackage?->name ?? 'Pro' }}</h4>
                @if($activeSubscription)
                    <small class="text-muted">
                        Aktif: {{ $activeSubscription->start_date->format('d M Y') }} &mdash;
                        {{ $activeSubscription->end_date->format('d M Y') }}
                    </small>
                @elseif($tenant->expired_at)
                    <small class="text-muted">
                        Berakhir: {{ $tenant->expired_at->format('d M Y') }}
                        @if($tenant->expired_at->isPast())
                            <span class="badge bg-danger ms-1">Expired</span>
                        @else
                            <span class="badge bg-success ms-1">Aktif</span>
                        @endif>
                    </small>
                @else
                    <small class="text-muted">Aktif tanpa batas waktu</small>
                @endif
            </div>
            <div class="col-auto">
                @if($currentPackage)
                    <h3 class="mb-0 text-primary">{{ $currentPackage->formatted_price }}/bulan</h3>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Package Card — Single Pro Plan -->
<div class="row justify-content-center mb-4">
    @foreach($packages as $package)
    @php
        $isCurrentPlan = $currentPackage?->id === $package->id;
    @endphp
    <div class="col-lg-6 col-md-8 mb-4">
        <div class="card h-100 border-primary" style="border: 2px solid #4680ff;">
            <div class="card-header text-center py-2" style="background: #4680ff; color: white;">
                <strong><i class="ti ti-star-filled me-1"></i>PAKET PRO</strong>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="text-center mb-4">
                    <h4 class="fw-bold">Pro</h4>
                    <div class="my-3">
                        <span style="font-size:36px;font-weight:700;color:#4680ff;">
                            Rp{{ number_format($package->price, 0, ',', '.') }}
                        </span>
                        <span class="text-muted">/bulan</span>
                    </div>
                </div>

                <ul class="list-unstyled flex-grow-1">
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        <strong>Unlimited</strong> akun sosial media
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        Scheduler & Content Calendar
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        Inbox Terpusat (Real-time)
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        AI Auto Reply <small class="text-muted">(Unlimited)</small>
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        Analytics Lengkap
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check text-success me-2"></i>
                        Multi User <small class="text-muted">(Unlimited)</small>
                    </li>
                </ul>

                <div class="mt-3">
                    @if($isCurrentPlan)
                        <button class="btn btn-success w-100" disabled>
                            <i class="ti ti-check me-1"></i>Paket Aktif
                        </button>
                    @else
                        <form action="{{ route('subscription.checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="package_id" value="{{ $package->id }}">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-credit-card me-1"></i>Berlangganan Pro
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Payment History -->
@if($paymentHistory->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-receipt me-2"></i>Riwayat Pembayaran</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Jumlah</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentHistory as $payment)
                    <tr>
                        <td><code>{{ $payment->invoice_no }}</code></td>
                        <td>Rp{{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td>{{ $payment->payment_method ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $payment->payment_status === 'paid' ? 'success' : ($payment->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($payment->payment_status) }}
                            </span>
                        </td>
                        <td>{{ $payment->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
