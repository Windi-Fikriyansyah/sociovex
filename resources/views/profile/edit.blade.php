@extends('layouts.mantis')

@section('title', 'Pengaturan Akun')
@section('page_title', 'Pengaturan Akun')

@section('breadcrumb')
    <li class="breadcrumb-item active">Pengaturan Akun</li>
@endsection

@section('content')
<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-user me-2 text-primary"></i>Informasi Profil</h5>
            </div>
            <div class="card-body">
                <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                    @csrf
                </form>

                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nama</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                            <div class="alert alert-warning mt-2 mb-0 py-2" style="font-size:13px;">
                                Email belum terverifikasi.
                                <button form="send-verification" class="btn btn-link p-0 ms-1" style="font-size:13px;vertical-align:baseline;">
                                    Kirim ulang link verifikasi
                                </button>
                                @if (session('status') === 'verification-link-sent')
                                    <span class="text-success ms-1">Link verifikasi telah dikirim.</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($tenant)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Bisnis</label>
                            <input type="text" class="form-control" value="{{ $tenant->business_name }}" disabled>
                            <small class="text-muted">Hubungi admin untuk mengubah nama bisnis</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Paket</label>
                            <div>
                                <span class="badge bg-primary" style="font-size:12px;">
                                    {{ $tenant->package?->name ?? 'Trial' }}
                                </span>
                                @if ($tenant->expired_at)
                                    <small class="text-muted ms-1">Aktif hingga {{ $tenant->expired_at->format('d M Y') }}</small>
                                @endif
                            </div>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Profil
                    </button>

                    @if (session('status') === 'profile-updated')
                        <span class="text-success ms-2" style="font-size:13px;">Profil berhasil disimpan.</span>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- Update Password -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-lock me-2 text-warning"></i>Ubah Password</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Password Lama</label>
                        <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            id="current_password" name="current_password" autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password Baru</label>
                        <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                            id="password" name="password" autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                            id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        @error('password_confirmation', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-device-floppy me-1"></i> Ubah Password
                    </button>

                    @if (session('status') === 'password-updated')
                        <span class="text-success ms-2" style="font-size:13px;">Password berhasil diubah.</span>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Zernio API Settings -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-plug me-2 text-info"></i>Pengaturan Zernio API</h5>
                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
                    <i class="ti ti-plus me-1"></i> Tambah API Key
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Kelola satu atau lebih API Key Zernio agar SocialPilot AI dapat terhubung dengan akun Zernio Anda.
                    Anda bisa mendapatkan API Key dari dashboard Zernio.
                </p>

                <!-- API Keys List -->
                @forelse ($zernioApiKeys as $index => $key)
                    <div class="border rounded p-3 mb-3 position-relative" style="background:#fafbfc;">
                        <div class="row align-items-start">
                            <div class="col-lg-12 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2" style="font-size:11px;">#{{ $index + 1 }}</span>
                                        <strong>{{ $key->label }}</strong>
                                        @if ($key->is_active)
                                            <span class="badge bg-success ms-2" style="font-size:10px;">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary ms-2" style="font-size:10px;">Nonaktif</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal" data-bs-target="#regenerateSecretModal{{ $key->id }}"
                                            title="Generate ulang secret">
                                            <i class="ti ti-refresh"></i>
                                        </button>
                                        <form action="{{ route('profile.zernio.destroy', $key) }}" method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus API Key ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:12px;">
                                    <i class="ti ti-key me-1"></i> API Key
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control font-monospace api-key-field"
                                        value="{{ $key->api_key }}" readonly style="font-size:12px;background:#f1f3f5;">
                                    <button type="button" class="btn btn-outline-secondary toggle-key-btn" title="Tampilkan/Sembunyikan">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary copy-key-btn"
                                        data-value="{{ $key->api_key }}" title="Salin">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:12px;">
                                    <i class="ti ti-shield-lock me-1"></i> Webhook Secret
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control font-monospace secret-field"
                                        value="{{ $key->webhook_secret ?? '(belum diset)' }}" readonly
                                        style="font-size:12px;background:#f1f3f5;">
                                    <button type="button" class="btn btn-outline-secondary toggle-secret-btn" title="Tampilkan/Sembunyikan">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    @if ($key->webhook_secret)
                                        <button type="button" class="btn btn-outline-primary copy-secret-btn"
                                            data-value="{{ $key->webhook_secret }}" title="Salin">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <small class="text-muted">
                                    Ditambahkan {{ $key->created_at->diffForHumans() }}
                                    @if ($key->updated_at->ne($key->created_at))
                                        &middot; Diperbarui {{ $key->updated_at->diffForHumans() }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Regenerate Secret Modal -->
                    <div class="modal fade" id="regenerateSecretModal{{ $key->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <form action="{{ route('profile.zernio.regenerate-secret', $key) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h6 class="modal-title">Generate Ulang Secret</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-0" style="font-size:13px;">
                                            Secret lama akan diganti dan tidak bisa digunakan lagi. Lanjutkan?
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-info text-white">Generate Ulang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ti ti-plug" style="font-size:40px;color:#dee2e6;"></i>
                        <p class="text-muted mt-2 mb-3">Belum ada API Key Zernio yang ditambahkan</p>
                        <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
                            <i class="ti ti-plus me-1"></i> Tambah API Key Pertama
                        </button>
                    </div>
                @endforelse

                <hr class="my-4">

                <!-- Webhook URL to copy -->
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h6 class="mb-2"><i class="ti ti-link me-1 text-primary"></i>Webhook URL</h6>
                        <p class="text-muted mb-2" style="font-size:13px;">
                            Salin URL berikut dan tempelkan di pengaturan Webhook dashboard Zernio Anda
                            agar notifikasi (komentar, pesan, status post) dapat diterima oleh SocialPilot AI.
                        </p>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="webhook-url"
                                value="{{ $webhookUrl }}" readonly
                                style="font-size:13px;background:#f8f9fa;">
                            <button type="button" class="btn btn-primary" id="copy-webhook-btn">
                                <i class="ti ti-copy me-1"></i> Salin
                            </button>
                        </div>
                        <div id="copy-feedback" class="text-success mt-1" style="font-size:12px;display:none;">
                            <i class="ti ti-circle-check me-1"></i>URL berhasil disalin!
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded p-3 mt-3 mt-lg-0" style="background:#f8f9fa;">
                            <h6 class="mb-2" style="font-size:13px;">
                                <i class="ti ti-info-circle me-1 text-info"></i>Cara Setup Webhook di Zernio
                            </h6>
                            <ol class="mb-0 ps-3" style="font-size:12px;line-height:1.8;">
                                <li>Buka dashboard Zernio</li>
                                <li>Masuk ke <strong>Settings &rarr; Webhooks</strong></li>
                                <li>Klik <strong>Add Webhook</strong></li>
                                <li>Tempel URL di atas</li>
                                <li>Pilih event: <code>new_message</code>, <code>new_comment</code>, <code>post_published</code>, <code>post_failed</code></li>
                                <li>Simpan</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add API Key Modal -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.zernio.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-2 text-info"></i>Tambah API Key Zernio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-label" class="form-label fw-semibold">Label / Nama</label>
                        <input type="text" class="form-control" id="add-label" name="label"
                            placeholder="Contoh: Akun Utama, Akun Testing, Klien A..." required>
                        <small class="text-muted">Beri nama agar mudah dibedakan jika ada beberapa API Key</small>
                    </div>

                    <div class="mb-3">
                        <label for="add-api-key" class="form-label fw-semibold">
                            <i class="ti ti-key me-1"></i> Zernio API Key
                        </label>
                        <input type="text" class="form-control" id="add-api-key" name="api_key"
                            placeholder="sk_xxxxxxxxxxxxxxxxxxxxxxxx" required>
                        <small class="text-muted">Dapatkan dari dashboard Zernio Anda</small>
                    </div>

                    <div class="mb-0">
                        <label for="add-webhook-secret" class="form-label fw-semibold">
                            <i class="ti ti-shield-lock me-1"></i> Webhook Secret
                            <span class="text-muted fw-normal">(opsional)</span>
                        </label>
                        <input type="text" class="form-control" id="add-webhook-secret" name="webhook_secret"
                            placeholder="Kosongkan jika tidak digunakan">
                        <small class="text-muted">Secret untuk memverifikasi webhook dari Zernio</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="ti ti-plus me-1"></i> Simpan API Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Danger Zone -->
<div class="row">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger bg-opacity-10">
                <h5 class="mb-0 text-danger"><i class="ti ti-alert-triangle me-2"></i>Zona Berbahaya</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Setelah akun dihapus, semua data dan resource akan dihapus secara permanen.
                    Pastikan Anda sudah mengunduh data yang ingin disimpan.
                </p>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="ti ti-trash me-1"></i> Hapus Akun
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')

                <div class="modal-header bg-danger bg-opacity-10">
                    <h5 class="modal-title text-danger">
                        <i class="ti ti-alert-triangle me-2"></i>Konfirmasi Hapus Akun
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus akun ini?</p>
                    <p class="text-muted" style="font-size:13px;">
                        Semua data akan dihapus secara permanen. Masukkan password Anda untuk mengonfirmasi.
                    </p>
                    <div class="mb-0">
                        <label for="delete-password" class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                            id="delete-password" name="password" placeholder="Masukkan password Anda">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i> Hapus Akun Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle visibility for all API key fields
    document.querySelectorAll('.toggle-key-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = this.closest('.input-group').querySelector('input');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="ti ti-eye-off"></i>' : '<i class="ti ti-eye"></i>';
        });
    });

    // Toggle visibility for all webhook secret fields
    document.querySelectorAll('.toggle-secret-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = this.closest('.input-group').querySelector('input');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="ti ti-eye-off"></i>' : '<i class="ti ti-eye"></i>';
        });
    });

    // Copy API key buttons
    document.querySelectorAll('.copy-key-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const value = this.dataset.value;
            navigator.clipboard.writeText(value).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<i class="ti ti-check"></i>';
                this.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    this.innerHTML = orig;
                    this.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            });
        });
    });

    // Copy webhook secret buttons
    document.querySelectorAll('.copy-secret-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const value = this.dataset.value;
            navigator.clipboard.writeText(value).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<i class="ti ti-check"></i>';
                this.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    this.innerHTML = orig;
                    this.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            });
        });
    });

    // Copy webhook URL
    const copyBtn = document.getElementById('copy-webhook-btn');
    const webhookInput = document.getElementById('webhook-url');
    const feedback = document.getElementById('copy-feedback');
    if (copyBtn && webhookInput) {
        copyBtn.addEventListener('click', function () {
            webhookInput.select();
            webhookInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(webhookInput.value).then(function () {
                feedback.style.display = 'block';
                copyBtn.innerHTML = '<i class="ti ti-check me-1"></i> Tersalin!';
                copyBtn.classList.replace('btn-primary', 'btn-success');
                setTimeout(function () {
                    feedback.style.display = 'none';
                    copyBtn.innerHTML = '<i class="ti ti-copy me-1"></i> Salin';
                    copyBtn.classList.replace('btn-success', 'btn-primary');
                }, 3000);
            });
        });
    }

    // Auto-open delete modal if there are validation errors
    @if ($errors->userDeletion->isNotEmpty())
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
        deleteModal.show();
    @endif
});
</script>
@endpush
