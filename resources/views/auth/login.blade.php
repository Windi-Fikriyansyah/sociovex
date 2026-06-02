@extends('layouts.mantis-guest')

@section('title', 'Login - SocialPilot AI')

@section('content')
<div class="card my-5">
    <div class="card-body">
        <div class="text-center mb-4">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <i class="ti ti-brand-twitter" style="font-size:28px;color:#4680ff;"></i>
                <span style="font-size:22px;font-weight:700;color:#4680ff;">Social<span style="color:#333;">Pilot AI</span></span>
            </div>
            <h4 class="mb-0">Selamat Datang</h4>
            <p class="text-muted mt-1">Login ke dashboard Anda</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-3">
                <i class="ti ti-circle-check me-2"></i>{{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-3">
                <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="email@bisnis.com" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                    placeholder="Password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label text-muted" for="remember">Ingat saya</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-primary" style="font-size:14px;">
                        Lupa Password?
                    </a>
                @endif
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-login me-2"></i>Login
                </button>
            </div>
        </form>

        <p class="text-center text-muted mb-0">
            Belum punya akun? <a href="{{ route('register') }}" class="link-primary">Daftar Gratis</a>
        </p>
    </div>
</div>
@endsection
