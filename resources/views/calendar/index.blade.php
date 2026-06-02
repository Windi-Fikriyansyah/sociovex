@extends('layouts.mantis')

@section('title', 'Content Calendar')
@section('page_title', 'Content Calendar')

@section('breadcrumb')
    <li class="breadcrumb-item active">Content Calendar</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<style>
    .fc-event { cursor: pointer; }
    .fc .fc-button-primary { background-color: #4680ff; border-color: #4680ff; }
    .fc .fc-button-primary:hover { background-color: #3a6ee0; border-color: #3a6ee0; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #2c5bd6; }
    .fc-event-title { font-size: 12px; font-weight: 600; }
    .fc-daygrid-event { border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="row mb-3">
    <div class="col">
        <a href="{{ route('posts.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Jadwalkan Post Baru
        </a>
    </div>
    <div class="col-auto">
        <div class="d-flex gap-2 align-items-center">
            <span class="badge" style="background:#4680ff;padding:6px 12px;">Pending</span>
            <span class="badge" style="background:#28a745;padding:6px 12px;">Terpublikasi</span>
            <span class="badge" style="background:#dc3545;padding:6px 12px;">Gagal</span>
        </div>
    </div>
</div>

<div class="row">
    <!-- Calendar -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Scheduled List -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-list me-2"></i>Jadwal Mendatang</h6>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                @php
                    $upcoming = $scheduledPosts->where('status', 'pending')->where('scheduled_at', '>=', now())->sortBy('scheduled_at');
                @endphp
                @forelse($upcoming as $post)
                <div class="px-3 py-3 border-bottom">
                    <div class="fw-semibold" style="font-size:12px;">{{ $post->scheduled_at->format('d M, H:i') }}</div>
                    <p class="text-muted mb-1 mt-1" style="font-size:12px;">{{ Str::limit($post->caption, 60) }}</p>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach((array)$post->platforms as $platform)
                            <span class="platform-badge platform-{{ $platform }}" style="font-size:10px;">{{ ucfirst($platform) }}</span>
                        @endforeach
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('calendar.edit', $post) }}" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px;">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                        <form action="{{ route('calendar.destroy', $post) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size:11px;padding:2px 8px;"
                                onclick="return confirm('Hapus jadwal ini?')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="ti ti-calendar" style="font-size:36px;color:#dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0" style="font-size:13px;">Tidak ada jadwal mendatang</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-calendar-event me-2"></i>Detail Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Caption:</strong></p>
                <p id="modal-caption" class="text-muted"></p>
                <p><strong>Platform:</strong> <span id="modal-platforms"></span></p>
                <p><strong>Status:</strong> <span id="modal-status"></span></p>
                <p><strong>Jadwal:</strong> <span id="modal-time"></span></p>
            </div>
            <div class="modal-footer">
                <a href="#" id="modal-edit-btn" class="btn btn-primary btn-sm">
                    <i class="ti ti-edit me-1"></i>Edit Jadwal
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const events = @json($events);
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        events: events,
        editable: true,
        eventClick: function (info) {
            const props = info.event.extendedProps;
            document.getElementById('modal-caption').textContent = props.caption || '-';
            document.getElementById('modal-platforms').textContent = props.platforms || '-';
            document.getElementById('modal-status').innerHTML = `<span class="badge bg-${props.status === 'published' ? 'success' : props.status === 'failed' ? 'danger' : 'primary'}">${props.status}</span>`;
            document.getElementById('modal-time').textContent = info.event.start.toLocaleString('id-ID');
            document.getElementById('modal-edit-btn').href = `/calendar/${info.event.id}/edit`;
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },
        eventDrop: function (info) {
            // Drag & drop reschedule
            fetch(`/calendar/${info.event.id}/update`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ scheduled_at: info.event.start.toISOString() }),
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    info.revert();
                    alert('Gagal memperbarui jadwal.');
                }
            })
            .catch(() => { info.revert(); });
        },
    });

    calendar.render();
});
</script>
@endpush
