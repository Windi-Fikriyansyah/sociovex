@extends('layouts.mantis')

@section('title', 'Semua Post')
@section('page_title', 'Semua Post')

@section('breadcrumb')
    <li class="breadcrumb-item active">Post</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col">
        <a href="{{ route('posts.create') }}" class="btn btn-primary">
            <i class="ti ti-pencil-plus me-1"></i> Buat Post Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="ti ti-news me-2"></i>Post Terpublikasi ({{ $posts->total() }})</h5>
    </div>
    <div class="card-body p-0">
        @if($posts->isEmpty())
            <div class="text-center py-5">
                <i class="ti ti-news" style="font-size:64px;color:#dee2e6;"></i>
                <h5 class="mt-3 text-muted">Belum ada post</h5>
                <p class="text-muted">Buat post pertama Anda sekarang!</p>
                <a href="{{ route('posts.create') }}" class="btn btn-primary">
                    <i class="ti ti-pencil-plus me-1"></i> Buat Post
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Caption</th>
                            <th>Platform</th>
                            <th>Akun</th>
                            <th>Dipublikasi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                        <tr>
                            <td style="max-width:300px;">
                                <p class="mb-0" style="font-size:13px;">{{ Str::limit($post->caption, 80) }}</p>
                                @if($post->hashtags)
                                    <small class="text-primary">{{ Str::limit($post->hashtags, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="platform-badge platform-{{ $post->platform }}">
                                    {{ ucfirst($post->platform ?? '-') }}
                                </span>
                            </td>
                            <td>{{ $post->socialAccount?->username ?? '-' }}</td>
                            <td>
                                <div>{{ $post->published_at?->format('d M Y') ?? '-' }}</div>
                                <small class="text-muted">{{ $post->published_at?->format('H:i') ?? '' }}</small>
                            </td>
                            <td>
                                @if($post->post_url)
                                <a href="{{ $post->post_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-external-link"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
