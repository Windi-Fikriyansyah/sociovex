<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SocialPilot AI - Kelola Semua Media Sosial dalam Satu Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/css/style.css') }}">
    <style>
        body { font-family: 'Public Sans', sans-serif; }
        .hero { background: linear-gradient(135deg, #4680ff 0%, #7c3aed 100%); min-height: 100vh; display: flex; align-items: center; }
        .feature-icon { width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .pricing-card { border: 2px solid transparent; transition: all 0.3s; }
        .pricing-card:hover, .pricing-card.featured { border-color: #4680ff; transform: translateY(-4px); }
        .platform-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 30px; font-size: 14px; font-weight: 600; margin: 4px; }
        .nav-brand { font-size: 20px; font-weight: 700; color: #4680ff; text-decoration: none; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid #e9ecef; padding: 16px 0; position: sticky; top: 0; z-index: 100;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="/" class="nav-brand">
                    <i class="ti ti-brand-twitter me-2"></i>Social<span style="color:#333;">Pilot AI</span>
                </a>
                <div>
                    @if(Route::has('login'))
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary me-2">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero text-white">
        <div class="container text-center py-5">
            <span class="badge mb-3" style="background:rgba(255,255,255,0.2);font-size:14px;padding:8px 16px;">
                🚀 Platform Social Media Management #1 untuk UMKM Indonesia
            </span>
            <h1 style="font-size:52px;font-weight:800;line-height:1.2;" class="mb-4">
                Kelola Semua Media Sosial<br>dalam <span style="color:#fbbf24;">Satu Dashboard</span>
            </h1>
            <p class="mb-5 opacity-80" style="font-size:18px;max-width:600px;margin:0 auto 2rem;">
                Posting ke Instagram, Facebook, TikTok, LinkedIn, dan lebih banyak lagi sekaligus.
                AI membalas komentar otomatis 24/7.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-warning btn-lg px-5 fw-bold">
                    <i class="ti ti-rocket me-2"></i>Mulai Gratis 14 Hari
                </a>
                <a href="#pricing" class="btn btn-outline-light btn-lg px-5">
                    Lihat Harga
                </a>
            </div>
            <div class="mt-4 opacity-75" style="font-size:13px;">
                Tidak perlu kartu kredit • Setup dalam 2 menit • Cancel kapan saja
            </div>

            <!-- Supported Platforms -->
            <div class="mt-5">
                <p class="opacity-75 mb-3">Terhubung dengan platform:</p>
                <div>
                    @foreach([['ti-brand-instagram', '#E1306C', 'Instagram'], ['ti-brand-facebook', '#1877F2', 'Facebook'], ['ti-brand-linkedin', '#0A66C2', 'LinkedIn'], ['ti-brand-tiktok', '#000', 'TikTok'], ['ti-brand-x', '#1DA1F2', 'X (Twitter)'], ['ti-brand-threads', '#000', 'Threads'], ['ti-brand-youtube', '#FF0000', 'YouTube']] as [$icon, $color, $name])
                    <span class="platform-pill" style="background:{{ $color }}22;color:{{ $color == '#000' ? '#fff' : $color }};border:1px solid {{ $color }}44;">
                        <i class="ti {{ $icon }}" style="color:{{ $color == '#000' ? '#ccc' : $color }};"></i>
                        <span style="color:white;">{{ $name }}</span>
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section style="padding: 80px 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="font-size:36px;font-weight:700;">Semua yang Anda Butuhkan</h2>
                <p class="text-muted">Satu platform untuk mengelola semua sosial media bisnis Anda</p>
            </div>
            <div class="row g-4">
                @foreach([
                    ['ti ti-pencil-plus', '#4680ff', '#e8f0fe', 'Multi-Platform Posting', 'Post ke semua platform sekaligus. Hemat waktu hingga 80%.'],
                    ['ti ti-calendar', '#2ecc71', '#e8f5e9', 'Content Calendar', 'Jadwalkan posting bulanan dengan drag & drop yang mudah.'],
                    ['ti ti-robot', '#7c3aed', '#ede9fe', 'AI Auto Reply', 'AI membalas komentar pelanggan secara otomatis 24/7 berdasarkan knowledge base bisnis Anda.'],
                    ['ti ti-inbox', '#f39c12', '#fff3e0', 'Inbox Terpusat', 'Kelola semua komentar, DM, dan mention dari satu tempat.'],
                    ['ti ti-chart-bar', '#e91e63', '#fce4ec', 'Analytics Mendalam', 'Pantau reach, impressions, engagement, dan pertumbuhan followers.'],
                    ['ti ti-users', '#00bcd4', '#e0f7fa', 'Multi Tenant & User', 'Kelola banyak klien dengan workspace terpisah. Cocok untuk agency.'],
                ] as [$icon, $color, $bg, $title, $desc])
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon" style="background:{{ $bg }};color:{{ $color }};">
                                <i class="{{ $icon }}" style="font-size:28px;"></i>
                            </div>
                            <h5 class="fw-bold">{{ $title }}</h5>
                            <p class="text-muted mb-0">{{ $desc }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Target Users -->
    <section style="padding: 80px 0;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="font-size:36px;font-weight:700;">Cocok untuk Semua Bisnis</h2>
            </div>
            <div class="row justify-content-center g-3">
                @foreach(['🏥 Klinik', '💇 Salon', '✂️ Barbershop', '🍜 Restoran', '🔧 Bengkel', '🛍️ Toko Online', '📱 Agency Digital', '📊 Freelancer'] as $biz)
                <div class="col-auto">
                    <span class="badge py-2 px-4" style="background:#f8f9fa;color:#333;border:1px solid #dee2e6;font-size:15px;font-weight:500;">{{ $biz }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" style="padding: 80px 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="font-size:36px;font-weight:700;">Harga Transparan</h2>
                <p class="text-muted">Pilih paket yang sesuai dengan kebutuhan bisnis Anda</p>
            </div>
            <div class="row justify-content-center g-4">
                @foreach([
                    ['Basic', '199.000', '1 akun sosial media', ['Scheduler & Calendar', 'Post sekarang atau jadwalkan', 'Support email'], false],
                    ['Pro', '399.000', '5 akun sosial media', ['Semua fitur Basic', 'AI Auto Reply', 'Inbox Terpusat', '500 AI replies/bulan'], true],
                    ['Agency', '999.000', '10 akun sosial media', ['Semua fitur Pro', 'Analytics Lengkap', 'Multi User (10 anggota)', '2000 AI replies/bulan'], false],
                ] as [$name, $price, $accounts, $features, $popular])
                <div class="col-lg-4 col-md-6">
                    <div class="card pricing-card {{ $popular ? 'featured' : '' }} h-100">
                        @if($popular)
                        <div class="card-header text-center py-2" style="background: #4680ff; color: white; border-radius: 8px 8px 0 0;">
                            <strong>⭐ PALING POPULER</strong>
                        </div>
                        @endif
                        <div class="card-body p-4">
                            <h4 class="fw-bold">{{ $name }}</h4>
                            <div class="my-3">
                                <span style="font-size:40px;font-weight:800;color:#4680ff;">Rp{{ $price }}</span>
                                <span class="text-muted">/bulan</span>
                            </div>
                            <p class="text-muted mb-3">{{ $accounts }}</p>
                            <ul class="list-unstyled">
                                @foreach($features as $feature)
                                <li class="mb-2"><i class="ti ti-check text-success me-2"></i>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="btn btn-{{ $popular ? 'primary' : 'outline-primary' }} w-100 mt-3">
                                Mulai Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section style="padding: 80px 0; background: linear-gradient(135deg, #4680ff, #7c3aed); color: white;">
        <div class="container text-center">
            <h2 style="font-size:40px;font-weight:800;" class="mb-3">Mulai 14 Hari Trial Gratis</h2>
            <p class="mb-5 opacity-80" style="font-size:18px;">Bergabung dengan ribuan bisnis yang sudah menggunakan SocialPilot AI</p>
            <a href="{{ route('register') }}" class="btn btn-warning btn-lg px-5 fw-bold" style="font-size:18px;">
                <i class="ti ti-rocket me-2"></i>Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #1e293b; color: #94a3b8; padding: 40px 0;">
        <div class="container text-center">
            <div class="mb-3">
                <span style="font-size:20px;font-weight:700;color:#4680ff;">SocialPilot AI</span>
            </div>
            <p class="mb-0">© {{ date('Y') }} SocialPilot AI. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/bootstrap.min.js') }}"></script>
</body>
</html>
