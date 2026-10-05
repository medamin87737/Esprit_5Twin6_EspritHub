@php($base = request()->routeIs('home') ? '' : route('home'))

<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $base }}#page-top">
            <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="38" height="38">
            <span>Nutri<span class="brand-accent">Trace</span></span>
        </a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.produits.*') ? 'active' : '' }}" href="{{ route('front.produits.index') }}" @if (request()->routeIs('front.produits.*')) aria-current="page" @endif>Catalogue</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.acteurs.*') ? 'active' : '' }}" href="{{ route('front.acteurs.index') }}" @if (request()->routeIs('front.acteurs.*')) aria-current="page" @endif>Acteurs</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.lots.*') ? 'active' : '' }}" href="{{ route('front.lots.search') }}" @if (request()->routeIs('front.lots.*')) aria-current="page" @endif>Traçabilité</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.empreintes.*') ? 'active' : '' }}" href="{{ route('front.empreintes.index') }}" @if (request()->routeIs('front.empreintes.*')) aria-current="page" @endif>Éco-score</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.certifications.*') ? 'active' : '' }}" href="{{ route('front.certifications.index') }}" @if (request()->routeIs('front.certifications.*')) aria-current="page" @endif>Labels</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ $base }}#contact">Contact</a></li>

                @auth
                    <li class="nav-item dropdown ms-lg-3 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle nav-user" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-avatar">{{ auth()->user()->initials() }}</span>
                            <span>{{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end nav-user-menu" aria-labelledby="userMenu">
                            <li class="px-3 py-2">
                                <div class="fw-semibold text-dark">{{ auth()->user()->name }}</div>
                                <div class="small text-muted">{{ auth()->user()->roleLabel() }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2" aria-hidden="true"></i>Mon compte</a></li>
                            @if (auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Espace d'administration</a></li>
                            @elseif (auth()->user()->isFournisseur())
                                <li><a class="dropdown-item" href="{{ route('fournisseur.produits.index') }}"><i class="bi bi-box-seam me-2" aria-hidden="true"></i>Espace fournisseur</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Se déconnecter</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a class="btn btn-primary btn-nav" href="{{ route('login') }}">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i> Connexion
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
