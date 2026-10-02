@php
    $photos = [
        ['file' => 'producteur-tomates.webp', 'category' => 'Production', 'name' => 'Cultures maraîchères'],
        ['file' => 'recolte-ble.webp', 'category' => 'Production', 'name' => 'Récolte des céréales'],
        ['file' => 'serre-plants.webp', 'category' => 'Agriculture', 'name' => 'Serres responsables'],
        ['file' => 'distributeur-supermarche.webp', 'category' => 'Distribution', 'name' => 'Rayons tracés'],
        ['file' => 'marche-fruits-legumes.webp', 'category' => 'Vente', 'name' => 'Marchés locaux'],
        ['file' => 'consommateur-salade.webp', 'category' => 'Consommation', 'name' => 'Une assiette éclairée'],
    ];
@endphp

<section class="galerie-section" id="galerie">
    <div class="container px-4 px-lg-5">
        <x-front.section-heading eyebrow="Sur le terrain" title="Ceux qui font vivre la chaîne">
            Des champs aux rayons, NutriTrace met en lumière le travail de chaque acteur.
        </x-front.section-heading>
    </div>
    <div id="portfolio">
        <div class="container-fluid p-0">
            <div class="row g-0">
                @foreach ($photos as $photo)
                    <div class="col-lg-4 col-sm-6">
                        <a class="portfolio-box" href="{{ Vite::asset('resources/assets/front/img/' . $photo['file']) }}" title="{{ $photo['name'] }}">
                            <img class="img-fluid" src="{{ Vite::asset('resources/assets/front/img/' . $photo['file']) }}" alt="{{ $photo['name'] }}" loading="lazy">
                            <div class="portfolio-box-caption">
                                <div class="project-category text-white-50">{{ $photo['category'] }}</div>
                                <div class="project-name">{{ $photo['name'] }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
