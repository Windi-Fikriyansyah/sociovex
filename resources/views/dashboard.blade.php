@extends('layouts.mantis')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#e8f0fe;color:#4680ff;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-plug-connected" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['total_accounts'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Akun Terhubung</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#e8f5e9;color:#2ecc71;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-news" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['total_posts'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Total Post</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#fff3e0;color:#f39c12;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-message-circle" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['total_comments'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Total Komentar</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#fce4ec;color:#e91e63;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-mail" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['total_dm'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Total DM</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#fce4ec;color:#e53935;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-message-dots" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['pending_replies'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Belum Dibalas</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon mb-2" style="background:#ede7f6;color:#673ab7;width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-calendar-time" style="font-size:22px;"></i>
                </div>
                <h3 class="mb-0">{{ $stats['scheduled'] }}</h3>
                <p class="text-muted mb-0 mt-1" style="font-size:13px;">Terjadwal</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Connected Accounts -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-plug-connected me-2 text-primary"></i>Akun Terhubung</h5>
                <a href="{{ route('social-accounts.index') }}" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body p-0">
                @forelse($socialAccounts as $account)
                    <div class="d-flex align-items-center px-3 py-3 border-bottom">
                        <div class="flex-shrink-0">
                            @if($account->avatar)
                                <img src="{{ $account->avatar }}" alt="avatar" class="rounded-circle" width="38" height="38">
                            @else
                                <div style="width:38px;height:38px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;">
                                    <i class="{{ $account->platform_icon }}" style="font-size:18px;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-0 fw-semibold">{{ $account->profile_name ?? $account->username }}</p>
                            <span class="platform-badge platform-{{ $account->platform }}">
                                <i class="{{ $account->platform_icon }}"></i>
                                {{ ucfirst($account->platform) }}
                            </span>
                        </div>
                        <span class="badge bg-success">Aktif</span>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ti ti-plug-connected" style="font-size:40px;color:#dee2e6;"></i>
                        <p class="text-muted mt-2 mb-3">Belum ada akun terhubung</p>
                        <a href="{{ route('social-accounts.index') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i> Hubungkan Akun
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Upcoming Scheduled -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-calendar me-2 text-warning"></i>Jadwal Terdekat</h5>
                <a href="{{ route('calendar.index') }}" class="btn btn-sm btn-outline-warning">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($upcomingScheduled as $scheduled)
                    <div class="px-3 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <p class="mb-1 fw-semibold" style="font-size:13px;">
                                {{ Str::limit($scheduled->caption, 60) }}
                            </p>
                            <span class="badge bg-warning text-dark ms-2" style="white-space:nowrap;font-size:10px;">
                                {{ $scheduled->scheduled_at->diffForHumans() }}
                            </span>
                        </div>
                        <div>
                            @foreach((array)$scheduled->platforms as $platform)
                                <span class="platform-badge platform-{{ $platform }} me-1">{{ ucfirst($platform) }}</span>
                            @endforeach
                        </div>
                        <small class="text-muted">{{ $scheduled->scheduled_at->format('d M Y, H:i') }}</small>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ti ti-calendar" style="font-size:40px;color:#dee2e6;"></i>
                        <p class="text-muted mt-2 mb-3">Tidak ada jadwal mendatang</p>
                        <a href="{{ route('posts.create') }}" class="btn btn-warning btn-sm">
                            <i class="ti ti-plus me-1"></i> Jadwalkan Post
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Comments -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-message-circle me-2 text-danger"></i>Komentar Terbaru</h5>
                <a href="{{ route('inbox.index') }}" class="btn btn-sm btn-outline-danger">Inbox</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentComments as $comment)
                    <div class="px-3 py-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold" style="font-size:13px;">{{ $comment->username }}</span>
                            @if(!$comment->is_replied)
                                <span class="badge bg-danger" style="font-size:10px;">Belum Dibalas</span>
                            @else
                                <span class="badge bg-success" style="font-size:10px;">Sudah Dibalas</span>
                            @endif
                        </div>
                        <p class="text-muted mb-1 mt-1" style="font-size:12px;">{{ Str::limit($comment->comment_text, 70) }}</p>
                        <small class="text-muted">{{ $comment->commented_at?->diffForHumans() ?? $comment->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ti ti-message-circle" style="font-size:40px;color:#dee2e6;"></i>
                        <p class="text-muted mt-2">Belum ada komentar masuk</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Posts -->
@if($recentPosts->count() > 0)
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-news me-2 text-success"></i>Post Terbaru</h5>
                <a href="{{ route('posts.index') }}" class="btn btn-sm btn-outline-success">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Caption</th>
                                <th>Platform</th>
                                <th>Akun</th>
                                <th>Dipublikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPosts as $post)
                            <tr>
                                <td>{{ Str::limit($post->caption, 60) }}</td>
                                <td>
                                    <span class="platform-badge platform-{{ $post->platform }}">
                                        {{ ucfirst($post->platform) }}
                                    </span>
                                </td>
                                <td>{{ $post->socialAccount?->username ?? '-' }}</td>
                                <td>{{ $post->published_at?->format('d M Y, H:i') ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Quick Actions -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="text-white mb-1">Selamat datang, {{ $tenant->business_name }}!</h5>
                        <p class="mb-0 opacity-75">
                            Paket: <strong>{{ $tenant->package?->name ?? 'Trial' }}</strong>
                            @if($tenant->expired_at)
                                &mdash; Aktif hingga {{ $tenant->expired_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('posts.create') }}" class="btn btn-light me-2">
                            <i class="ti ti-pencil-plus me-1"></i> Buat Post
                        </a>
                        <a href="{{ route('social-accounts.index') }}" class="btn btn-outline-light">
                            <i class="ti ti-plug-connected me-1"></i> Hubungkan Akun
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
