@php($user = auth()->user())

<nav class="navbar navbar-expand navbar-light bg-white topbar nt-topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-2" type="button" aria-label="Afficher le menu">
        <i class="bi bi-list" style="font-size: 1.35rem;" aria-hidden="true"></i>
    </button>

    <div class="nt-topbar-date d-none d-sm-block">
        <i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>
        <strong>{{ ucfirst(now()->translatedFormat('l j F Y')) }}</strong>
    </div>

    <ul class="navbar-nav ml-auto align-items-center">
        <li class="nav-item">
            <a class="nt-icon-link" href="{{ route('home') }}" target="_blank" rel="noopener" data-toggle="tooltip" data-placement="bottom" title="Voir le site public">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                <span class="sr-only">Voir le site public</span>
            </a>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-none d-lg-flex flex-column text-right mr-3">
                    <span class="nt-user-name">{{ $user->name }}</span>
                    <span class="nt-user-role">{{ $user->roleLabel() }}</span>
                </span>
                <span class="nt-avatar">{{ $user->initials() }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right animated--grow-in" aria-labelledby="userDropdown">
                <div class="px-3 py-2 d-lg-none">
                    <div class="nt-user-name">{{ $user->name }}</div>
                    <div class="nt-user-role">{{ $user->email }}</div>
                </div>
                <a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="bi bi-person" aria-hidden="true"></i> Mon profil</a>
                <a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="bi bi-people" aria-hidden="true"></i> Utilisateurs</a>
                <a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Tableau de bord</a>
                <a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-house" aria-hidden="true"></i> Site public</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Se déconnecter
                </a>
            </div>
        </li>
    </ul>
</nav>
