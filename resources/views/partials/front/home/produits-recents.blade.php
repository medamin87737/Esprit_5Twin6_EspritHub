<section class="page-section bg-cream" id="produits-recents">
    <div class="container px-4 px-lg-5">
        <x-front.section-heading eyebrow="Nouveautés" title="Produits récemment ajoutés">
            Les dernières fiches publiées, avec leur score environnemental et leurs labels en cours de validité.
        </x-front.section-heading>

        @if ($produitsRecents->isEmpty())
            <x-front.empty-state icon="bi-basket2" title="Aucun produit pour le moment">
                Les produits apparaîtront ici dès leur enregistrement par les producteurs et transformateurs.
            </x-front.empty-state>
        @else
            <div class="row g-4">
                @foreach ($produitsRecents as $produit)
                    <div class="col-sm-6 col-lg-3">
                        <x-front.produit-card :produit="$produit" />
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('front.produits.index') }}" class="btn btn-primary btn-lg rounded-pill px-4">
                    Voir tout le catalogue <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>
</section>
