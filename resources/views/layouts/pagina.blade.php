<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'HortyFru')</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('pagina_web/images/favicon/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.46.0/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/tiny-slider.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.1.1/swiper-bundle.min.css">
    <link rel="stylesheet" href="{{ asset('pagina_web/css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('pagina_web/css/site.css') }}">

    @stack('styles')
</head>
<body>
    <x-pagina.navbar />

    <main>
        @yield('content')
    </main>

    <x-pagina.footer />
    <x-pagina.modal-product />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('pagina_web/js/main.js') }}"></script>
    <script src="{{ asset('pagina_web/js/vendors/countdown.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>
    <script src="{{ asset('pagina_web/js/vendors/tns-slider.js') }}"></script>
    <script src="{{ asset('pagina_web/js/vendors/zoom.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11.1.1/swiper-bundle.min.js"></script>
    <script src="{{ asset('pagina_web/js/vendors/swiper.js') }}"></script>
    <script src="{{ asset('pagina_web/js/vendors/validation.js') }}"></script>

    @stack('scripts')
</body>
</html>
