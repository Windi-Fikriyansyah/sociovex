@extends('layouts.mantis')

@section('title', 'Buat Post')
@section('page_title', 'Buat Post Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Post</a></li>
    <li class="breadcrumb-item active">Buat Post</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-pencil-plus me-2 text-primary"></i>Buat Konten Baru</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="post-form">
                    @csrf

                    <!-- Caption -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Caption <span class="text-danger">*</span></label>
                        <textarea name="caption" class="form-control @error('caption') is-invalid @enderror"
                            rows="6" placeholder="Tulis caption yang menarik..."
                            id="caption-input" maxlength="2200" required>{{ old('caption') }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Gunakan teks yang menarik untuk meningkatkan engagement</small>
                            <small class="text-muted"><span id="char-count">0</span>/2200</small>
                        </div>
                        @error('caption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <!-- Hashtags -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Hashtag</label>
                        <input type="text" name="hashtags" class="form-control"
                            placeholder="#bisnis #sosmed #marketing"
                            value="{{ old('hashtags') }}">
                        <small class="text-muted">Pisahkan dengan spasi. Contoh: #bisnis #klinik #pontianak</small>
                    </div>

                    <!-- Media Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Media (Gambar/Video)</label>
                        <div class="border rounded p-4 text-center" id="drop-zone"
                            style="border-style: dashed !important; cursor:pointer; background:#f8f9fa;">
                            <input type="file" name="media" id="media-input" class="d-none"
                                accept="image/*,video/*">
                            <i class="ti ti-photo-up" style="font-size:40px;color:#adb5bd;"></i>
                            <p class="text-muted mb-2 mt-2">Klik atau drag & drop gambar/video</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="document.getElementById('media-input').click()">
                                Pilih File
                            </button>
                        </div>
                        <div id="media-preview" class="mt-3 d-none">
                            <img id="preview-img" src="" alt="preview" style="max-height:200px;border-radius:8px;">
                        </div>
                    </div>

                    <!-- Target Accounts -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Target Akun <span class="text-danger">*</span></label>
                        @if($socialAccounts->isEmpty())
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                Belum ada akun sosial media terhubung.
                                <a href="{{ route('social-accounts.index') }}">Hubungkan akun dulu</a>.
                            </div>
                        @else
                            <div class="row g-2">
                                @foreach($socialAccounts as $account)
                                <div class="col-md-6">
                                    <label class="d-flex align-items-center p-3 border rounded account-select-card"
                                        style="cursor:pointer; transition:all 0.2s;"
                                        for="account-{{ $account->id }}">
                                        <input type="checkbox" name="social_accounts[]"
                                            value="{{ $account->id }}"
                                            id="account-{{ $account->id }}"
                                            class="form-check-input me-3 account-checkbox"
                                            {{ count(old('social_accounts', [])) > 0 && in_array($account->id, old('social_accounts', [])) ? 'checked' : '' }}>
                                        <div class="flex-shrink-0 me-2">
                                            <div style="width:36px;height:36px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                                                <i class="{{ $account->platform_icon }}" style="font-size:18px;color:{{ $account->platform_color }};"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:13px;">{{ $account->profile_name ?? $account->username }}</div>
                                            <span class="platform-badge platform-{{ $account->platform }}" style="font-size:10px;">
                                                {{ ucfirst($account->platform) }}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @error('social_accounts')<div class="text-danger mt-1" style="font-size:13px;">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <!-- Publish Type -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Waktu Publikasi <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="d-flex align-items-center p-3 border rounded"
                                    style="cursor:pointer;" for="publish-now">
                                    <input type="radio" name="publish_type" value="now" id="publish-now"
                                        class="form-check-input me-3" {{ old('publish_type', 'now') === 'now' ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-semibold"><i class="ti ti-send me-2 text-success"></i>Publish Sekarang</div>
                                        <small class="text-muted">Post langsung diterbitkan</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="d-flex align-items-center p-3 border rounded"
                                    style="cursor:pointer;" for="publish-schedule">
                                    <input type="radio" name="publish_type" value="schedule" id="publish-schedule"
                                        class="form-check-input me-3" {{ old('publish_type') === 'schedule' ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-semibold"><i class="ti ti-calendar-time me-2 text-primary"></i>Jadwalkan</div>
                                        <small class="text-muted">Tentukan waktu publikasi</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Date -->
                    <div class="mb-4" id="schedule-section" style="display:none;">
                        <label class="form-label fw-semibold">Jadwal Publikasi</label>
                        <input type="datetime-local" name="scheduled_at"
                            class="form-control @error('scheduled_at') is-invalid @enderror"
                            value="{{ old('scheduled_at') }}"
                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary px-4" id="submit-btn">
                            <i class="ti ti-send me-2"></i><span id="submit-text">Publish Sekarang</span>
                        </button>
                        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview & Tips -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-eye me-2"></i>Preview</h5>
            </div>
            <div class="card-body">
                <div class="border rounded p-3" style="background:#f8f9fa;min-height:200px;">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width:36px;height:36px;border-radius:50%;background:#dee2e6;"></div>
                        <div class="ms-2">
                            <div class="fw-semibold" style="font-size:13px;">{{ $tenant->business_name }}</div>
                            <small class="text-muted">Sekarang</small>
                        </div>
                    </div>
                    <p id="caption-preview" class="text-muted" style="font-size:13px;white-space:pre-line;">Caption akan muncul di sini...</p>
                    <p id="hashtag-preview" class="text-primary" style="font-size:12px;"></p>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-bulb me-2 text-warning"></i>Tips Konten</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Caption optimal 125-150 karakter</li>
                    <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Gunakan 5-10 hashtag relevan</li>
                    <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Posting di jam prime time (09:00, 12:00, 18:00)</li>
                    <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Sertakan call-to-action</li>
                    <li><i class="ti ti-check text-success me-2"></i>Gunakan gambar berkualitas tinggi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Character counter
    const captionInput = document.getElementById('caption-input');
    const charCount = document.getElementById('char-count');
    const captionPreview = document.getElementById('caption-preview');

    captionInput.addEventListener('input', function () {
        charCount.textContent = this.value.length;
        captionPreview.textContent = this.value || 'Caption akan muncul di sini...';
    });

    // Hashtag preview
    document.querySelector('[name="hashtags"]').addEventListener('input', function () {
        document.getElementById('hashtag-preview').textContent = this.value;
    });

    // Publish type toggle
    document.querySelectorAll('[name="publish_type"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const scheduleSection = document.getElementById('schedule-section');
            const submitText = document.getElementById('submit-text');
            if (this.value === 'schedule') {
                scheduleSection.style.display = 'block';
                submitText.textContent = 'Jadwalkan Post';
            } else {
                scheduleSection.style.display = 'none';
                submitText.textContent = 'Publish Sekarang';
            }
        });
    });

    // Check if schedule is already selected
    if (document.querySelector('[name="publish_type"]:checked')?.value === 'schedule') {
        document.getElementById('schedule-section').style.display = 'block';
        document.getElementById('submit-text').textContent = 'Jadwalkan Post';
    }

    // Account card selection visual
    document.querySelectorAll('.account-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const card = this.closest('.account-select-card');
            if (this.checked) {
                card.style.borderColor = '#4680ff';
                card.style.background = '#e8f0fe';
            } else {
                card.style.borderColor = '';
                card.style.background = '';
            }
        });
    });

    // Media preview
    document.getElementById('media-input').addEventListener('change', function () {
        if (this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('preview-img').src = e.target.result;
                document.getElementById('media-preview').classList.remove('d-none');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Drop zone
    const dropZone = document.getElementById('drop-zone');
    dropZone.addEventListener('click', () => document.getElementById('media-input').click());
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#4680ff';
    });
    dropZone.addEventListener('dragleave', () => {
        dropZone.style.borderColor = '';
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (file) {
            document.getElementById('media-input').files = e.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = (ev) => {
                document.getElementById('preview-img').src = ev.target.result;
                document.getElementById('media-preview').classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
        dropZone.style.borderColor = '';
    });
</script>
@endpush
