<header class="masthead hero">
    <video class="masthead-video" autoplay muted loop playsinline preload="metadata"
           poster="{{ Vite::asset('resources/assets/front/img/hero-poster.webp') }}" aria-hidden="true">
        <source src="{{ Vite::asset('resources/assets/front/video/hero-champs.webm') }}" type="video/webm">
    </video>
    <div class="masthead-overlay"></div>

    <div class="container px-4 px-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9 hero-copy text-center">
                <h1 class="hero-title">De la ferme à l'assiette, <span class="highlight">la vérité</span> sur ce que vous mangez.</h1>
                <p class="hero-lead">
                    NutriTrace suit chaque lot alimentaire à travers toute la chaîne — producteur, transformateur, distributeur —
                    et rend visibles son empreinte environnementale et ses certifications vérifiées.
                </p>

                <div class="hero-actions">
                    <a class="btn btn-gold btn-lg" href="#parcours">
                        <i class="bi bi-signpost-split me-2" aria-hidden="true"></i>Explorer la traçabilité
                    </a>
                    <a class="btn btn-outline-light-soft btn-lg" href="#mission">Découvrir la plateforme</a>
                </div>

                @if (Route::has('front.lots.search'))
                    <form class="hero-search" action="{{ route('front.lots.search') }}" method="GET" role="search">
                        <label for="hero-lot" class="visually-hidden">Numéro de lot</label>
                        <span class="input-icon" aria-hidden="true"><i class="bi bi-upc-scan"></i></span>
                        <input class="form-control" id="hero-lot" type="search" name="numero" placeholder="Rechercher un numéro de lot…">
                        <button class="btn btn-primary" type="submit">Tracer</button>
                    </form>
                @endif

                <ul class="hero-points">
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Parcours des lots de bout en bout</li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Labels reliés à leur organisme</li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Éco-score de A à E</li>
                </ul>
            </div>
        </div>
    </div>

    <a href="#galerie" class="hero-scroll" aria-label="Faire défiler vers la suite de la page">
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </a>
</header>
