<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title', 'Mantis Admin')</title>
    <!-- [Meta] -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- [Favicon] icon -->
    <link rel="icon" href="{{ asset('mantis-free-bootstrap-admin-template/dist/assets/images/favicon.svg') }}" type="image/x-icon">

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

    <div class="auth-main">
        <div class="auth-wrapper v3">
            <div class="auth-form w-100" style="max-width: 800px;">
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
