@extends('layouts.mantis')

@section('title', 'Messages - Inbox')
@section('page_title', 'Messages')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('inbox.index') }}">Inbox</a></li>
    <li class="breadcrumb-item active">Messages</li>
@endsection

@section('content')
    <div class="inbox-wrap">

        {{-- LEFT SIDEBAR --}}
        <div class="inbox-sidebar">

            <div class="inbox-sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="inbox-title">
                        Messages
                        @if ($messages->count())
                            <span class="inbox-badge">{{ $messages->count() }}</span>
                        @endif
                    </span>
                    {{-- <button class="inbox-icon-btn" title="New message">
                        <i class="ti ti-edit"></i>
                    </button> --}}
                </div>

                <div class="d-flex gap-2 mt-3">
                    <select class="inbox-filter-select" onchange="window.location=this.value">
                        <option value="{{ request()->fullUrlWithQuery(['platform' => 'all']) }}"
                            {{ $platform == 'all' ? 'selected' : '' }}>All platforms</option>
                        @foreach ($platforms as $plat)
                            <option value="{{ request()->fullUrlWithQuery(['platform' => $plat]) }}"
                                {{ $platform == $plat ? 'selected' : '' }}>
                                {{ ucfirst($plat) }}
                            </option>
                        @endforeach
                    </select>

                    <select class="inbox-filter-select" onchange="window.location=this.value">
                        <option value="{{ request()->fullUrlWithQuery(['account' => 'all']) }}">All accounts</option>
                        @foreach ($socialAccounts as $acc)
                            <option
    value="{{ request()->fullUrlWithQuery([
        'account' => $acc->zernio_account_id
    ]) }}"
    {{ $account == $acc->zernio_account_id ? 'selected' : '' }}>
                                {{ '@' . $acc->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="inbox-toolbar">
                <span class="inbox-toolbar-label">Conversations</span>
                <select
    class="inbox-sort-select"
    onchange="window.location=this.value">

    <option
        value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}"
        {{ $sort === 'newest' ? 'selected' : '' }}>
        Newest first
    </option>

    <option
        value="{{ request()->fullUrlWithQuery(['sort' => 'oldest']) }}"
        {{ $sort === 'oldest' ? 'selected' : '' }}>
        Oldest first
    </option>
</select>
            </div>

            <div class="inbox-conv-list">
                @forelse($messages as $message)
                    @php
    $sender = trim($message['participantName'] ?? '');

    $text = trim($message['lastMessage'] ?? '');

    $platform = $message['platform'] ?? 'instagram';

    $updated =
        $message['updatedTime']
        ?? now();

    $convId =
        $message['id']
        ?? null;

    $participantPicture =
        $message['participantPicture']
        ?? null;

    $accountUsername =
        $message['accountUsername']
        ?? '';

    $initial = strtoupper(substr($sender, 0, 1));

    // fallback minimal (bukan fake id)
    if ($sender === '') {
        $sender = 'Instagram User';
    }

    if ($text === '') {
        $text = '[Attachment]';
    }
@endphp

                    <div class="inbox-conv-item {{ $loop->first ? 'active' : '' }}"
    data-id="{{ $convId }}"
    data-name="{{ $sender }}"
    data-platform="{{ $platform }}"
    data-via="{{ '@'.$accountUsername }}"
    data-avatar="{{ $participantPicture }}"
    data-initial="{{ $initial }}"
    onclick="openConversation(this, '{{ $convId }}')">

                        <div class="inbox-avatar {{ $platform }}">

    @if($participantPicture)
        <img src="{{ $participantPicture }}"
             alt="{{ $sender }}">
    @else
        {{ $initial }}
    @endif

</div>

                        <div class="inbox-conv-body">
                            <div class="inbox-conv-top">
                                <span class="inbox-conv-name">{{ $sender }}</span>
                                <span class="inbox-conv-time">
                                    {{ \Carbon\Carbon::parse($updated)->shortAbsoluteDiffForHumans() }}
                                </span>
                            </div>
                            <div class="inbox-conv-via">
    via {{ '@'.$accountUsername }}
</div>
                            <div class="inbox-conv-preview">
                                @if ($platform === 'instagram')
                                    <span class="platform-dot instagram">
                                        <i class="ti ti-brand-instagram"></i>
                                    </span>
                                @elseif ($platform === 'facebook')
                                    <span class="platform-dot facebook">
                                        <i class="ti ti-brand-facebook"></i>
                                    </span>
                                @endif
                                {{ \Illuminate\Support\Str::limit($text, 42) }}
                            </div>
                        </div>
                    </div>

                @empty
                    <div class="inbox-empty-list">
                        <i class="ti ti-inbox-off"></i>
                        <span>No messages yet</span>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT PANEL --}}
        <div class="inbox-panel" id="inbox-panel">

            <div class="inbox-panel-empty" id="panel-empty">
                <i class="ti ti-messages"></i>
                <p>Select a conversation to view messages</p>
            </div>

            <div class="inbox-panel-active d-none" id="panel-active">
                <div class="inbox-panel-header" id="panel-header">
                    <div class="inbox-avatar" id="panel-avatar">-</div>
                    <div>
                        <div class="inbox-panel-name" id="panel-name">-</div>
                        <div class="inbox-panel-sub" id="panel-via">-</div>
                    </div>
                    <div class="ms-auto d-flex gap-1">
                        <button class="inbox-icon-btn" title="More options"><i class="ti ti-dots"></i></button>
                        <button class="inbox-icon-btn" title="Close"><i class="ti ti-x"></i></button>
                    </div>
                </div>

                <div class="inbox-messages-body" id="messages-body">
                    {{-- Messages injected via JS --}}
                </div>

                <div class="inbox-reply-bar">
                    <button class="inbox-icon-btn" title="Attach"><i class="ti ti-paperclip"></i></button>
                    <input type="text" id="reply-input" class="inbox-reply-input" placeholder="Type a message…">
                    <button class="inbox-send-btn" onclick="sendReply()" title="Send">
                        <i class="ti ti-send"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ──────────────────────────────────────
                   LAYOUT
                ────────────────────────────────────── */

                .inbox-avatar{
    width:36px;
    height:36px;
    min-width:36px;
    border-radius:50%;
    overflow:hidden;
    background:#f3f3f3;
    border:1px solid #ebebeb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    font-weight:500;
    color:#666;
}

.inbox-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}
        .inbox-wrap {
            display: flex;
            height: calc(100vh - 148px);
            min-height: 480px;
            border: 1px solid #e8eaed;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        /* ──────────────────────────────────────
                   SIDEBAR
                ────────────────────────────────────── */
        .inbox-sidebar {
            width: 300px;
            min-width: 300px;
            border-right: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .inbox-sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .inbox-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .inbox-badge {
            background: #e8f0fc;
            color: #185FA5;
            font-size: 11px;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 99px;
        }

        .inbox-icon-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            background: #fff;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.12s, border-color 0.12s;
        }

        .inbox-icon-btn:hover {
            background: #f5f5f5;
            border-color: #d0d0d0;
        }

        .inbox-filter-select {
            flex: 1;
            height: 30px;
            font-size: 12px;
            border: 1px solid #e8eaed;
            border-radius: 7px;
            padding: 0 8px;
            background: #fafafa;
            color: #444;
            cursor: pointer;
            outline: none;
        }

        .inbox-filter-select:focus {
            border-color: #a8c4e8;
        }

        /* toolbar */
        .inbox-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            border-bottom: 1px solid #f5f5f5;
        }

        .inbox-toolbar-label {
            font-size: 11px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .inbox-sort-select {
            font-size: 11px;
            height: 24px;
            border: 1px solid #e8eaed;
            border-radius: 6px;
            padding: 0 6px;
            background: #fff;
            color: #666;
            outline: none;
        }

        /* conversation list */
        .inbox-conv-list {
            flex: 1;
            overflow-y: auto;
        }

        .inbox-conv-list::-webkit-scrollbar {
            width: 4px;
        }

        .inbox-conv-list::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 4px;
        }

        .inbox-conv-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 11px 16px;
            border-bottom: 1px solid #f7f7f7;
            cursor: pointer;
            transition: background 0.12s;
            position: relative;
        }

        .inbox-conv-item:hover {
            background: #fafafa;
        }

        .inbox-conv-item.active {
            background: #f3f7fd;
        }

        /* avatar */
        .inbox-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 50%;
            background: #f3f3f3;
            border: 1px solid #ebebeb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 500;
            color: #666;
        }

        .inbox-avatar.instagram {
            background: #fff0f5;
            color: #c2185b;
            border-color: #f8c0d5;
        }

        .inbox-avatar.facebook {
            background: #e8f0fc;
            color: #1565c0;
            border-color: #b3ccf0;
        }

        .inbox-avatar.twitter {
            background: #e8f4fd;
            color: #0277bd;
            border-color: #b3d9f5;
        }

        /* conv body */
        .inbox-conv-body {
            flex: 1;
            min-width: 0;
        }

        .inbox-conv-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 6px;
        }

        .inbox-conv-name {
            font-size: 13px;
            font-weight: 500;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .inbox-conv-time {
            font-size: 11px;
            color: #aaa;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .inbox-conv-via {
            font-size: 11px;
            color: #bbb;
            margin-top: 1px;
        }

        .inbox-conv-preview {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
            font-size: 12px;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* platform dots */
        .platform-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            flex-shrink: 0;
        }

        .platform-dot.instagram {
            background: #fff0f5;
            color: #c2185b;
        }

        .platform-dot.facebook {
            background: #e8f0fc;
            color: #1565c0;
        }

        /* empty sidebar */
        .inbox-empty-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 40px 20px;
            color: #bbb;
            font-size: 13px;
        }

        .inbox-empty-list i {
            font-size: 28px;
        }

        /* ──────────────────────────────────────
                   RIGHT PANEL
                ────────────────────────────────────── */
        .inbox-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* empty state */
        .inbox-panel-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #ccc;
        }

        .inbox-panel-empty i {
            font-size: 40px;
        }

        .inbox-panel-empty p {
            font-size: 14px;
            color: #bbb;
            margin: 0;
        }

        /* active panel */
        .inbox-panel-active {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .inbox-panel-header {
            height: 58px;
            padding: 0 18px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .inbox-panel-name {
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }

        .inbox-panel-sub {
            font-size: 11px;
            color: #aaa;
        }

        /* messages body */
        .inbox-messages-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .inbox-messages-body::-webkit-scrollbar {
            width: 4px;
        }

        .inbox-messages-body::-webkit-scrollbar-thumb {
            background: #e8e8e8;
            border-radius: 4px;
        }

        .msg-group {
            display: flex;
            flex-direction: column;
        }

        .msg-ts {
            font-size: 10px;
            color: #bbb;
            margin-bottom: 3px;
        }

        .msg-ts.out {
            text-align: right;
        }

        .msg-row {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .msg-row.out {
            flex-direction: row-reverse;
        }

        .msg-bubble {
            max-width: 68%;
            padding: 8px 13px;
            font-size: 13px;
            line-height: 1.55;
            border-radius: 14px;
        }

        .msg-bubble.in {
            background: #f4f5f6;
            color: #1a1a1a;
            border-bottom-left-radius: 4px;
        }

        .msg-bubble.out {
            background: #185FA5;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        /* reply bar */
        .inbox-reply-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid #f0f0f0;
            flex-shrink: 0;
        }

        .inbox-reply-input {
            flex: 1;
            height: 36px;
            border: 1px solid #e8eaed;
            border-radius: 18px;
            padding: 0 14px;
            font-size: 13px;
            background: #fafafa;
            color: #1a1a1a;
            outline: none;
            transition: border-color 0.15s;
        }

        .inbox-reply-input:focus {
            border-color: #a8c4e8;
            background: #fff;
        }

        .inbox-send-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: #185FA5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            flex-shrink: 0;
        }

        .inbox-send-btn:hover {
            background: #0d4a8a;
        }

        .inbox-send-btn:active {
            transform: scale(0.94);
        }

        /* ──────────────────────────────────────
                   RESPONSIVE
                ────────────────────────────────────── */
        @media (max-width: 768px) {
            .inbox-sidebar {
                width: 100%;
                min-width: 0;
            }

            .inbox-panel {
                display: none;
            }

            .inbox-sidebar.panel-open {
                display: none;
            }

            .inbox-panel.panel-open {
                display: flex;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
async function openConversation(el, id)
{
    document.querySelectorAll('.inbox-conv-item')
        .forEach(i => i.classList.remove('active'));

    el.classList.add('active');

    const name = el.dataset.name;
    const via = el.dataset.via;
    const avatarUrl = el.dataset.avatar;
    const initial = el.dataset.initial;
    const platform = el.dataset.platform;

    document.getElementById('panel-empty')
        .classList.add('d-none');

    document.getElementById('panel-active')
        .classList.remove('d-none');

    const avatar = document.getElementById('panel-avatar');

    avatar.className = 'inbox-avatar ' + platform;

    if (avatarUrl) {
        avatar.innerHTML =
            `<img src="${avatarUrl}" alt="">`;
    } else {
        avatar.textContent = initial;
    }

    document.getElementById('panel-name')
        .textContent = name;

    document.getElementById('panel-via')
        .textContent = via;

    const body = document.getElementById('messages-body');

    body.innerHTML = `
        <div class="text-center text-muted py-3">
            Loading messages...
        </div>
    `;

    try {

        const res = await fetch(
            `/inbox/messages/${id}`
        );

        const json = await res.json();

        if (!json.success) {
            throw new Error('Failed');
        }

        const messages = json.data ?? [];

        if (!messages.length) {
            body.innerHTML = `
                <div class="text-center text-muted py-5">
                    No messages
                </div>
            `;
            return;
        }

        body.innerHTML = messages.map(msg => {

            const isOut =
                msg.direction === 'outgoing'
                || msg.isMine
                || msg.senderType === 'business';

            const text =
                escapeHtml(
                    msg.message
                    || msg.text
                    || msg.content
                    || '[Attachment]'
                );

            const time =
                msg.createdAt
                || msg.created_at
                || '';

            return `
                <div class="msg-group">

                    <div class="msg-ts ${isOut ? 'out' : ''}">
                        ${formatTime(time)}
                    </div>

                    <div class="msg-row ${isOut ? 'out' : ''}">

                        ${
                            !isOut
                            ? `<div class="inbox-avatar ${platform}"
                                style="width:26px;height:26px;min-width:26px;font-size:11px">
                                ${
                                    avatarUrl
                                    ? `<img src="${avatarUrl}">`
                                    : initial
                                }
                            </div>`
                            : ''
                        }

                        <div class="msg-bubble ${isOut ? 'out' : 'in'}">
                            ${text}
                        </div>

                    </div>
                </div>
            `;
        }).join('');

        body.scrollTop = body.scrollHeight;

    } catch (e) {

        body.innerHTML = `
            <div class="text-danger text-center py-4">
                Failed load messages
            </div>
        `;
    }
}

function formatTime(date)
{
    if (!date) return '';

    return new Date(date)
        .toLocaleString();
}

function escapeHtml(text)
{
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function sendReply()
{
    const input =
        document.getElementById('reply-input');

    const text = input.value.trim();

    if (!text) return;

    input.value = '';
}

document
    .getElementById('reply-input')
    .addEventListener('keydown', function(e){

        if (
            e.key === 'Enter'
            && !e.shiftKey
        ) {
            e.preventDefault();
            sendReply();
        }
    });

window.addEventListener('load', () => {

    const first =
        document.querySelector(
            '.inbox-conv-item'
        );

    if (first) {
        openConversation(
            first,
            first.dataset.id
        );
    }
});
</script>
@endpush
