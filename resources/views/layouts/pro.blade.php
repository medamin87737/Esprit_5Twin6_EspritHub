@extends('layouts.front')

@section('body_class', 'pro-body')

@section('content')
    @php($acteur = auth()->user()->acteur)

    <header class="page-header page-header-pro" style="background-image: linear-gradient(180deg, rgba(6, 32, 19, 0.78), rgba(6, 32, 19, 0.92)), url('{{ Vite::asset('resources/assets/front/img/legumes-planche.webp') }}');">
        <div class="container px-4 px-lg-5">
            <h1 class="text-white mb-2">@yield('title')</h1>
            <p class="page-header-lead mb-0">
                @if ($acteur)
                    <i class="bi bi-building me-1" aria-hidden="true"></i>{{ $acteur->nom }}
                @else
                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Profil société à compléter
                @endif
                <span class="label-chip label-chip-light ms-2">{{ auth()->user()->roleLabel() }}</span>
            </p>
        </div>
    </header>

    <section class="pro-section bg-cream">
        <div class="container px-4 px-lg-5">
            @yield('pro_content')
        </div>
    </section>
@endsection
