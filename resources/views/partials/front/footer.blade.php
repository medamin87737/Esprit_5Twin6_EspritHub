@php
    $user = auth()->user();
    $estPro = (bool) $user?->isPro();
    $liens = \App\Support\MenuFront::liens($user);
    $base = request()->routeIs('home') ? '' : route('home');
@endphp

<footer class="site-footer">
    <div class="container px-4 px-lg-5">
        <div class="row gy-5">
            <div class="col-lg-4">
                <a class="footer-brand" href="{{ $estPro ? \App\Support\MenuFront::accueil($user) : $base . '#page-top' }}">
                    <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="40" height="40">
                    Nutri<span>Trace</span>
                </a>
                <p class="mb-0">La plateforme de traçabilité alimentaire qui remplace les promesses marketing par des preuves : parcours des lots, empreinte carbone et certifications vérifiées.</p>
            </div>

            @unless ($estPro)
                <div class="col-6 col-lg-2">
                    <h6>Plateforme</h6>
                    <ul>
                        <li><a href="{{ $base }}#mission">Notre mission</a></li>
                        <li><a href="{{ $base }}#parcours">Parcours d'un lot</a></li>
                        <li><a href="{{ $base }}#galerie">Sur le terrain</a></li>
                        @guest
                            <li><a href="{{ route('register') }}">Devenir partenaire</a></li>
                        @endguest
                    </ul>
                </div>
            @endunless

            <div class="col-6 {{ $estPro ? 'col-lg-5' : 'col-lg-3' }}">
                <h6>{{ $estPro ? 'Mon activité' : 'Explorer' }}</h6>
                <ul>
                    @foreach ($liens as $lien)
                        <li><a href="{{ route($lien['route']) }}">{{ $lien['libelle'] }}</a></li>
                    @endforeach
                    @if ($estPro)
                        <li><a href="{{ route('pro.profil.edit') }}">Profil société</a></li>
                    @else
                        <li><a href="{{ route('front.empreintes.index') }}">Éco-scores</a></li>
                    @endif
                </ul>
            </div>

            <div class="col-lg-3">
                <h6>Contact</h6>
                <ul>
                    <li><i class="bi bi-geo-alt me-2"></i>Technopôle El Ghazala, Ariana</li>
                    <li><i class="bi bi-envelope me-2"></i><a href="mailto:contact@nutritrace.tn">contact@nutritrace.tn</a></li>
                    @unless ($estPro)
                        <li><a href="{{ $base }}#contact"><i class="bi bi-chat-dots me-2"></i>Formulaire de contact</a></li>
                    @endunless
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>&copy; <span data-year>{{ date('Y') }}</span> NutriTrace — Projet académique · Applications Web Avancées</div>
            <div>Template : <a href="https://startbootstrap.com/theme/creative" target="_blank" rel="noopener">Creative</a> par Start Bootstrap</div>
        </div>
    </div>
</footer>
