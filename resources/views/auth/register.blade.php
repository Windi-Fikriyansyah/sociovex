@extends('layouts.mantis-guest')

@section('title', 'Daftar - SocialPilot AI')

@section('content')
<div class="card my-5">
    <div class="card-body">
        <div class="text-center mb-4">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <i class="ti ti-brand-twitter" style="font-size:28px;color:#4680ff;"></i>
                <span style="font-size:22px;font-weight:700;color:#4680ff;">Social<span style="color:#333;">Pilot AI</span></span>
            </div>
            <h4 class="mb-0">Buat Akun Baru</h4>
            <p class="text-muted mt-1">Kelola semua media sosial dalam satu dashboard</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Nama Bisnis <span class="text-danger">*</span></label>
                        <input type="text" name="business_name"
                            class="form-control @error('business_name') is-invalid @enderror"
                            placeholder="Contoh: Klinik Sehat Pontianak"
                            value="{{ old('business_name') }}" required autofocus>
                        @error('business_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Nama Pemilik <span class="text-danger">*</span></label>
                        <input type="text" name="owner_name"
                            class="form-control @error('owner_name') is-invalid @enderror"
                            placeholder="Nama lengkap Anda"
                            value="{{ old('owner_name') }}" required>
                        @error('owner_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="email@bisnis.com" value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-semibold">No. Telepon</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                    placeholder="08xx xxxx xxxx" value="{{ old('phone') }}">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Min. 8 karakter" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                            placeholder="Ulangi password" required>
                    </div>
                </div>
            </div>

            <div class="alert alert-success d-flex align-items-start py-2 mb-3">
                <i class="ti ti-gift me-2 mt-1"></i>
                <div>
                    <strong>Gratis 14 Hari Trial!</strong> Coba semua fitur tanpa kartu kredit.
                </div>
            </div>

            <p class="text-muted mb-3" style="font-size:12px;">
                Dengan mendaftar, Anda menyetujui <a href="#" class="text-primary">Syarat & Ketentuan</a>
                dan <a href="#" class="text-primary">Kebijakan Privasi</a> kami.
            </p>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-user-plus me-2"></i>Buat Akun Gratis
                </button>
            </div>
        </form>

        <p class="text-center text-muted mb-0">
            Sudah punya akun? <a href="{{ route('login') }}" class="link-primary">Login di sini</a>
        </p>
    </div>
</div>
@endsection
