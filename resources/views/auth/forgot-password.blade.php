@extends('layouts.mantis-guest')

@section('title', 'Lupa Password - SocialPilot AI')

@section('content')
<div class="card my-5">
    <div class="card-body">
        <div class="text-center mb-4">
            <i class="ti ti-lock-open" style="font-size:48px;color:#4680ff;"></i>
            <h3 class="mt-2 mb-0"><b>Lupa Password?</b></h3>
            <p class="text-muted mt-1">Masukkan email Anda untuk menerima link reset password.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-3">
                <i class="ti ti-circle-check me-2"></i>{{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Email Address" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-mail me-2"></i>Kirim Link Reset Password
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="link-primary">
                <i class="ti ti-arrow-left me-1"></i>Kembali ke Login
            </a>
        </div>
    </div>
</div>
@endsection
