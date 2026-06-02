@extends('layouts.mantis')

@section('title', 'AI Auto Reply')
@section('page_title', 'AI Auto Reply Settings')

@section('breadcrumb')
    <li class="breadcrumb-item active">AI Auto Reply</li>
@endsection

@section('content')
<div class="row">
    <!-- AI Settings -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-robot me-2 text-info"></i>Pengaturan AI</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ai.save-settings') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Model AI</label>
                        <select name="model" class="form-select">
                            @foreach(['gpt-4o-mini' => 'GPT-4o Mini (Cepat & Hemat)', 'gpt-4o' => 'GPT-4o (Canggih)', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Ekonomis)'] as $val => $label)
                                <option value="{{ $val }}" {{ $aiSetting->model === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kreativitas (Temperature): <span id="temp-val">{{ $aiSetting->temperature ?? 0.7 }}</span></label>
                        <input type="range" name="temperature" class="form-range"
                            min="0" max="2" step="0.1"
                            value="{{ $aiSetting->temperature ?? 0.7 }}"
                            id="temperature-range">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Lebih Formal</small>
                            <small class="text-muted">Lebih Kreatif</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">System Prompt</label>
                        <textarea name="system_prompt" class="form-control" rows="5"
                            placeholder="Contoh: Kamu adalah asisten ramah dari Klinik Sehat. Balas dengan sopan dan profesional. Selalu sapa dengan nama pelanggan jika ada.">{{ $aiSetting->system_prompt }}</textarea>
                        <small class="text-muted">Instruksi khusus untuk AI dalam membalas komentar</small>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_reply_enabled"
                                id="auto-reply-switch" role="switch"
                                {{ $aiSetting->auto_reply_enabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="auto-reply-switch">
                                Auto Reply Komentar
                            </label>
                        </div>
                        <small class="text-muted">Aktifkan untuk membalas komentar secara otomatis menggunakan AI</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-2"></i>Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Knowledge Base -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-database me-2 text-success"></i>Knowledge Base</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Tambahkan informasi bisnis Anda agar AI dapat membalas lebih akurat.</p>

                <!-- Add Knowledge Form -->
                <form action="{{ route('ai.store-knowledge') }}" method="POST" class="mb-4">
                    @csrf
                    <div class="mb-2">
                        <input type="text" name="title" class="form-control form-control-sm"
                            placeholder="Judul (contoh: Jam Operasional)"
                            value="{{ old('title') }}" required>
                    </div>
                    <div class="mb-2">
                        <textarea name="content" class="form-control form-control-sm" rows="3"
                            placeholder="Konten (contoh: Senin-Sabtu 08.00-20.00, Minggu 09.00-17.00)"
                            required>{{ old('content') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="ti ti-plus me-1"></i>Tambah Knowledge
                    </button>
                </form>

                <!-- Knowledge List -->
                @forelse($knowledgeBases as $kb)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $kb->title }}</div>
                            <p class="text-muted mb-0 mt-1" style="font-size:12px;">{{ Str::limit($kb->content, 100) }}</p>
                        </div>
                        <form action="{{ route('ai.destroy-knowledge', $kb) }}" method="POST" class="ms-2">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Hapus knowledge ini?')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-3">
                    <i class="ti ti-database" style="font-size:36px;color:#dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">Belum ada knowledge base</p>
                    <small class="text-muted">Tambahkan informasi bisnis Anda</small>
                </div>
                @endforelse

                <!-- Example Template -->
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-info w-100" id="load-template">
                        <i class="ti ti-template me-1"></i>Load Template Contoh
                    </button>
                </div>
            </div>
        </div>

        <!-- Workflow Info -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-info-circle me-2 text-primary"></i>Cara Kerja AI Reply</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <span class="badge bg-primary me-3" style="min-width:28px;">1</span>
                    <div>Komentar baru masuk via Webhook Zernio</div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <span class="badge bg-primary me-3" style="min-width:28px;">2</span>
                    <div>AI membaca komentar + knowledge base Anda</div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <span class="badge bg-primary me-3" style="min-width:28px;">3</span>
                    <div>OpenAI menghasilkan balasan yang relevan</div>
                </div>
                <div class="d-flex align-items-start">
                    <span class="badge bg-success me-3" style="min-width:28px;">4</span>
                    <div>Balasan otomatis terkirim ke komentar</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('temperature-range').addEventListener('input', function () {
    document.getElementById('temp-val').textContent = this.value;
});

document.getElementById('load-template').addEventListener('click', function () {
    const templates = [
        { title: 'Nama Bisnis', content: '{{ $tenant->business_name }}' },
        { title: 'Jam Operasional', content: 'Senin - Sabtu: 08.00 - 20.00 WIB\nMinggu: 09.00 - 17.00 WIB' },
        { title: 'Alamat', content: 'Jl. Contoh No. 123, Pontianak, Kalimantan Barat' },
        { title: 'Layanan', content: 'Konsultasi dokter umum\nPemeriksaan kesehatan\nVaksinasi' },
    ];

    const form = document.querySelector('form[action="{{ route("ai.store-knowledge") }}"]');
    const titleInput = form.querySelector('[name="title"]');
    const contentInput = form.querySelector('[name="content"]');

    // Load first template as example
    titleInput.value = templates[0].title;
    contentInput.value = templates[0].content;
    titleInput.focus();
    alert('Template dimuat! Sesuaikan isi dan klik "Tambah Knowledge".');
});
</script>
@endpush
