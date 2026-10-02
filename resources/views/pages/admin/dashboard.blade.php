@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <section class="nt-welcome">
        <div class="d-flex flex-wrap justify-content-between align-items-end" style="gap: 1.25rem;">
            <div style="position: relative; z-index: 1;">
                <span class="nt-eyebrow">Back Office</span>
                <h1>Bonjour {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</h1>
                <p class="mb-0">Pilotez les cinq modules de NutriTrace : catalogue, acteurs, lots, empreinte et certifications.</p>
            </div>
            <div class="nt-welcome-actions">
                <a href="{{ route('home') }}" class="btn nt-btn-glass" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Voir le site
                </a>
                <a href="{{ route('admin.produits.create') }}" class="btn btn-gold">
                    <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouveau produit
                </a>
            </div>
        </div>
    </section>

    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ route('admin.users.index') }}" class="card h-100 nt-card-link">
                <div class="nt-kpi">
                    <span class="nt-kpi-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div>
                        <div class="nt-kpi-value">{{ $utilisateurs }}</div>
                        <div class="nt-kpi-label">Comptes utilisateurs <i class="bi bi-arrow-right-short" aria-hidden="true"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3 mb-4">
            <div class="card h-100">
                <div class="nt-kpi">
                    <span class="nt-kpi-icon nt-kpi-icon-gold"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <div>
                        <div class="nt-kpi-value">{{ $administrateurs }}</div>
                        <div class="nt-kpi-label">Administrateur{{ $administrateurs > 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-4">
            <div class="card h-100">
                <div class="nt-kpi">
                    <span class="nt-kpi-icon nt-kpi-icon-info"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></span>
                    <div>
                        <div class="nt-kpi-value">{{ $modules->count() }}</div>
                        <div class="nt-kpi-label">Modules de gestion</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-4">
            <div class="card h-100">
                <div class="nt-kpi">
                    <span class="nt-kpi-icon nt-kpi-icon-muted"><i class="bi bi-database" aria-hidden="true"></i></span>
                    <div class="flex-grow-1">
                        <div class="nt-kpi-value">{{ $tablesPretes }}<small class="text-muted" style="font-size: 0.9rem;"> / {{ $totalEntites }}</small></div>
                        <div class="nt-kpi-label mb-2">Tables métier créées</div>
                        <div class="progress nt-progress" role="progressbar" aria-label="Tables créées" aria-valuenow="{{ $tablesPretes }}" aria-valuemin="0" aria-valuemax="{{ $totalEntites }}">
                            <div class="progress-bar" style="width: {{ $totalEntites ? round($tablesPretes / $totalEntites * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h5 font-weight-600 mb-1">Modules</h2>
            <p class="text-muted small mb-0">Nombre d'enregistrements par entité, lu directement dans la base de données.</p>
        </div>
    </div>

    <div class="row">
        @foreach ($modules as $module)
            <div class="col-md-6 col-xl-4 mb-4">
                <article class="card nt-module-card">
                    <div class="nt-module-head">
                        <span class="nt-module-icon"><i class="bi {{ $module['icone'] }}" aria-hidden="true"></i></span>
                        <div>
                            <div class="nt-module-number">Module {{ $module['numero'] }}</div>
                            <h3 class="nt-module-title">{{ $module['titre'] }}</h3>
                            <span class="nt-owner">
                                <span class="nt-avatar">{{ mb_substr($module['responsable'], 0, 1) }}</span>
                                {{ $module['responsable'] }}
                            </span>
                        </div>
                    </div>

                    @foreach ($module['entites'] as $entite)
                        <div class="nt-entity-row">
                            <i class="bi {{ $entite['icone'] }}" aria-hidden="true"></i>
                            <a href="{{ route('admin.' . $entite['route'] . '.index') }}" class="nt-entity-name">{{ $entite['libelle'] }}</a>
                            @if ($entite['total'] === null)
                                <span class="nt-badge nt-badge-muted" data-toggle="tooltip" title="La migration de cette table n'a pas encore été créée">Table à créer</span>
                            @else
                                <span class="nt-entity-count">{{ $entite['total'] }}</span>
                            @endif
                            <a href="{{ route('admin.' . $entite['route'] . '.create') }}" class="nt-action" data-toggle="tooltip" title="Ajouter">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i><span class="sr-only">Ajouter — {{ $entite['libelle'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </article>
            </div>
        @endforeach

        <div class="col-md-6 col-xl-4 mb-4">
            <article class="card h-100">
                <div class="card-header">
                    <h3 class="nt-card-title">Comptes par rôle</h3>
                </div>
                <div class="card-body py-2">
                    @foreach (\App\Models\User::ROLES as $role => $libelle)
                        <div class="nt-role-row">
                            <span>{{ $libelle }}</span>
                            <span class="nt-badge {{ ($roles[$role] ?? 0) > 0 ? '' : 'nt-badge-muted' }}">{{ $roles[$role] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </div>
@endsection
