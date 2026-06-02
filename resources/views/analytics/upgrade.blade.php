@extends('layouts.mantis')
@section('title', 'Upgrade Diperlukan')
@section('page_title', 'Analytics')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card text-center">
            <div class="card-body py-5">
                <i class="ti ti-chart-bar" style="font-size:64px;color:#dee2e6;"></i>
                <h4 class="mt-3">Fitur Analytics</h4>
                <p class="text-muted">Fitur Analytics lengkap tersedia untuk paket <strong>Agency</strong>. Pantau performa konten Anda secara mendalam.</p>
                <a href="{{ route('subscription.index') }}" class="btn btn-primary mt-2">
                    <i class="ti ti-arrow-up me-1"></i>Upgrade ke Agency
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
