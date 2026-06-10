<!DOCTYPE html>
<html lang="en">

<head>
    <title>@yield('title', 'SocialPilot AI') - SocialPilot AI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('images/logo/logo-icon.svg') }}" type="image/x-icon">

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
        id="main-font-link">
    <link rel="stylesheet"
        href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/material.css') }}">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/css/style.css') }}"
        id="main-style-link">
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/css/style-preset.css') }}">

    <style>
        .platform-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .platform-instagram {
            background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
            color: #fff;
        }

        .platform-facebook {
            background: #1877F2;
            color: #fff;
        }

        .platform-linkedin {
            background: #0A66C2;
            color: #fff;
        }

        .platform-tiktok {
            background: #000;
            color: #fff;
        }

        .platform-threads {
            background: #000;
            color: #fff;
        }

        .platform-x,
        .platform-twitter {
            background: #1DA1F2;
            color: #fff;
        }

        .platform-youtube {
            background: #FF0000;
            color: #fff;
        }

        .sidebar-brand-text {
            font-size: 18px;
            font-weight: 700;
            color: #4680ff;
            letter-spacing: -0.5px;
        }

        .sidebar-brand-text span {
            color: #333;
        }
    </style>

    @stack('styles')
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('dashboard') }}" class="b-brand text-primary">
                    <i class="ti ti-brand-twitter" style="font-size:24px;color:#4680ff;"></i>
                    <span class="sidebar-brand-text ms-2">Social<span>Pilot</span> AI</span>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar">

                    <li class="pc-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-home-2" style="font-size: 18px;"></i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Konten</label>
                    </li>

                    <li class="pc-item {{ request()->routeIs('posts.*') ? 'active' : '' }}">
                        <a href="{{ route('posts.create') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-square-plus" style="font-size: 18px;"></i></span>
                            <span class="pc-mtext">Buat Post</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('posts.index') ? 'active' : '' }}">
                        <a href="{{ route('posts.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-news"></i></span>
                            <span class="pc-mtext">Semua Post</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                        <a href="{{ route('calendar.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar"></i></span>
                            <span class="pc-mtext">Content Calendar</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Interaksi</label>
                    </li>

                    <li class="pc-item pc-hasmenu {{ request()->routeIs('inbox.*') ? 'active' : '' }}">
                        <a href="#!" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-inbox" style="font-size: 18px;"></i></span>
                            <span class="pc-mtext">Inbox</span>
                            <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                            @php
                                $unreadCount = 0;
                                if (Auth::user()?->tenant_id) {
                                    $unreadCount =
                                        \App\Models\Comment::where('tenant_id', Auth::user()->tenant_id)
                                            ->where('is_replied', 0)
                                            ->count() +
                                        \App\Models\InboxMessage::where('tenant_id', Auth::user()->tenant_id)
                                            ->where('is_read', 0)
                                            ->count();
                                }
                            @endphp
                            @if ($unreadCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $unreadCount }}</span>
                            @endif
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item {{ request()->routeIs('inbox.messages') ? 'active' : '' }}">
                                <a class="pc-link" href="{{ route('inbox.messages') }}">
                                    <span class="pc-mtext">Messages</span>
                                    @php
                                        $unreadMessages = 0;
                                        if (Auth::user()?->tenant_id) {
                                            $unreadMessages = \App\Models\InboxMessage::where(
                                                'tenant_id',
                                                Auth::user()->tenant_id,
                                            )
                                                ->where('is_read', 0)
                                                ->count();
                                        }
                                    @endphp
                                    @if ($unreadMessages > 0)
                                        <span class="badge bg-danger ms-auto"
                                            style="font-size: 10px;">{{ $unreadMessages }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="pc-item {{ request()->routeIs('inbox.comments') ? 'active' : '' }}">
                                <a class="pc-link" href="{{ route('inbox.comments') }}">
                                    <span class="pc-mtext">Comments</span>
                                    @php
                                        $unrepliedComments = 0;
                                        if (Auth::user()?->tenant_id) {
                                            $unrepliedComments = \App\Models\Comment::where(
                                                'tenant_id',
                                                Auth::user()->tenant_id,
                                            )
                                                ->where('is_replied', 0)
                                                ->count();
                                        }
                                    @endphp
                                    @if ($unrepliedComments > 0)
                                        <span class="badge bg-warning ms-auto"
                                            style="font-size: 10px;">{{ $unrepliedComments }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="pc-item {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                        <a href="{{ route('ai.settings') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-robot-face" style="font-size: 18px;"></i></span>
                            <span class="pc-mtext">AI Auto Reply</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Laporan & Akun</label>
                    </li>

                    <li class="pc-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                        <a href="{{ route('analytics.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-chart-bar"></i></span>
                            <span class="pc-mtext">Analytics</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('social-accounts.*') ? 'active' : '' }}">
                        <a href="{{ route('social-accounts.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-link-off" style="font-size: 18px;"></i></span>
                            <span class="pc-mtext">Akun Sosial Media</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Pengaturan</label>
                    </li>

                    <li class="pc-item {{ request()->routeIs('subscription.*') ? 'active' : '' }}">
                        <a href="{{ route('subscription.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-credit-card"></i></span>
                            <span class="pc-mtext">Langganan</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <a href="{{ route('profile.edit') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-settings"></i></span>
                            <span class="pc-mtext">Pengaturan Akun</span>
                        </a>
                    </li>

                    <li class="pc-item">
                        <form method="POST" action="{{ route('logout') }}" id="logout-form-sidebar">
                            @csrf
                        </form>
                        <a href="#" class="pc-link"
                            onclick="document.getElementById('logout-form-sidebar').submit()">
                            <span class="pc-micon"><i class="ti ti-logout"></i></span>
                            <span class="pc-mtext">Logout</span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <!-- End Sidebar -->

    <!-- Header -->
    <header class="pc-header">
        <div class="header-wrapper">
            <div class="me-auto pc-mob-drp">
                <ul class="list-unstyled">
                    <li class="pc-h-item pc-sidebar-collapse">
                        <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                            <i class="ti ti-menu-2"></i>
                        </a>
                    </li>
                    <li class="pc-h-item pc-sidebar-popup">
                        <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                            <i class="ti ti-menu-2"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="ms-auto">
                <ul class="list-unstyled">
                    <!-- Quick create -->
                    <li class="pc-h-item">
                        <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm me-2">
                            <i class="ti ti-plus me-1"></i> Buat Post
                        </a>
                    </li>
                    <!-- User profile -->
                    <li class="dropdown pc-h-item header-user-profile">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                            href="#" role="button" aria-haspopup="false" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <img src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/images/user/avatar-2.jpg') }}"
                                alt="user-image" class="user-avtar">
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                            <div class="dropdown-header">
                                <div class="d-flex mb-1">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/images/user/avatar-2.jpg') }}"
                                            alt="user-image" class="user-avtar wid-35">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->tenant?->business_name }}</small>
                                        <br>
                                        <span class="badge bg-primary" style="font-size:10px;">
                                            {{ Auth::user()->tenant?->package?->name ?? 'Trial' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="ti ti-user me-2"></i> Profile
                            </a>
                            <a href="{{ route('subscription.index') }}" class="dropdown-item">
                                <i class="ti ti-credit-card me-2"></i> Langganan
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item text-danger"
                                onclick="document.getElementById('logout-form-sidebar').submit()">
                                <i class="ti ti-power me-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <!-- End Header -->

    <!-- Main Content -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- breadcrumb -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">@yield('page_title', 'Dashboard')</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                @yield('breadcrumb')
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Terjadi Kesalahan:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <footer class="pc-footer">
        <div class="footer-wrapper container-fluid">
            <div class="row">
                <div class="col-sm my-1">
                    <p class="m-0">SocialPilot AI &copy; {{ date('Y') }} &mdash; Kelola semua media sosial dari
                        satu dashboard</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/fonts/custom-font.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/pcoded.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/feather.min.js') }}"></script>

    <script>
        layout_change('light');
    </script>
    <script>
        change_box_container('false');
    </script>
    <script>
        layout_rtl_change('false');
    </script>
    <script>
        preset_change("preset-1");
    </script>
    <script>
        font_change("Public-Sans");
    </script>

    @stack('scripts')
</body>

</html>
