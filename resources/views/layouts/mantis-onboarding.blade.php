<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title', 'Mantis Onboarding')</title>
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

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .step-item {
            position: relative;
            z-index: 1;
        }
        .step-item::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            z-index: -1;
        }
        .step-item:last-child::after {
            display: none;
        }
        .step-item.active::after {
            background: #1890ff;
        }
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            color: #bfbfbf;
            transition: all 0.3s;
        }
        .step-item.completed .step-icon {
            background: #52c41a;
            border-color: #52c41a;
            color: #fff;
        }
        .step-item.active .step-icon {
            background: #1890ff;
            border-color: #1890ff;
            color: #fff;
        }
        .step-label {
            font-size: 12px;
            color: #8c8c8c;
            text-align: center;
        }
        .step-item.active .step-label {
            color: #262626;
            font-weight: 600;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo/logo-dark.svg') }}" alt="img" class="mb-3" style="height: 30px;">
                    <h2 class="f-w-600">@yield('onboarding_title', 'Setup Your Business')</h2>
                    <p class="text-muted">@yield('onboarding_subtitle', 'Lengkapi setup bisnis kamu dalam beberapa langkah mudah')</p>
                </div>

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

                @yield('content')
            </div>
        </div>
    </div>

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
