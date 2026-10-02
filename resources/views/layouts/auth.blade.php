<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#064e35">
    <title>@yield('title') · NutriTrace</title>
    <link rel="icon" type="image/svg+xml" href="{{ Vite::asset('resources/assets/front/img/favicon.svg') }}">
    @vite([
        'resources/assets/admin/css/sb-admin-2.css',
        'resources/assets/admin/css/admin.css',
        'resources/assets/admin/js/admin.js',
    ])
</head>
<body>
    <main class="nt-auth">
        <div class="nt-auth-card">
            <div class="row no-gutters">
                <div class="col-lg-6 col-xl-7">
                    @include('partials.auth.visual')
                </div>
                <div class="col-lg-6 col-xl-5">
                    <div class="nt-auth-form">
                        <div class="nt-auth-form-inner">
                            <a class="nt-auth-back" href="{{ route('home') }}">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour au site
                            </a>
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
