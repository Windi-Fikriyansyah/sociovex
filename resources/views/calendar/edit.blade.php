@extends('layouts.mantis')

@section('title', 'Edit Jadwal')
@section('page_title', 'Edit Jadwal Post')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('calendar.index') }}">Content Calendar</a></li>
    <li class="breadcrumb-item active">Edit Jadwal</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-calendar-event me-2 text-primary"></i>Edit Jadwal Post</h5>
            </div>
            <div class="card-body">
                <div class="mb-3 p-3 bg-light rounded">
                    <p class="mb-1 fw-semibold">Caption:</p>
                    <p class="text-muted mb-0">{{ $scheduledPost->caption }}</p>
                    <div class="mt-2">
                        @foreach((array)$scheduledPost->platforms as $platform)
                            <span class="platform-badge platform-{{ $platform }} me-1">{{ ucfirst($platform) }}</span>
                        @endforeach
                    </div>
                </div>

                <form action="{{ route('calendar.reschedule', $scheduledPost) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Jadwal Baru <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_at"
                            class="form-control @error('scheduled_at') is-invalid @enderror"
                            value="{{ $scheduledPost->scheduled_at->format('Y-m-d\TH:i') }}"
                            required>
                        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>Simpan Jadwal
                        </button>
                        <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
