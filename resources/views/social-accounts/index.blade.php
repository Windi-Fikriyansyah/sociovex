@extends('layouts.mantis')

@section('title', 'Akun Sosial Media')
@section('page_title', 'Akun Sosial Media')

@section('breadcrumb')
    <li class="breadcrumb-item active">Akun Sosial Media</li>
@endsection

@section('content')
<div class="row">
    <!-- Connect New Account -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-plug-connected me-2 text-primary"></i>Hubungkan Akun Baru</h5>
            </div>
            <div class="card-body">
                @php
                    $package = $tenant->package;
                    $currentCount = $accounts->where('status', 'active')->count();
                    $maxAccounts = $package?->max_social_accounts ?? 1;
                    $canAdd = $currentCount < $maxAccounts;
                @endphp

                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="ti ti-info-circle me-2"></i>
                    <div>
                        Terhubung: <strong>{{ $currentCount }}/{{ $maxAccounts }}</strong> akun
                        (Paket <strong>{{ $package?->name ?? 'Trial' }}</strong>)
                    </div>
                </div>

                @if(!$canAdd)
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Batas akun tercapai. <a href="{{ route('subscription.index') }}">Upgrade paket</a> untuk akun lebih banyak.
                    </div>
                @else
                    <form action="{{ route('social-accounts.connect') }}" method="POST">
                        @csrf
                        <p class="text-muted mb-3">Pilih platform yang ingin dihubungkan:</p>
                        <div class="row g-2">
                            @foreach([
                                ['instagram', 'ti ti-brand-instagram', '#E1306C', 'Instagram'],
                                ['facebook', 'ti ti-brand-facebook', '#1877F2', 'Facebook'],
                                ['linkedin', 'ti ti-brand-linkedin', '#0A66C2', 'LinkedIn'],
                                ['tiktok', 'ti ti-brand-tiktok', '#000', 'TikTok'],
                                ['threads', 'ti ti-brand-threads', '#000', 'Threads'],
                                ['x', 'ti ti-brand-x', '#1DA1F2', 'X (Twitter)'],
                                ['youtube', 'ti ti-brand-youtube', '#FF0000', 'YouTube'],
                            ] as [$slug, $icon, $color, $label])
                            <div class="col-6">
                                <button type="submit" name="platform" value="{{ $slug }}"
                                    class="btn w-100 d-flex align-items-center gap-2"
                                    style="border: 2px solid {{ $color }}; color: {{ $color }}; background: transparent; padding: 8px 12px; font-size:13px;">
                                    <i class="{{ $icon }}" style="font-size:18px;"></i>
                                    {{ $label }}
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Connected Accounts List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-list me-2"></i>Akun Terhubung ({{ $accounts->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @forelse($accounts as $account)
                <div class="d-flex align-items-center px-4 py-3 border-bottom">
                    <div class="flex-shrink-0">
                        @if($account->avatar)
                            <img src="{{ $account->avatar }}" alt="avatar" class="rounded-circle" width="48" height="48">
                        @else
                            <div style="width:48px;height:48px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;">
                                <i class="{{ $account->platform_icon }}" style="font-size:22px;color:{{ $account->platform_color }};"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fw-semibold">{{ $account->profile_name ?? $account->username }}</div>
                        <div class="text-muted" style="font-size:13px;">@{{ $account->username }}</div>
                        <span class="platform-badge platform-{{ $account->platform }} mt-1">
                            <i class="{{ $account->platform_icon }}"></i>
                            {{ ucfirst($account->platform) }}
                        </span>
                    </div>
                    <div class="text-center mx-3">
                        @if($account->status === 'active')
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Terputus</span>
                        @endif
                        <div class="text-muted mt-1" style="font-size:11px;">
                            {{ $account->connected_at?->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if($account->status === 'active')
                            <form action="{{ route('social-accounts.disconnect', $account) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                    onclick="return confirm('Putuskan akun ini?')">
                                    <i class="ti ti-plug-x"></i>
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('social-accounts.destroy', $account) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Hapus akun ini? Data terkait tidak akan dihapus.')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ti ti-plug-connected" style="font-size:64px;color:#dee2e6;"></i>
                    <h5 class="mt-3 text-muted">Belum ada akun terhubung</h5>
                    <p class="text-muted">Hubungkan akun sosial media Anda untuk mulai posting.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
