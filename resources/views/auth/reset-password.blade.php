@extends('layouts.mantis-guest')

@section('title', 'Reset Password - SocialPilot AI')

@section('content')
<div class="card my-5">
    <div class="card-body">
        <div class="text-center mb-4">
            <i class="ti ti-shield-lock" style="font-size:48px;color:#4680ff;"></i>
            <h3 class="mt-2 mb-0"><b>Reset Password</b></h3>
        </div>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="form-group mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Email Address" value="{{ old('email', $request->email) }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                    placeholder="Password Baru" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control"
                    placeholder="Konfirmasi Password Baru" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-lock me-2"></i>Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
