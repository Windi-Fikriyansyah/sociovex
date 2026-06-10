@extends('layouts.mantis')

@section('title', 'Akun Sosial Media')
@section('page_title', 'Akun Sosial Media')

@section('breadcrumb')
    <li class="breadcrumb-item active">Akun Sosial Media</li>
@endsection

@section('content')

    <div class="row">
        {{-- ── Connect New Account ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-plug-connected me-2 text-primary"></i>Hubungkan Akun Baru
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $package = $tenant->package;
                        $currentCount = $accounts->where('status', 'active')->count();
                        $maxAccounts = $package?->max_social_accounts ?? 1;
                        $canAdd = $currentCount < $maxAccounts;
                    @endphp

                    <div class="alert alert-info d-flex align-items-center mb-3 py-2">
                        <i class="ti ti-info-circle me-2"></i>
                        <div>
                            Terhubung: <strong>{{ $currentCount }}/{{ $maxAccounts }}</strong> akun
                            &nbsp;·&nbsp; Paket <strong>{{ $package?->name ?? 'Trial' }}</strong>
                        </div>
                    </div>

                    @if (!$canAdd)
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            Batas akun tercapai.
                            <a href="{{ route('subscription.index') }}">Upgrade paket</a> untuk akun lebih banyak.
                        </div>
                    @else
                        <p class="text-muted mb-3 small">Pilih platform yang ingin dihubungkan:</p>
                        <div class="row g-2">
                            @foreach ([['instagram', 'ti ti-brand-instagram', '#E1306C', 'Instagram'], ['facebook', 'ti ti-brand-facebook', '#1877F2', 'Facebook'], ['linkedin', 'ti ti-brand-linkedin', '#0A66C2', 'LinkedIn'], ['tiktok', 'ti ti-brand-tiktok', '#010101', 'TikTok'], ['threads', 'ti ti-brand-threads', '#010101', 'Threads'], ['x', 'ti ti-brand-x', '#1DA1F2', 'X (Twitter)'], ['youtube', 'ti ti-brand-youtube', '#FF0000', 'YouTube']] as [$slug, $icon, $color, $label])
                                <div class="col-6">
                                    <a href="{{ route('social-accounts.connect', ['platform' => $slug]) }}"
                                        class="btn btn-sm w-100 d-flex align-items-center justify-content-start gap-2"
                                        style="border:2px solid {{ $color }};color:{{ $color }};background:transparent;">
                                        <i class="{{ $icon }}" style="font-size:17px;flex-shrink:0;"></i>
                                        <span>{{ $label }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Connected Accounts ──────────────────────────────────────────── --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-list me-2"></i>Akun Terhubung
                        <span class="badge bg-primary ms-1">{{ $accounts->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @forelse($accounts as $account)
                        <div class="d-flex align-items-center px-4 py-3 border-bottom">

                            {{-- Avatar --}}
                            <div class="flex-shrink-0">
                                @if ($account->avatar)
                                    <img src="{{ $account->avatar }}" alt="avatar" class="rounded-circle" width="46"
                                        height="46" style="object-fit:cover;">
                                @else
                                    <div
                                        style="width:46px;height:46px;border-radius:50%;
                                        background:#e9ecef;display:flex;
                                        align-items:center;justify-content:center;">
                                        <i class="{{ $account->platform_icon }}"
                                            style="font-size:22px;color:{{ $account->platform_color }};"></i>
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-grow-1 ms-3 overflow-hidden">
                                {{-- Display name: prefer username, fall back to profile_name --}}
                                @php
                                    $displayName = $account->username ?: $account->profile_name ?: '—';
                                @endphp
                                <div class="fw-semibold text-truncate">{{ $displayName }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span
                                        style="
                                display:inline-flex;align-items:center;gap:4px;
                                font-size:12px;padding:2px 8px;border-radius:20px;
                                background:{{ $account->platform_color }}22;
                                color:{{ $account->platform_color }};font-weight:600;">
                                        <i class="{{ $account->platform_icon }}"></i>
                                        {{ ucfirst($account->platform) }}
                                    </span>
                                    @if ($account->connected_at)
                                        <span class="text-muted" style="font-size:11px;">
                                            {{ $account->connected_at->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Status badge --}}
                            <div class="mx-3 text-center flex-shrink-0">
                                @if ($account->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Terputus</span>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="d-flex gap-2 flex-shrink-0">

                                <form action="{{ route('social-accounts.destroy', $account) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus akun"
                                        onclick="return confirm('Hapus akun {{ $account->username }}? Data terkait tidak ikut terhapus.')">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="ti ti-plug-connected" style="font-size:60px;color:#dee2e6;"></i>
                            <h5 class="mt-3 text-muted">Belum ada akun terhubung</h5>
                            <p class="text-muted small">Hubungkan akun sosial media Anda untuk mulai posting.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
