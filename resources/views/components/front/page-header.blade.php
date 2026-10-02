@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'image' => 'legumes-planche.webp',
])

<header class="page-header" style="background-image: linear-gradient(180deg, rgba(6, 32, 19, 0.72), rgba(6, 32, 19, 0.88)), url('{{ Vite::asset('resources/assets/front/img/' . $image) }}');">
    <div class="container px-4 px-lg-5 text-center">
        @if ($eyebrow)
            <span class="eyebrow eyebrow-gold">{{ $eyebrow }}</span>
        @endif
        <h1 class="text-white mb-3">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-header-lead mx-auto mb-0">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</header>
