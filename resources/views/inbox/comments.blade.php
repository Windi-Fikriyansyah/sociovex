@extends('layouts.mantis')

@section('title', 'Comments - Inbox')
@section('page_title', 'Comments')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inbox.index') }}">Inbox</a></li>
    <li class="breadcrumb-item active">Comments</li>
@endsection

@section('content')
    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0">{{ $stats['total_comments'] }}</h4>
                    <small class="text-muted">Total Comments</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-danger">{{ $stats['unreplied'] }}</h4>
                    <small class="text-muted">Unreplied</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filters and Posts List -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-3">
                        <i class="ti ti-message-circle me-2 text-primary"></i>Posts
                        <span class="badge bg-primary ms-2">{{ $comments->total() }}</span>
                    </h5>

                    <!-- Filters -->
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Platform</label>
                            <select class="form-select form-select-sm"
                                onchange="location = new URL(window.location).searchParams.set('platform', this.value) || window.location">
                                <option value="all" {{ $platform === 'all' ? 'selected' : '' }}>All Platforms</option>
                                @foreach ($platforms as $plat)
                                    <option value="{{ $plat }}" {{ $platform === $plat ? 'selected' : '' }}>
                                        {{ ucfirst($plat) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label small fw-semibold mb-1">Account</label>
                            <select class="form-select form-select-sm"
                                onchange="location = new URL(window.location).searchParams.set('account', this.value) || window.location">
                                <option value="all" {{ $account === 'all' ? 'selected' : '' }}>All Accounts</option>
                                @foreach ($socialAccounts as $acc)
                                    @if ($platform === 'all' || $acc->platform === $platform)
                                        <option value="{{ $acc->id }}" {{ $account == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->username }} ({{ ucfirst($acc->platform) }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label small fw-semibold mb-1">Status</label>
                            <div class="btn-group w-100" role="group">
                                @foreach (['all' => 'All', 'unreplied' => 'Unreplied', 'replied' => 'Replied'] as $val => $label)
                                    <a href="{{ request()->fullUrlWithQuery(['filter' => $val]) }}"
                                        class="btn btn-sm {{ $filter === $val ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posts with Comments List -->
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @forelse($comments as $comment)
                        <div class="p-3 border-bottom">
                            <!-- Post Preview -->
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span
                                            class="fw-semibold small d-block">{{ $comment->post?->content ? Str::limit($comment->post->content, 50) : 'Post' }}</span>
                                        <span
                                            class="platform-badge platform-{{ $comment->socialAccount->platform }} ms-0 mt-1"
                                            style="font-size: 10px; display: inline-flex;">
                                            <i class="{{ $comment->socialAccount->platform_icon }}"></i>
                                            {{ ucfirst($comment->socialAccount->platform) }}
                                        </span>
                                    </div>
                                    @if (!$comment->is_replied)
                                        <span class="badge bg-danger" style="font-size: 10px;">Unreplied</span>
                                    @else
                                        <span class="badge bg-success" style="font-size: 10px;">Replied</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Comment Preview -->
                            <div class="bg-light p-2 rounded mb-2" style="border-left: 3px solid #4680ff;">
                                <p class="text-dark mb-1" style="font-size: 12px;">
                                    <strong>{{ $comment->username }}</strong>:
                                </p>
                                <p class="text-dark mb-0" style="font-size: 12px;">
                                    {{ Str::limit($comment->comment_text, 100) }}
                                </p>
                            </div>

                            <small class="text-muted d-block">
                                {{ $comment->commented_at?->format('d M Y, H:i') ?? $comment->created_at->format('d M Y, H:i') }}
                            </small>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">
                            <i class="ti ti-message-circle" style="font-size: 40px; opacity: 0.5;"></i>
                            <p class="mt-3 mb-0">No comments yet</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($comments->hasPages())
                    <div class="card-footer">
                        {{ $comments->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Comment Detail -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-message-circle me-2"></i>Select a post
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 600px;">
                    <div class="text-center text-muted">
                        <i class="ti ti-message-circle" style="font-size: 60px; opacity: 0.3;"></i>
                        <p class="mt-3" style="font-size: 16px;">Select a post to view comments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
