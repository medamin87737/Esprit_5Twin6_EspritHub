<ul class="navbar-nav sidebar sidebar-dark accordion nt-sidebar" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('fournisseur.produits.index') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ Vite::asset('resources/assets/front/img/logo.svg') }}" alt="" width="36" height="36">
        </div>
        <div class="sidebar-brand-text mx-2">Nutri<span>Trace</span></div>
    </a>

    <hr class="sidebar-divider my-0">

    <div class="sidebar-heading mt-3">Espace fournisseur</div>

    <li class="nav-item {{ request()->routeIs('fournisseur.produits.index', 'fournisseur.produits.show', 'fournisseur.produits.edit') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('fournisseur.produits.index') }}">
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            <span>Mes produits</span>
        </a>
    </li>
    <li class="nav-item {{ request()->routeIs('fournisseur.produits.create') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('fournisseur.produits.create') }}">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>
            <span>Ajouter un produit</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Mon compte</div>

    <li class="nav-item">
        <a class="nav-link" href="{{ route('profile.edit') }}">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span>Mon profil</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('front.produits.index') }}">
            <i class="bi bi-basket2" aria-hidden="true"></i>
            <span>Catalogue public</span>
        </a>
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
