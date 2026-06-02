<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title', 'Mantis Bootstrap 5 Admin Template')</title>
    <!-- [Meta] -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- [Favicon] icon -->
    <link rel="icon" href="{{ asset('images/logo/logo.png') }}" type="image/x-icon">

    <!-- [Google Font] Family -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">

    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/tabler-icons.min.css') }}" >
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/feather.css') }}" >
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/fontawesome.css') }}" >
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/fonts/material.css') }}" >

    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/css/style.css') }}" id="main-style-link" >
    <link rel="stylesheet" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/css/style-preset.css') }}" >

    <style>
        .pc-sidebar .m-header .b-brand img.logo-lg-main {
            width: 140px !important;
            height: auto !important;
            display: inline-block !important;
        }
        .pc-sidebar.pc-sidebar-hide .m-header .b-brand img.logo-lg-main {
            display: none !important;
        }
        .pc-sidebar .m-header .b-brand img.logo-sm-main {
            width: 35px !important;
            height: auto !important;
            display: none !important;
        }
        .pc-sidebar.pc-sidebar-hide .m-header .b-brand img.logo-sm-main {
            display: inline-block !important;
        }
    </style>

    @stack('styles')
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- [ Sidebar Menu ] start -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ route('dashboard') }}" class="b-brand text-primary">
                    <img src="{{ asset('images/logo/logo.png') }}" class="logo-lg-main" alt="logo">
                    <img src="{{ asset('images/logo/logo.png') }}" class="logo-sm-main" alt="logo">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar">
                    <li class="pc-item">
                        <a href="{{ route('dashboard') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Utama</label>
                        <i class="ti ti-calendar-event"></i>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('bookings.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Bookings</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('bookings.calendar') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar"></i></span>
                            <span class="pc-mtext">Kalender Booking</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('customers.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-users"></i></span>
                            <span class="pc-mtext">Customers</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Data Master (Setup)</label>
                        <i class="ti ti-database"></i>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('branches.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-building"></i></span>
                            <span class="pc-mtext">Branches (Cabang)</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('operational-hours.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-clock"></i></span>
                            <span class="pc-mtext">Jam Operasional</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('services.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-package"></i></span>
                            <span class="pc-mtext">Services (Layanan)</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Pengaturan & Integrasi</label>
                        <i class="ti ti-settings"></i>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('payment-settings.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-credit-card"></i></span>
                            <span class="pc-mtext">Payment Settings</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('notification-settings.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-bell"></i></span>
                            <span class="pc-mtext">Notification Settings</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('plans.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-credit-card"></i></span>
                            <span class="pc-mtext">Paket Langganan</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('whatsapp.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-brand-whatsapp"></i></span>
                            <span class="pc-mtext">WhatsApp Bot</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Lainnya</label>
                        <i class="ti ti-brand-chrome"></i>
                    </li>
                    <li class="pc-item">
                        <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
                            @csrf
                        </form>
                        <a href="#" class="pc-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="pc-micon"><i class="ti ti-logout"></i></span>
                            <span class="pc-mtext">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- [ Sidebar Menu ] end -->

    <!-- [ Header Topbar ] start -->
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
                    <li class="dropdown pc-h-item header-user-profile">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" data-bs-auto-close="outside" aria-expanded="false">
                            <img src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar">
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                            <div class="dropdown-header">
                                <div class="d-flex mb-1">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar wid-35">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">{{ Auth::user()->name }}</h6>
                                        <span>{{ Auth::user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="ti ti-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ti ti-power"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
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
            <!-- [ breadcrumb ] end -->

            <div class="row">
                <div class="col-sm-12">
                    @if(session('success'))
                        <x-ui.alert variant="success" :message="session('success')" class="mb-4" />
                    @endif

                    @if(session('error'))
                        <x-ui.alert variant="error" :message="session('error')" class="mb-4" />
                    @endif

                    @if($errors->any())
                        <x-ui.alert variant="error" title="Terjadi Kesalahan" class="mb-4">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif
                </div>
            </div>

            <!-- [ Main Content ] start -->
            @yield('content')
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <footer class="pc-footer">
        <div class="footer-wrapper container-fluid">
            <div class="row">
                <div class="col-sm my-1">
                    <p class="m-0">Booknesia.com &#9829; Booking lebih mudah</p>
                </div>
                <div class="col-auto my-1">
                    <ul class="list-inline footer-link mb-0">
                        
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Required Js -->
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/fonts/custom-font.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/pcoded.js') }}"></script>
    <script src="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/js/plugins/feather.min.js') }}"></script>

    <script>layout_change('light');</script>
    <script>change_box_container('false');</script>
    <script>layout_rtl_change('false');</script>
    <script>preset_change("preset-1");</script>
    <script>font_change("Public-Sans");</script>

    @stack('scripts')
</body>
</html>
