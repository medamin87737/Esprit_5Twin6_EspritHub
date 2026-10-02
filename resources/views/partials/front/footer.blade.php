@php($base = request()->routeIs('home') ? '' : route('home'))

<footer class="site-footer">
    <div class="container px-4 px-lg-5">
        <div class="row gy-5">
            <div class="col-lg-4">
                <a class="footer-brand" href="{{ $base }}#page-top">
                    <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="40" height="40">
                    Nutri<span>Trace</span>
                </a>
                <p class="mb-0">La plateforme de traçabilité alimentaire qui remplace les promesses marketing par des preuves : parcours des lots, empreinte carbone et certifications vérifiées.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Plateforme</h6>
                <ul>
                    <li><a href="{{ $base }}#mission">Notre mission</a></li>
                    <li><a href="{{ $base }}#parcours">Parcours d'un lot</a></li>
                    <li><a href="{{ $base }}#galerie">Sur le terrain</a></li>
                    <li><a href="{{ route('login') }}">Espace professionnel</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6>Explorer</h6>
                <ul>
                    <li><a href="{{ route('front.produits.index') }}">Catalogue des produits</a></li>
                    <li><a href="{{ route('front.acteurs.index') }}">Annuaire des acteurs</a></li>
                    <li><a href="{{ route('front.lots.search') }}">Tracer un lot</a></li>
                    <li><a href="{{ route('front.empreintes.index') }}">Comparer les éco-scores</a></li>
                    <li><a href="{{ route('front.certifications.index') }}">Vérifier un label</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6>Contact</h6>
                <ul>
                    <li><i class="bi bi-geo-alt me-2"></i>Technopôle El Ghazala, Ariana</li>
                    <li><i class="bi bi-envelope me-2"></i><a href="mailto:contact@nutritrace.tn">contact@nutritrace.tn</a></li>
                    <li><a href="{{ $base }}#contact"><i class="bi bi-chat-dots me-2"></i>Formulaire de contact</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>&copy; <span data-year>{{ date('Y') }}</span> NutriTrace — Projet académique · Applications Web Avancées</div>
            <div>Template : <a href="https://startbootstrap.com/theme/creative" target="_blank" rel="noopener">Creative</a> par Start Bootstrap</div>
        </div>
    </div>
</footer>
