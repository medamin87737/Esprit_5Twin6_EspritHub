<aside class="nt-auth-visual" style="background-image: url('{{ Vite::asset('resources/assets/front/img/hero-poster.webp') }}');">
    <video autoplay muted loop playsinline preload="metadata" aria-hidden="true"
           poster="{{ Vite::asset('resources/assets/front/img/hero-poster.webp') }}">
        <source src="{{ Vite::asset('resources/assets/front/video/hero-champs.webm') }}" type="video/webm">
    </video>

    <div class="nt-auth-visual-inner">
        <a class="nt-auth-brand" href="{{ route('home') }}">
            <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="40" height="40">
            Nutri<span>Trace</span>
        </a>

        <div>
            <h2 class="nt-auth-headline">De la ferme à l'assiette, <em>chaque étape</em> compte.</h2>
            <p class="nt-auth-lead d-none d-sm-block">
                Producteurs, transformateurs et distributeurs partagent une même source de vérité sur le parcours des aliments.
            </p>
        </div>

        <ul class="nt-auth-points">
            <li><i class="bi bi-signpost-split" aria-hidden="true"></i> Parcours des lots tracé de bout en bout</li>
            <li><i class="bi bi-globe-europe-africa" aria-hidden="true"></i> Empreinte carbone et éco-score de A à E</li>
            <li><i class="bi bi-patch-check" aria-hidden="true"></i> Labels reliés à leur organisme certificateur</li>
        </ul>
    </div>
</aside>
