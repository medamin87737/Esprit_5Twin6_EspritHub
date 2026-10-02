<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="@yield('meta_description', 'NutriTrace suit le parcours de vos aliments de la ferme à l\'assiette : traçabilité des lots, empreinte environnementale et certifications vérifiées.')">
    <meta name="theme-color" content="#0b3d24">
    <title>
        @hasSection('title')
            @yield('title') · NutriTrace
        @else
            NutriTrace — De la ferme à l'assiette
        @endif
    </title>
    <link rel="icon" type="image/svg+xml" href="{{ Vite::asset('resources/assets/front/img/favicon.svg') }}">
    @vite([
        'resources/assets/front/css/styles.css',
        'resources/assets/front/css/nutritrace.css',
        'resources/assets/front/js/front.js',
    ])
    @stack('styles')
</head>
<body id="page-top" class="@yield('body_class')">
    @include('partials.front.navbar')
    @include('partials.front.flash')

    <main>
        @yield('content')
    </main>

    @include('partials.front.footer')

    @stack('scripts')
</body>
</html>
