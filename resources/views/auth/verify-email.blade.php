@extends('layouts.mantis-guest')

@section('title', 'Verifikasi Email - SocialPilot AI')

@section('content')
<div class="card my-5">
    <div class="card-body text-center">
        <i class="ti ti-mail-check" style="font-size:64px;color:#4680ff;"></i>
        <h3 class="mt-3 mb-2"><b>Verifikasi Email</b></h3>
        <p class="text-muted mb-4">
            Terima kasih sudah mendaftar! Sebelum memulai, silakan verifikasi email Anda dengan mengklik link yang telah kami kirim.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4">
                <i class="ti ti-circle-check me-2"></i>
                Link verifikasi baru telah dikirim ke email Anda.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-primary w-100">
                <i class="ti ti-send me-2"></i>Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="ti ti-logout me-2"></i>Logout
            </button>
        </form>
    </div>
</div>
@endsection
