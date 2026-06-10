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
        <div class="inbox-sidebar" id="inbox-sidebar">

            <div class="inbox-sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="inbox-title">
                        Messages
                        @if ($stats['unread_messages'] > 0)
                            <span class="inbox-badge inbox-badge-unread" id="unread-badge">{{ $stats['unread_messages'] }} unread</span>
                        @else
                            <span class="inbox-badge inbox-badge-unread" id="unread-badge" style="display:none;">0 unread</span>
                        @endif
                        <span class="inbox-badge" id="total-badge">{{ $stats['total_messages'] }}</span>
                    </span>
                    <button class="inbox-icon-btn" title="Refresh" onclick="location.reload()">
                        <i class="ti ti-refresh"></i>
                    </button>
                    <span id="ws-status" title="WebSocket: checking..." style="font-size:14px;cursor:help;">⚪</span>
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
                        <option value="{{ request()->fullUrlWithQuery(['account' => 'all']) }}"
                            {{ $account == 'all' ? 'selected' : '' }}>All accounts</option>
                        @foreach ($socialAccounts as $acc)
                            <option
                                value="{{ request()->fullUrlWithQuery(['account' => $acc->zernio_account_id]) }}"
                                {{ $account == $acc->zernio_account_id ? 'selected' : '' }}">
                                {{ '@' . $acc->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="inbox-toolbar">
                <span class="inbox-toolbar-label">Conversations</span>
                <select class="inbox-sort-select" onchange="window.location=this.value">
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}"
                        {{ $sort === 'newest' ? 'selected' : '' }}>Newest first</option>
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'oldest']) }}"
                        {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                </select>
            </div>

            <div class="inbox-conv-list" id="conv-list">
                @forelse($messages as $conv)
                    @php
                        $sender           = trim($conv->participant_name ?? '');
                        $text             = trim($conv->last_message ?? '');
                        $convPlatform     = $conv->platform ?? 'instagram';
                        $updated          = $conv->last_message_at ?? now();
                        $convId           = $conv->zernio_conversation_id ?? null;
                        $participantPic   = $conv->participant_picture ?? null;
                        $accountUsername  = $conv->account_username ?? '';
                        $zernioAccountId  = $conv->zernio_account_id ?? '';
                        $unreadCount      = (int) ($conv->unread_count ?? 0);
                        $isUnread         = $unreadCount > 0;
                        $initial          = strtoupper(substr($sender ?: 'U', 0, 1));
                        $localId          = $conv->id;

                        if ($sender === '') $sender = 'User';
                        if ($text === '')   $text = '[Attachment]';
                    @endphp

                    <div class="inbox-conv-item {{ $loop->first ? 'active' : '' }} {{ $isUnread ? 'unread' : '' }}"
                        data-id="{{ $convId }}"
                        data-local-id="{{ $localId }}"
                        data-account-id="{{ $zernioAccountId }}"
                        data-name="{{ $sender }}"
                        data-platform="{{ $convPlatform }}"
                        data-via="{{ '@' . $accountUsername }}"
                        data-avatar="{{ $participantPic }}"
                        data-initial="{{ $initial }}"
                        data-unread="{{ $unreadCount }}"
                        onclick="openConversation(this, '{{ $convId }}')">

                        <div class="inbox-avatar {{ $convPlatform }}">
                            @if($participantPic)
                                <img src="{{ $participantPic }}" alt="{{ $sender }}">
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
                            <div class="inbox-conv-via">via {{ '@' . $accountUsername }}</div>
                            <div class="inbox-conv-preview">
                                @if ($convPlatform === 'instagram')
                                    <span class="platform-dot instagram"><i class="ti ti-brand-instagram"></i></span>
                                @elseif ($convPlatform === 'facebook')
                                    <span class="platform-dot facebook"><i class="ti ti-brand-facebook"></i></span>
                                @elseif ($convPlatform === 'tiktok')
                                    <span class="platform-dot tiktok"><i class="ti ti-brand-tiktok"></i></span>
                                @endif
                                <span class="preview-text">{{ \Illuminate\Support\Str::limit($text, 38) }}</span>
                                @if ($isUnread)
                                    <span class="unread-badge-count" style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:99px;margin-left:auto;flex-shrink:0;">{{ $unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="inbox-empty-list">
                        <i class="ti ti-inbox-off"></i>
                        <span>No conversations found</span>
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
                    <button class="inbox-icon-btn inbox-back-btn d-md-none" onclick="closePanel()" title="Back">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <div class="inbox-avatar" id="panel-avatar">-</div>
                    <div>
                        <div class="inbox-panel-name" id="panel-name">-</div>
                        <div class="inbox-panel-sub" id="panel-via">-</div>
                    </div>
                    <div class="ms-auto d-flex gap-1">
                        <button class="inbox-icon-btn" title="Refresh messages" onclick="refreshConversation()">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button class="inbox-icon-btn d-md-none" title="Close" onclick="closePanel()">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>

                <div class="inbox-messages-body" id="messages-body">
                    {{-- Messages injected via JS --}}
                </div>

                <div class="inbox-reply-bar" id="reply-bar">
                    <input type="text" id="reply-input" class="inbox-reply-input"
                        placeholder="Type a message..." maxlength="2200">
                    <button class="inbox-send-btn" id="send-btn" onclick="sendReply()" title="Send">
                        <i class="ti ti-send"></i>
                    </button>
                </div>

                {{-- Sending status indicator --}}
                <div class="inbox-sending d-none" id="sending-indicator">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span>Sending...</span>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
<style>
/* ── LAYOUT ── */
.inbox-wrap {
    display: flex;
    height: calc(100vh - 148px);
    min-height: 480px;
    border: 1px solid #e8eaed;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

/* ── SIDEBAR ── */
.inbox-sidebar {
    width: 320px;
    min-width: 320px;
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
    font-size: 10px;
    font-weight: 600;
    padding: 1px 7px;
    border-radius: 99px;
}

.inbox-badge-unread {
    background: #fee2e2;
    color: #dc2626;
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

.inbox-filter-select:focus { border-color: #a8c4e8; }

/* conversation list */
.inbox-conv-list {
    flex: 1;
    overflow-y: auto;
}

.inbox-conv-list::-webkit-scrollbar { width: 4px; }
.inbox-conv-list::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 4px; }

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

.inbox-conv-item:hover { background: #fafafa; }
.inbox-conv-item.active { background: #f3f7fd; }

.inbox-conv-item.unread .inbox-conv-name { font-weight: 700; color: #000; }
.inbox-conv-item.unread .preview-text { font-weight: 600; color: #333; }

/* avatar */
.inbox-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    overflow: hidden;
    background: #f3f3f3;
    border: 1px solid #ebebeb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 500;
    color: #666;
}

.inbox-avatar img { width: 100%; height: 100%; object-fit: cover; }

.inbox-avatar.instagram { background: #fff0f5; color: #c2185b; border-color: #f8c0d5; }
.inbox-avatar.facebook { background: #e8f0fc; color: #1565c0; border-color: #b3ccf0; }
.inbox-avatar.tiktok { background: #f0f0f0; color: #000; border-color: #ccc; }
.inbox-avatar.twitter, .inbox-avatar.x { background: #e8f4fd; color: #0277bd; border-color: #b3d9f5; }

/* conv body */
.inbox-conv-body { flex: 1; min-width: 0; }

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

.inbox-conv-via { font-size: 11px; color: #bbb; margin-top: 1px; }

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

.preview-text {
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

.platform-dot.instagram { background: #fff0f5; color: #c2185b; }
.platform-dot.facebook { background: #e8f0fc; color: #1565c0; }
.platform-dot.tiktok { background: #f0f0f0; color: #000; }

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

.inbox-empty-list i { font-size: 28px; }

/* ── RIGHT PANEL ── */
.inbox-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

.inbox-panel-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #ccc;
}

.inbox-panel-empty i { font-size: 40px; }
.inbox-panel-empty p { font-size: 14px; color: #bbb; margin: 0; }

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

.inbox-panel-name { font-size: 14px; font-weight: 600; color: #1a1a1a; }
.inbox-panel-sub { font-size: 11px; color: #aaa; }

.inbox-back-btn { margin-right: 4px; }

/* messages body */
.inbox-messages-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.inbox-messages-body::-webkit-scrollbar { width: 4px; }
.inbox-messages-body::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 4px; }

.msg-group { display: flex; flex-direction: column; }

.msg-ts { font-size: 10px; color: #bbb; margin-bottom: 3px; }
.msg-ts.out { text-align: right; }

.msg-row { display: flex; gap: 8px; align-items: flex-end; }
.msg-row.out { flex-direction: row-reverse; }

.msg-bubble {
    max-width: 68%;
    padding: 8px 13px;
    font-size: 13px;
    line-height: 1.55;
    border-radius: 14px;
    word-break: break-word;
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

.msg-bubble.sending {
    opacity: 0.6;
}

.msg-status {
    font-size: 10px;
    color: #aaa;
    text-align: right;
    margin-top: 2px;
}

.msg-status.sent { color: #4caf50; }
.msg-status.failed { color: #dc2626; }

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
    height: 38px;
    border: 1px solid #e8eaed;
    border-radius: 19px;
    padding: 0 16px;
    font-size: 13px;
    background: #fafafa;
    color: #1a1a1a;
    outline: none;
    transition: border-color 0.15s;
}

.inbox-reply-input:focus { border-color: #a8c4e8; background: #fff; }

.inbox-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: #185FA5;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
    flex-shrink: 0;
}

.inbox-send-btn:hover { background: #0d4a8a; }
.inbox-send-btn:active { transform: scale(0.94); }
.inbox-send-btn:disabled { background: #ccc; cursor: not-allowed; }

/* sending indicator */
.inbox-sending {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 18px;
    font-size: 12px;
    color: #888;
    border-top: 1px solid #f5f5f5;
}

/* icon button */
.inbox-icon-btn {
    width: 32px;
    height: 32px;
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

.inbox-icon-btn:hover { background: #f5f5f5; border-color: #d0d0d0; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .inbox-sidebar { width: 100%; min-width: 0; }
    .inbox-panel { display: none; }
    .inbox-sidebar.panel-hidden { display: none; }
    .inbox-panel.panel-open { display: flex; }
}

/* ── ANIMATIONS ── */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); background: #e8f4fd; }
    to { opacity: 1; transform: translateY(0); background: transparent; }
}

.inbox-conv-item.new-flash {
    animation: fadeIn 0.6s ease;
}

/* ── WebSocket status indicator ── */
.ws-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-left: 4px;
}
.ws-status.connected { background: #4caf50; }
.ws-status.disconnected { background: #dc2626; }
.ws-status.connecting { background: #ff9800; animation: pulse-dot 1s ease-in-out infinite; }
</style>
@endpush

@push('scripts')
<script>
// ────────────────────────────────────────────
// State
// ────────────────────────────────────────────
let currentConversationId = null;  // zernio_conversation_id
let currentLocalId        = null;  // local DB id
let currentAccountId      = null;
let currentPlatform       = null;
let currentAvatar         = null;
let currentInitial        = null;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Notification sound (short beep)
const notifySound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACA');

// ────────────────────────────────────────────
// Open conversation
// ────────────────────────────────────────────
async function openConversation(el, id) {
    // Update active state
    document.querySelectorAll('.inbox-conv-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');

    // Remove unread styling since user opened it
    el.classList.remove('unread');
    const badge = el.querySelector('.unread-badge-count');
    if (badge) badge.remove();
    el.dataset.unread = '0';

    // Store state
    currentConversationId = id;
    currentLocalId        = el.dataset.localId || '';
    currentAccountId      = el.dataset.accountId || '';
    currentPlatform       = el.dataset.platform;
    currentAvatar         = el.dataset.avatar;
    currentInitial        = el.dataset.initial;
    const name = el.dataset.name;
    const via  = el.dataset.via;

    // Show panel
    document.getElementById('panel-empty').classList.add('d-none');
    document.getElementById('panel-active').classList.remove('d-none');

    // Mobile: show panel, hide sidebar
    if (window.innerWidth <= 768) {
        document.getElementById('inbox-sidebar').classList.add('panel-hidden');
        document.getElementById('inbox-panel').classList.add('panel-open');
    }

    // Update header
    const avatar = document.getElementById('panel-avatar');
    avatar.className = 'inbox-avatar ' + currentPlatform;
    avatar.innerHTML = currentAvatar
        ? `<img src="${currentAvatar}" alt="">`
        : currentInitial;

    document.getElementById('panel-name').textContent = name;
    document.getElementById('panel-via').textContent = via;

    // Load messages
    await loadMessages(id);

    // Mark as read
    markAsRead(id);
}

// ────────────────────────────────────────────
// Load messages for a conversation
// ────────────────────────────────────────────
async function loadMessages(id) {
    const body = document.getElementById('messages-body');
    body.innerHTML = `<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading messages...</div>`;

    try {
        const res = await fetch(`/inbox/messages/${encodeURIComponent(id)}?accountId=${encodeURIComponent(currentAccountId)}`);
        const json = await res.json();

        if (!json.success) throw new Error(json.message || 'Failed');

        const messages = json.data ?? [];

        if (!messages.length) {
            body.innerHTML = `<div class="text-center text-muted py-5"><i class="ti ti-messages" style="font-size:32px;display:block;margin-bottom:8px;"></i>No messages in this conversation</div>`;
            return;
        }

        renderMessages(messages);
        body.scrollTop = body.scrollHeight;

    } catch (e) {
        body.innerHTML = `<div class="text-danger text-center py-4"><i class="ti ti-alert-circle me-1"></i>Failed to load messages<br><small>${escapeHtml(e.message)}</small></div>`;
    }
}

// ────────────────────────────────────────────
// Render messages
// ────────────────────────────────────────────
function renderMessages(messages) {
    const body = document.getElementById('messages-body');

    body.innerHTML = messages.map(msg => {
        const isOut = msg.direction === 'outgoing'
            || msg.isMine === true
            || msg.senderType === 'business'
            || msg.from?.type === 'page';

        const text = escapeHtml(msg.message || msg.text || msg.content || '[Attachment]');
        const time = msg.createdAt || msg.created_at || '';

        return `
            <div class="msg-group">
                <div class="msg-ts ${isOut ? 'out' : ''}">${formatTime(time)}</div>
                <div class="msg-row ${isOut ? 'out' : ''}">
                    ${!isOut ? `
                        <div class="inbox-avatar ${currentPlatform}"
                            style="width:28px;height:28px;min-width:28px;font-size:11px;">
                            ${currentAvatar ? `<img src="${currentAvatar}">` : currentInitial}
                        </div>` : ''}
                    <div class="msg-bubble ${isOut ? 'out' : 'in'}">${text}</div>
                </div>
            </div>`;
    }).join('');

    body.scrollTop = body.scrollHeight;
}

// ────────────────────────────────────────────
// Append a single new message (from WebSocket)
// ────────────────────────────────────────────
function appendMessage(msg) {
    const body = document.getElementById('messages-body');
    if (!body) return;

    const isOut = msg.direction === 'outgoing';
    const text = escapeHtml(msg.message_text || msg.message || msg.text || '');
    const time = msg.received_at || msg.createdAt || new Date().toISOString();

    const html = `
        <div class="msg-group new-flash">
            <div class="msg-ts ${isOut ? 'out' : ''}">${formatTime(time)}</div>
            <div class="msg-row ${isOut ? 'out' : ''}">
                ${!isOut ? `
                    <div class="inbox-avatar ${currentPlatform}"
                        style="width:28px;height:28px;min-width:28px;font-size:11px;">
                        ${currentAvatar ? `<img src="${currentAvatar}">` : currentInitial}
                    </div>` : ''}
                <div class="msg-bubble ${isOut ? 'out' : 'in'}">${text}</div>
            </div>
        </div>`;

    body.insertAdjacentHTML('beforeend', html);
    body.scrollTop = body.scrollHeight;
}

// ────────────────────────────────────────────
// Send reply
// ────────────────────────────────────────────
async function sendReply() {
    const input = document.getElementById('reply-input');
    const sendBtn = document.getElementById('send-btn');
    const text = input.value.trim();

    if (!text || !currentConversationId) return;

    // Disable input while sending
    input.value = '';
    input.disabled = true;
    sendBtn.disabled = true;

    // Append the outgoing message immediately (optimistic)
    const body = document.getElementById('messages-body');
    const tempId = 'msg-' + Date.now();
    body.insertAdjacentHTML('beforeend', `
        <div class="msg-group" id="${tempId}">
            <div class="msg-ts out">${formatTime(new Date().toISOString())}</div>
            <div class="msg-row out">
                <div class="msg-bubble out sending">${escapeHtml(text)}</div>
            </div>
            <div class="msg-status" id="${tempId}-status">Sending...</div>
        </div>
    `);
    body.scrollTop = body.scrollHeight;

    try {
        const res = await fetch(`/inbox/messages/${encodeURIComponent(currentConversationId)}/reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message: text,
                accountId: currentAccountId,
            }),
        });

        const json = await res.json();

        if (!json.success) throw new Error(json.message || 'Failed to send');

        // Update status
        const statusEl = document.getElementById(`${tempId}-status`);
        if (statusEl) {
            statusEl.textContent = '✓ Sent';
            statusEl.classList.add('sent');
        }

        // Remove sending opacity
        const bubble = document.querySelector(`#${tempId} .msg-bubble`);
        if (bubble) bubble.classList.remove('sending');

        // Remove status after 3 seconds
        setTimeout(() => {
            const s = document.getElementById(`${tempId}-status`);
            if (s) s.remove();
        }, 3000);

        // Update sidebar preview for this conversation
        updateSidebarPreview(currentConversationId, text);

    } catch (e) {
        // Show failed status
        const statusEl = document.getElementById(`${tempId}-status`);
        if (statusEl) {
            statusEl.textContent = '✗ Failed to send';
            statusEl.classList.add('failed');
        }

        showToast('Gagal mengirim pesan. Coba lagi.', 'danger');
    }

    // Re-enable input
    input.disabled = false;
    sendBtn.disabled = false;
    input.focus();
}

// ────────────────────────────────────────────
// Mark conversation as read
// ────────────────────────────────────────────
async function markAsRead(id) {
    if (!currentAccountId) return;
    try {
        await fetch(`/inbox/messages/${encodeURIComponent(id)}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ accountId: currentAccountId }),
        });
    } catch (e) {
        // Silent fail
    }
}

// ────────────────────────────────────────────
// Refresh current conversation
// ────────────────────────────────────────────
function refreshConversation() {
    if (currentConversationId) {
        loadMessages(currentConversationId);
    }
}

// ────────────────────────────────────────────
// Close panel (mobile)
// ────────────────────────────────────────────
function closePanel() {
    document.getElementById('inbox-sidebar').classList.remove('panel-hidden');
    document.getElementById('inbox-panel').classList.remove('panel-open');
    document.getElementById('panel-active').classList.add('d-none');
    document.getElementById('panel-empty').classList.remove('d-none');
    currentConversationId = null;
    currentLocalId = null;
}

// ────────────────────────────────────────────
// Toast notification
// ────────────────────────────────────────────
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top:80px;right:20px;z-index:9999;max-width:350px;font-size:13px;';
    toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────
function formatTime(date) {
    if (!date) return '';
    try {
        return new Date(date).toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch { return date; }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ────────────────────────────────────────────
// Keyboard shortcut
// ────────────────────────────────────────────
document.getElementById('reply-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendReply();
    }
});

// ────────────────────────────────────────────
// Auto-open first conversation on page load
// ────────────────────────────────────────────
window.addEventListener('load', () => {
    const first = document.querySelector('.inbox-conv-item');
    if (first) {
        openConversation(first, first.dataset.id);
    }

    // Initialize WebSocket
    initWebSocket();
});

// ════════════════════════════════════════════
//  WEBSOCKET (Laravel Echo + Reverb)
// ════════════════════════════════════════════

function initWebSocket() {
    const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
    if (!tenantId) {
        console.warn('[Inbox] No tenant-id meta tag — falling back to polling');
        startFallbackPolling();
        return;
    }
    if (!window.Echo) {
        console.warn('[Inbox] Laravel Echo not loaded — falling back to polling');
        startFallbackPolling();
        return;
    }

    console.log('[Inbox] Subscribing to private-tenant.' + tenantId + '.inbox');

    try {
        const channel = window.Echo.private(`tenant.${tenantId}.inbox`);

        channel
            .listen('.inbox.message.received', (e) => {
                console.log('[Inbox] WS event: inbox.message.received', e);
                handleIncomingMessage(e.message, e.conversation);
            })
            .listen('.inbox.conversation.read', (e) => {
                console.log('[Inbox] WS event: inbox.conversation.read', e);
                handleConversationRead(e);
            });

        // Monitor connection state
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('[Inbox] WebSocket CONNECTED');
            updateWsStatus('connected');
        });
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.warn('[Inbox] WebSocket DISCONNECTED');
            updateWsStatus('disconnected');
        });
        window.Echo.connector.pusher.connection.bind('error', (err) => {
            console.error('[Inbox] WebSocket CONNECTION ERROR:', err);
            updateWsStatus('error');
            startFallbackPolling();
        });

    } catch (err) {
        console.error('[Inbox] Failed to subscribe:', err);
        startFallbackPolling();
    }
}

function updateWsStatus(status) {
    const el = document.getElementById('ws-status');
    if (!el) return;
    const icons = { connected: '🟢', disconnected: '🔴', error: '⚠️' };
    el.textContent = icons[status] || '⚪';
    el.title = 'WebSocket: ' + status;
}

// ────────────────────────────────────────────
// Handle incoming message from WebSocket
// ────────────────────────────────────────────
function handleIncomingMessage(message, conversation) {
    const convId = conversation.zernio_conversation_id;

    playNotification();

    // ── CASE 1: User is viewing THIS conversation ──
    if (currentConversationId === convId) {
        // Append message directly to chat panel (no full reload)
        appendMessage(message);

        // Mark as read since user is viewing it
        markAsRead(convId);

        // Update sidebar preview (same conversation)
        updateSidebarPreview(convId, message.message_text);
    }
    // ── CASE 2: Message from a DIFFERENT conversation ──
    else {
        // Update sidebar: update preview + increment unread badge
        updateSidebarConversation(conversation);

        // Show toast notification
        const senderName = conversation.participant_name || 'Someone';
        const preview = (message.message_text || '').substring(0, 30);
        showToast(`<strong>${escapeHtml(senderName)}</strong>: ${escapeHtml(preview)}`, 'info');
    }

    // Update header badges
    updateHeaderBadges();
}

// ────────────────────────────────────────────
// Handle conversation read event from WebSocket
// ────────────────────────────────────────────
function handleConversationRead(e) {
    const convId = e.zernio_conversation_id;

    // Update sidebar unread count for this conversation
    const el = document.querySelector(`.inbox-conv-item[data-id="${convId}"]`);
    if (el) {
        el.classList.remove('unread');
        el.dataset.unread = '0';
        const badge = el.querySelector('.unread-badge-count');
        if (badge) badge.remove();
    }

    updateHeaderBadges();
}

// ────────────────────────────────────────────
// Update sidebar preview for a specific conversation
// ────────────────────────────────────────────
function updateSidebarPreview(convId, newMessageText) {
    const el = document.querySelector(`.inbox-conv-item[data-id="${convId}"]`);
    if (!el) return;

    // Update preview text
    const previewEl = el.querySelector('.preview-text');
    if (previewEl) {
        previewEl.textContent = (newMessageText || '').substring(0, 38);
    }

    // Update time
    const timeEl = el.querySelector('.inbox-conv-time');
    if (timeEl) {
        timeEl.textContent = 'Just now';
    }

    // Move conversation to top of list (if not already first)
    const list = document.getElementById('conv-list');
    if (list && list.firstElementChild !== el) {
        list.prepend(el);
        el.classList.add('new-flash');
        setTimeout(() => el.classList.remove('new-flash'), 600);
    }
}

// ────────────────────────────────────────────
// Update sidebar conversation (from a DIFFERENT conversation)
// ────────────────────────────────────────────
function updateSidebarConversation(conversation) {
    const convId = conversation.zernio_conversation_id;
    let el = document.querySelector(`.inbox-conv-item[data-id="${convId}"]`);

    if (el) {
        // ── Update existing conversation item ──
        // Update preview
        const previewEl = el.querySelector('.preview-text');
        if (previewEl) {
            previewEl.textContent = (conversation.last_message || '').substring(0, 38);
        }

        // Update time
        const timeEl = el.querySelector('.inbox-conv-time');
        if (timeEl) {
            timeEl.textContent = 'Just now';
        }

        // Increment unread badge
        const currentUnread = parseInt(el.dataset.unread || '0');
        const newUnread = currentUnread + 1;
        el.dataset.unread = newUnread;
        el.classList.add('unread');

        // Update or add unread count badge
        let badge = el.querySelector('.unread-badge-count');
        if (badge) {
            badge.textContent = newUnread;
        } else {
            const previewDiv = el.querySelector('.inbox-conv-preview');
            if (previewDiv) {
                previewDiv.insertAdjacentHTML('beforeend',
                    `<span class="unread-badge-count" style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:99px;margin-left:auto;flex-shrink:0;">${newUnread}</span>`
                );
            }
        }

        // Move to top of list
        const list = document.getElementById('conv-list');
        if (list && list.firstElementChild !== el) {
            list.prepend(el);
            el.classList.add('new-flash');
            setTimeout(() => el.classList.remove('new-flash'), 600);
        }
    } else {
        // ── New conversation (not in sidebar yet) ──
        // Prepend a new conversation item at top of list
        const list = document.getElementById('conv-list');
        if (!list) return;

        const sender = (conversation.participant_name || 'User').trim();
        const text = (conversation.last_message || '[Attachment]').trim();
        const platform = conversation.platform || 'instagram';
        const accountId = conversation.account_id || conversation.zernio_account_id || '';
        const avatar = conversation.participant_picture || '';
        const initial = sender.charAt(0).toUpperCase();
        const unreadCount = conversation.unread_count || 1;

        // Platform icon
        let platformIcon = 'brand-instagram';
        if (platform === 'facebook') platformIcon = 'brand-facebook';
        else if (platform === 'tiktok') platformIcon = 'brand-tiktok';
        else if (platform === 'twitter' || platform === 'x') platformIcon = 'brand-x';

        const html = `
            <div class="inbox-conv-item unread new-flash"
                data-id="${convId}"
                data-local-id="${conversation.id || ''}"
                data-account-id="${accountId}"
                data-name="${escapeHtml(sender)}"
                data-platform="${platform}"
                data-via="@${escapeHtml(conversation.account_username || '')}"
                data-avatar="${avatar}"
                data-initial="${initial}"
                data-unread="${unreadCount}"
                onclick="openConversation(this, '${convId}')">

                <div class="inbox-avatar ${platform}">
                    ${avatar ? `<img src="${avatar}" alt="">` : initial}
                </div>
                <div class="inbox-conv-body">
                    <div class="inbox-conv-top">
                        <span class="inbox-conv-name">${escapeHtml(sender)}</span>
                        <span class="inbox-conv-time">Just now</span>
                    </div>
                    <div class="inbox-conv-via">via @${escapeHtml(conversation.account_username || '')}</div>
                    <div class="inbox-conv-preview">
                        <span class="platform-dot ${platform}"><i class="ti ti-${platformIcon}"></i></span>
                        <span class="preview-text">${escapeHtml(text.substring(0, 38))}</span>
                        <span class="unread-badge-count" style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:99px;margin-left:auto;flex-shrink:0;">${unreadCount}</span>
                    </div>
                </div>
            </div>`;

        // Remove empty list message if present
        const emptyMsg = list.querySelector('.inbox-empty-list');
        if (emptyMsg) emptyMsg.remove();

        list.insertAdjacentHTML('afterbegin', html);
        setTimeout(() => {
            const newEl = list.querySelector('.new-flash');
            if (newEl) newEl.classList.remove('new-flash');
        }, 600);
    }
}

// ────────────────────────────────────────────
// Update header badges
// ────────────────────────────────────────────
function updateHeaderBadges() {
    // Count unread conversations from sidebar
    let unreadCount = 0;
    let totalCount = 0;
    document.querySelectorAll('.inbox-conv-item').forEach(el => {
        totalCount++;
        const u = parseInt(el.dataset.unread || '0');
        if (u > 0) unreadCount++;
    });

    // Update unread badge
    const unreadBadge = document.getElementById('unread-badge');
    if (unreadBadge) {
        if (unreadCount > 0) {
            unreadBadge.textContent = unreadCount + ' unread';
            unreadBadge.style.display = '';
        } else {
            unreadBadge.style.display = 'none';
        }
    }

    // Update total badge
    const totalBadge = document.getElementById('total-badge');
    if (totalBadge) totalBadge.textContent = totalCount;

    // Update page title
    if (unreadCount > 0) {
        document.title = `(${unreadCount}) Messages - Inbox`;
    } else {
        document.title = 'Messages - Inbox';
    }
}

// ────────────────────────────────────────────
// Play notification sound
// ────────────────────────────────────────────
function playNotification() {
    try {
        notifySound.play().catch(() => {});
    } catch (e) {}
}

// ────────────────────────────────────────────
// Fallback polling (only if WebSocket fails)
// ────────────────────────────────────────────
let fallbackPollTimer = null;

function startFallbackPolling() {
    // Poll every 15 seconds as fallback
    fallbackPollTimer = setInterval(pollConversations, 15000);
}

async function pollConversations() {
    try {
        const res = await fetch('{{ route("inbox.conversations.json") }}');
        const json = await res.json();
        if (!json.success) return;

        const convs = json.conversations ?? [];
        const stats = json.stats ?? {};

        // Update unread badge
        const unreadBadge = document.getElementById('unread-badge');
        if (unreadBadge) {
            if (stats.unread_messages > 0) {
                unreadBadge.textContent = stats.unread_messages + ' unread';
                unreadBadge.style.display = '';
            } else {
                unreadBadge.style.display = 'none';
            }
        }

        const totalBadge = document.getElementById('total-badge');
        if (totalBadge) totalBadge.textContent = stats.total_messages;

        if (stats.unread_messages > 0) {
            document.title = `(${stats.unread_messages}) Messages - Inbox`;
        } else {
            document.title = 'Messages - Inbox';
        }

        // Rebuild sidebar
        rebuildConversationList(convs);

    } catch (e) {
        // Silent fail
    }
}

function rebuildConversationList(convs) {
    const list = document.getElementById('conv-list');
    if (!list) return;

    const activeId = currentConversationId;

    const html = convs.map(conv => {
        const sender = (conv.participantName || 'User').trim();
        const text = (conv.lastMessage || '[Attachment]').trim();
        const platform = conv.platform || 'instagram';
        const accountId = conv.accountId || '';
        const avatar = conv.participantPicture || '';
        const initial = sender.charAt(0).toUpperCase();
        const unreadCount = conv.unreadCount || 0;
        const isUnread = unreadCount > 0;
        const isActive = conv.id === activeId;
        const time = conv.updatedTime || '';

        let timeStr = '';
        try { timeStr = new Date(time).toLocaleString('id-ID', {hour:'2-digit', minute:'2-digit'}); }
        catch(e) { timeStr = ''; }

        let platformIcon = 'brand-instagram';
        if (platform === 'facebook') platformIcon = 'brand-facebook';
        else if (platform === 'tiktok') platformIcon = 'brand-tiktok';
        else if (platform === 'twitter' || platform === 'x') platformIcon = 'brand-x';

        return `
            <div class="inbox-conv-item ${isActive ? 'active' : ''} ${isUnread ? 'unread' : ''}"
                data-id="${conv.id}"
                data-local-id="${conv.local_id || ''}"
                data-account-id="${accountId}"
                data-name="${escapeHtml(sender)}"
                data-platform="${platform}"
                data-via="@${escapeHtml(conv.accountUsername || '')}"
                data-avatar="${avatar}"
                data-initial="${initial}"
                data-unread="${unreadCount}"
                onclick="openConversation(this, '${conv.id}')">

                <div class="inbox-avatar ${platform}">
                    ${avatar ? `<img src="${avatar}" alt="">` : initial}
                </div>
                <div class="inbox-conv-body">
                    <div class="inbox-conv-top">
                        <span class="inbox-conv-name">${escapeHtml(sender)}</span>
                        <span class="inbox-conv-time">${timeStr}</span>
                    </div>
                    <div class="inbox-conv-via">via @${escapeHtml(conv.accountUsername || '')}</div>
                    <div class="inbox-conv-preview">
                        <span class="platform-dot ${platform}"><i class="ti ti-${platformIcon}"></i></span>
                        <span class="preview-text">${escapeHtml(text.substring(0, 38))}</span>
                        ${isUnread ? `<span class="unread-badge-count" style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:99px;margin-left:auto;flex-shrink:0;">${unreadCount}</span>` : ''}
                    </div>
                </div>
            </div>`;
    }).join('');

    if (convs.length === 0) {
        list.innerHTML = `
            <div class="inbox-empty-list">
                <i class="ti ti-inbox-off"></i>
                <span>No conversations found</span>
            </div>`;
    } else {
        list.innerHTML = html;
    }
}
</script>
@endpush
