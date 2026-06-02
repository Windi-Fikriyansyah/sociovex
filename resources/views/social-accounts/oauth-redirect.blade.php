@extends('layouts.mantis')

@section('title', 'Hubungkan ' . ucfirst($platform))
@section('page_title', 'Hubungkan ' . ucfirst($platform))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('social-accounts.index') }}">Akun Sosial Media</a></li>
    <li class="breadcrumb-item active">Hubungkan {{ ucfirst($platform) }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body text-center py-5">
                @php
                    $icons = [
                        'instagram' => ['ti ti-brand-instagram', '#E1306C'],
                        'facebook'  => ['ti ti-brand-facebook', '#1877F2'],
                        'linkedin'  => ['ti ti-brand-linkedin', '#0A66C2'],
                        'tiktok'    => ['ti ti-brand-tiktok', '#000'],
                        'threads'   => ['ti ti-brand-threads', '#000'],
                        'x'         => ['ti ti-brand-x', '#1DA1F2'],
                        'youtube'   => ['ti ti-brand-youtube', '#FF0000'],
                    ];
                    [$icon, $color] = $icons[$platform] ?? ['ti ti-share', '#6c757d'];
                @endphp

                <div style="width:80px;height:80px;border-radius:50%;background:{{ $color }}1a;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <i class="{{ $icon }}" style="font-size:40px;color:{{ $color }};"></i>
                </div>

                <h4>Hubungkan {{ ucfirst($platform) }}</h4>
                <p class="text-muted">
                    Pada implementasi produksi, Anda akan diarahkan ke halaman login {{ ucfirst($platform) }}
                    melalui Zernio OAuth API.
                </p>

                <div class="alert alert-info text-start">
                    <strong><i class="ti ti-info-circle me-2"></i>Mode Demo:</strong><br>
                    Isi detail akun di bawah untuk simulasi koneksi.
                </div>

                <form action="{{ route('social-accounts.oauth-callback') }}" method="POST" class="text-start mt-4">
                    @csrf
                    <input type="hidden" name="platform" value="{{ $platform }}">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username {{ ucfirst($platform) }}</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" name="username" class="form-control"
                                placeholder="username_anda" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Profil (Opsional)</label>
                        <input type="text" name="profile_name" class="form-control" placeholder="Nama tampilan akun">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="{{ $icon }} me-2"></i>Hubungkan {{ ucfirst($platform) }}
                        </button>
                        <a href="{{ route('social-accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
