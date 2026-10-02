<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#064e35">
    <title>@yield('title', 'Tableau de bord') · NutriTrace Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ Vite::asset('resources/assets/front/img/favicon.svg') }}">
    @vite([
        'resources/assets/admin/css/sb-admin-2.css',
        'resources/assets/admin/css/admin.css',
        'resources/assets/admin/js/admin.js',
    ])
    @stack('styles')
</head>
<body id="page-top">
    <div id="wrapper">
        @include('partials.admin.sidebar')

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                @include('partials.admin.topbar')

                <main class="container-fluid px-3 px-md-4">
                    @include('partials.admin.alerts')
                    @yield('content')
                </main>
            </div>

            @include('partials.admin.footer')
        </div>
    </div>

    <a class="scroll-to-top" href="#page-top" aria-label="Revenir en haut de la page">
        <i class="bi bi-chevron-up" aria-hidden="true"></i>
    </a>

    @include('partials.admin.logout-modal')

    @stack('scripts')
</body>
</html>
