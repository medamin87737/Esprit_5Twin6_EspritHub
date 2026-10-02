<ul class="navbar-nav sidebar sidebar-dark accordion nt-sidebar" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="36" height="36">
        </div>
        <div class="sidebar-brand-text mx-2">Nutri<span>Trace</span></div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i>
            <span>Tableau de bord</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Modules de gestion</div>

    @foreach (config('nutritrace.modules') as $module)
        @php
            $patterns = collect($module['entites'])->map(fn ($e) => 'admin.' . $e['route'] . '.*')->all();
            $moduleActive = request()->routeIs(...$patterns);
            $collapseId = 'collapseModule' . $module['numero'];
        @endphp
        <li class="nav-item {{ $moduleActive ? 'active' : '' }}">
            <a class="nav-link {{ $moduleActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#{{ $collapseId }}"
               aria-expanded="{{ $moduleActive ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                <i class="bi {{ $module['icone'] }}" aria-hidden="true"></i>
                <span>{{ $module['titre'] }}</span>
            </a>
            <div id="{{ $collapseId }}" class="collapse {{ $moduleActive ? 'show' : '' }}" data-parent="#accordionSidebar">
                <div class="bg-white collapse-inner">
                    <h6 class="collapse-header">Module {{ $module['numero'] }}</h6>
                    <span class="nt-sidebar-owner">Responsable : {{ $module['responsable'] }}</span>
                    @foreach ($module['entites'] as $entite)
                        @if (Route::has('admin.' . $entite['route'] . '.index'))
                            <a class="collapse-item {{ request()->routeIs('admin.' . $entite['route'] . '.*') ? 'active' : '' }}"
                               href="{{ route('admin.' . $entite['route'] . '.index') }}">
                                <i class="bi {{ $entite['icone'] }}" aria-hidden="true"></i> {{ $entite['libelle'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </li>
    @endforeach

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Administration</div>

    <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people-fill" aria-hidden="true"></i>
            <span>Utilisateurs</span>
        </a>
    </li>
    <li class="nav-item {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.profile') }}">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span>Mon profil</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Front Office</div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFront" aria-expanded="false" aria-controls="collapseFront">
            <i class="bi bi-window" aria-hidden="true"></i>
            <span>Pages publiques</span>
        </a>
        <div id="collapseFront" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white collapse-inner">
                <h6 class="collapse-header">Site NutriTrace</h6>
                <a class="collapse-item" href="{{ route('home') }}"><i class="bi bi-house" aria-hidden="true"></i> Accueil</a>
                <a class="collapse-item" href="{{ route('front.produits.index') }}"><i class="bi bi-basket2" aria-hidden="true"></i> Catalogue</a>
                <a class="collapse-item" href="{{ route('front.acteurs.index') }}"><i class="bi bi-people" aria-hidden="true"></i> Annuaire des acteurs</a>
                <a class="collapse-item" href="{{ route('front.lots.search') }}"><i class="bi bi-upc-scan" aria-hidden="true"></i> Traçabilité</a>
                <a class="collapse-item" href="{{ route('front.empreintes.index') }}"><i class="bi bi-cloud-haze2" aria-hidden="true"></i> Éco-scores</a>
                <a class="collapse-item" href="{{ route('front.certifications.index') }}"><i class="bi bi-patch-check" aria-hidden="true"></i> Labels</a>
            </div>
        </div>
    </li>

    <li class="nav-item nt-sidebar-logout">
        <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Se déconnecter</span>
        </a>
    </li>

    <div class="d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Réduire le menu"></button>
    </div>
</ul>
