<section class="page-section cta-section text-white" style="background-image: url('{{ Vite::asset('resources/assets/front/img/legumes-planche.webp') }}');">
    <div class="container px-4 px-lg-5">
        <div class="row">
            <div class="col-lg-7 reveal">
                <span class="eyebrow eyebrow-gold">Professionnels</span>
                <h2 class="text-white mb-3">Producteur, transformateur ou distributeur ?</h2>
                <p class="text-white-75 mb-4">
                    Rejoignez le réseau NutriTrace et valorisez des pratiques réellement durables.
                    Vos efforts deviennent visibles, mesurables et vérifiables par vos clients.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-gold btn-lg" href="#contact">Rejoindre le réseau</a>
                    @guest
                        <a class="btn btn-outline-light-soft btn-lg" href="{{ route('register') }}">Créer un compte professionnel</a>
                    @endguest
                    @can('espace-pro')
                        <a class="btn btn-outline-light-soft btn-lg" href="{{ route('pro.dashboard') }}">Accéder à mon tableau de bord</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</section>
