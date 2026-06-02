@extends('layouts.mantis')

@section('title', 'Analytics')
@section('page_title', 'Analytics')

@section('breadcrumb')
    <li class="breadcrumb-item active">Analytics</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.css">
@endpush

@section('content')
<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form class="d-flex flex-wrap gap-3 align-items-center" method="GET">
            <div>
                <label class="form-label mb-0 me-2 fw-semibold">Periode:</label>
                <select name="period" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                    @foreach(['7' => '7 Hari', '14' => '14 Hari', '30' => '30 Hari', '90' => '90 Hari'] as $val => $label)
                        <option value="{{ $val }}" {{ $period == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-0 me-2 fw-semibold">Akun:</label>
                <select name="account_id" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                    <option value="">Semua Akun</option>
                    @foreach($socialAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                            {{ $acc->profile_name ?? $acc->username }} ({{ ucfirst($acc->platform) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    @foreach([
        ['reach', 'Reach', 'ti ti-eye', '#4680ff', '#e8f0fe'],
        ['impressions', 'Impressions', 'ti ti-chart-bar', '#2ecc71', '#e8f5e9'],
        ['likes', 'Likes', 'ti ti-heart', '#e91e63', '#fce4ec'],
        ['comments', 'Komentar', 'ti ti-message', '#f39c12', '#fff3e0'],
        ['shares', 'Shares', 'ti ti-share', '#9c27b0', '#f3e5f5'],
        ['engagement_rate', 'Engagement Rate', 'ti ti-trending-up', '#00bcd4', '#e0f7fa'],
    ] as [$key, $label, $icon, $color, $bg])
    <div class="col-md-2 col-sm-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div style="width:48px;height:48px;border-radius:12px;background:{{ $bg }};color:{{ $color }};display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                    <i class="{{ $icon }}" style="font-size:22px;"></i>
                </div>
                <h4 class="mb-0">
                    {{ $key === 'engagement_rate' ? $totals[$key] . '%' : number_format($totals[$key]) }}
                </h4>
                <small class="text-muted">{{ $label }}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($analytics->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-chart-bar" style="font-size:64px;color:#dee2e6;"></i>
        <h5 class="mt-3 text-muted">Belum ada data analytics</h5>
        <p class="text-muted">Data analytics akan muncul setelah Anda terhubung ke akun sosial media dan mulai posting.</p>
    </div>
</div>
@else
<!-- Charts -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-trending-up me-2 text-primary"></i>Impressions & Reach</h5>
            </div>
            <div class="card-body">
                <div id="chart-impressions"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-chart-donut me-2 text-success"></i>Engagement</h5>
            </div>
            <div class="card-body">
                <div id="chart-engagement"></div>
            </div>
        </div>
    </div>
</div>

<!-- Engagement Chart -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="ti ti-heart me-2 text-danger"></i>Likes & Komentar per Hari</h5>
    </div>
    <div class="card-body">
        <div id="chart-likes"></div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
const chartData = @json($chartData);

if (chartData.labels.length > 0) {
    // Impressions & Reach Chart
    new ApexCharts(document.getElementById('chart-impressions'), {
        chart: { type: 'area', height: 280, toolbar: { show: false } },
        series: [
            { name: 'Impressions', data: chartData.impressions },
            { name: 'Reach', data: chartData.reach },
        ],
        xaxis: { categories: chartData.labels },
        colors: ['#4680ff', '#2ecc71'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        legend: { position: 'top' },
        tooltip: { x: { format: 'dd MMM' } },
    }).render();

    // Engagement Donut Chart
    const totalLikes = chartData.likes.reduce((a, b) => a + b, 0);
    const totalComments = chartData.comments.reduce((a, b) => a + b, 0);
    new ApexCharts(document.getElementById('chart-engagement'), {
        chart: { type: 'donut', height: 280 },
        series: [totalLikes, totalComments],
        labels: ['Likes', 'Komentar'],
        colors: ['#e91e63', '#f39c12'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true },
    }).render();

    // Likes & Comments Chart
    new ApexCharts(document.getElementById('chart-likes'), {
        chart: { type: 'bar', height: 250, toolbar: { show: false } },
        series: [
            { name: 'Likes', data: chartData.likes },
            { name: 'Komentar', data: chartData.comments },
        ],
        xaxis: { categories: chartData.labels },
        colors: ['#e91e63', '#f39c12'],
        dataLabels: { enabled: false },
        legend: { position: 'top' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    }).render();
}
</script>
@endpush
