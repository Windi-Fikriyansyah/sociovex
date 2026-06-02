@extends('layouts.mantis')
@section('title', 'Upgrade Diperlukan')
@section('page_title', 'Inbox')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card text-center">
            <div class="card-body py-5">
                <i class="ti ti-inbox" style="font-size:64px;color:#dee2e6;"></i>
                <h4 class="mt-3">Fitur Inbox</h4>
                <p class="text-muted">Fitur Inbox tersedia untuk paket <strong>Pro</strong> dan <strong>Agency</strong>. Upgrade sekarang untuk mengelola semua komentar dan DM dalam satu tempat.</p>
                <a href="{{ route('subscription.index') }}" class="btn btn-primary mt-2">
                    <i class="ti ti-arrow-up me-1"></i>Upgrade Sekarang
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
