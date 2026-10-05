@php
    $user = auth()->user();
    $liens = \App\Support\MenuFront::liens($user);
    $logo = $user?->isPro() ? \App\Support\MenuFront::accueil($user) : (request()->routeIs('home') ? '' : route('home')) . '#page-top';
@endphp

<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $logo }}">
            <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="38" height="38">
            <span>Nutri<span class="brand-accent">Trace</span></span>
        </a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-lg-center">
                @foreach ($liens as $lien)
                    @php($actif = request()->routeIs(...$lien['actif']))
                    <li class="nav-item">
                        <a class="nav-link {{ $actif ? 'active' : '' }}" href="{{ route($lien['route']) }}" @if ($actif) aria-current="page" @endif>
                            <i class="bi {{ $lien['icone'] }} me-1 d-lg-none" aria-hidden="true"></i>{{ $lien['libelle'] }}
                        </a>
                    </li>
                @endforeach

                @auth
                    <li class="nav-item dropdown ms-lg-3 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle nav-user" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-avatar">{{ $user->initials() }}</span>
                            <span>{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end nav-user-menu" aria-labelledby="userMenu">
                            <li class="px-3 py-2">
                                <div class="fw-semibold text-dark">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->roleLabel() }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>

                            @can('espace-consommateur')
                                <li><a class="dropdown-item" href="{{ route('consommateur.scans.index') }}"><i class="bi bi-clock-history me-2" aria-hidden="true"></i>Historique des scans</a></li>
                            @endcan

                            @can('espace-pro')
                                <li><a class="dropdown-item" href="{{ route('pro.profil.edit') }}"><i class="bi bi-building me-2" aria-hidden="true"></i>Profil société</a></li>
                            @endcan

                            @if ($user->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Espace d'administration</a></li>
                            @endif

                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2" aria-hidden="true"></i>Mon compte</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Se déconnecter</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0 d-flex gap-2">
                        <a class="btn btn-outline-primary btn-nav btn-nav-outline" href="{{ route('register') }}">Inscription</a>
                        <a class="btn btn-primary btn-nav" href="{{ route('login') }}">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i> Connexion
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
