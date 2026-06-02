@extends('layouts.mantis')

@section('title', 'Inbox')
@section('page_title', 'Inbox')

@section('breadcrumb')
    <li class="breadcrumb-item active">Inbox</li>
@endsection

@section('content')
<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <h4 class="mb-0">{{ $stats['total_comments'] }}</h4>
                <small class="text-muted">Total Komentar</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <h4 class="mb-0 text-danger">{{ $stats['unreplied'] }}</h4>
                <small class="text-muted">Belum Dibalas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <h4 class="mb-0">{{ $stats['total_messages'] }}</h4>
                <small class="text-muted">Total DM</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <h4 class="mb-0 text-warning">{{ $stats['unread_messages'] }}</h4>
                <small class="text-muted">DM Belum Dibaca</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted me-2 fw-semibold">Filter:</span>
            <div class="d-flex gap-1">
                @foreach(['all' => 'Semua', 'unreplied' => 'Belum Dibalas', 'replied' => 'Sudah Dibalas'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['filter' => $val]) }}"
                    class="btn btn-sm {{ $filter === $val ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <span class="text-muted mx-2">|</span>
            <div class="d-flex gap-1">
                @foreach(['all' => 'Semua', 'comment' => 'Komentar', 'dm' => 'DM'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $val]) }}"
                    class="btn btn-sm {{ $type === $val ? 'btn-info text-white' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Comments -->
    @if($type === 'all' || $type === 'comment')
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-message-circle me-2 text-primary"></i>Komentar ({{ $comments->count() }})</h5>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                @forelse($comments as $comment)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-semibold">{{ $comment->username }}</span>
                            <span class="platform-badge platform-{{ $comment->platform }} ms-2" style="font-size:10px;">
                                {{ ucfirst($comment->platform) }}
                            </span>
                        </div>
                        @if(!$comment->is_replied)
                            <span class="badge bg-danger" style="font-size:10px;">Belum Dibalas</span>
                        @else
                            <span class="badge bg-success" style="font-size:10px;">Sudah Dibalas</span>
                        @endif
                    </div>
                    <p class="text-dark mb-2" style="font-size:13px;">{{ $comment->comment_text }}</p>
                    <small class="text-muted d-block mb-2">
                        {{ $comment->commented_at?->format('d M Y, H:i') ?? $comment->created_at->format('d M Y, H:i') }}
                    </small>

                    <!-- Replies -->
                    @foreach($comment->replies as $reply)
                    <div class="ms-3 p-2 rounded mb-2" style="background:#f0f4ff;border-left:3px solid #4680ff;">
                        <div class="d-flex justify-content-between">
                            <small class="fw-semibold">Balasan Anda</small>
                            <span class="badge {{ $reply->source === 'ai' ? 'bg-info' : 'bg-secondary' }}" style="font-size:10px;">
                                {{ $reply->source === 'ai' ? '🤖 AI' : 'Manual' }}
                            </span>
                        </div>
                        <p class="mb-0 mt-1" style="font-size:12px;">{{ $reply->reply_text }}</p>
                    </div>
                    @endforeach

                    <!-- Reply Form -->
                    @if(!$comment->is_replied)
                    <div class="mt-2">
                        <form action="{{ route('inbox.reply-comment', $comment) }}" method="POST" class="reply-form">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="text" name="reply_text" class="form-control"
                                    placeholder="Tulis balasan..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send"></i>
                                </button>
                            </div>
                        </form>
                        @if($tenant->package?->has_ai_reply)
                        <button type="button" class="btn btn-sm btn-outline-info mt-1 ai-reply-btn"
                            data-comment-id="{{ $comment->id }}"
                            data-comment-text="{{ addslashes($comment->comment_text) }}">
                            <i class="ti ti-robot me-1"></i>Generate AI Reply
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ti ti-message-circle" style="font-size:48px;color:#dee2e6;"></i>
                    <p class="text-muted mt-2">Tidak ada komentar</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <!-- DM Messages -->
    @if($type === 'all' || $type === 'dm')
    <div class="{{ $type === 'all' ? 'col-lg-6' : 'col-12' }}">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-mail me-2 text-success"></i>Direct Messages ({{ $messages->count() }})</h5>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                @forelse($messages as $message)
                <div class="p-3 border-bottom {{ !$message->is_read ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-semibold">{{ $message->sender_name }}</span>
                            <span class="platform-badge platform-{{ $message->platform }} ms-2" style="font-size:10px;">
                                {{ ucfirst($message->platform) }}
                            </span>
                        </div>
                        @if(!$message->is_read)
                            <form action="{{ route('inbox.mark-read', $message) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-xs btn-outline-success" style="font-size:11px;padding:2px 8px;">
                                    Tandai Dibaca
                                </button>
                            </form>
                        @else
                            <span class="badge bg-success" style="font-size:10px;">Dibaca</span>
                        @endif
                    </div>
                    <p class="text-dark mb-1" style="font-size:13px;">{{ $message->message_text }}</p>
                    <small class="text-muted">{{ $message->received_at?->format('d M Y, H:i') ?? $message->created_at->format('d M Y, H:i') }}</small>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ti ti-mail" style="font-size:48px;color:#dee2e6;"></i>
                    <p class="text-muted mt-2">Tidak ada pesan DM</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>

<!-- AI Reply Modal -->
<div class="modal fade" id="aiReplyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-robot me-2 text-info"></i>AI Reply Generator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ai-original-comment" class="p-3 rounded mb-3" style="background:#f8f9fa;">
                    <small class="text-muted">Komentar:</small>
                    <p class="mb-0 fw-semibold" id="ai-comment-text"></p>
                </div>
                <div id="ai-loading" class="text-center py-3 d-none">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2 text-muted">AI sedang membuat balasan...</p>
                </div>
                <div id="ai-result" class="d-none">
                    <label class="form-label fw-semibold">Balasan yang Dihasilkan:</label>
                    <textarea id="ai-reply-text" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-generate" class="btn btn-info text-white">
                    <i class="ti ti-sparkles me-1"></i>Generate
                </button>
                <button type="button" id="btn-send-ai-reply" class="btn btn-primary d-none">
                    <i class="ti ti-send me-1"></i>Kirim Balasan
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentCommentId = null;

document.querySelectorAll('.ai-reply-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        currentCommentId = this.dataset.commentId;
        document.getElementById('ai-comment-text').textContent = this.dataset.commentText;
        document.getElementById('ai-result').classList.add('d-none');
        document.getElementById('ai-loading').classList.add('d-none');
        document.getElementById('btn-send-ai-reply').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('aiReplyModal')).show();
    });
});

document.getElementById('btn-generate').addEventListener('click', function () {
    if (!currentCommentId) return;

    document.getElementById('ai-loading').classList.remove('d-none');
    document.getElementById('ai-result').classList.add('d-none');

    fetch(`/ai/generate-reply/${currentCommentId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('ai-loading').classList.add('d-none');
        if (data.reply) {
            document.getElementById('ai-reply-text').value = data.reply;
            document.getElementById('ai-result').classList.remove('d-none');
            document.getElementById('btn-send-ai-reply').classList.remove('d-none');
        } else {
            alert(data.error || 'Gagal menghasilkan balasan.');
        }
    })
    .catch(() => {
        document.getElementById('ai-loading').classList.add('d-none');
        alert('Terjadi kesalahan.');
    });
});

document.getElementById('btn-send-ai-reply').addEventListener('click', function () {
    const replyText = document.getElementById('ai-reply-text').value;
    if (!replyText || !currentCommentId) return;

    fetch(`/ai/save-reply/${currentCommentId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ reply_text: replyText }),
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('aiReplyModal')).hide();
            location.reload();
        }
    });
});
</script>
@endpush
