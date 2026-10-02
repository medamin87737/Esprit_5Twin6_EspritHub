@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <x-admin.page-header title="Utilisateurs" module="Administration"
                         subtitle="Les comptes qui accèdent à NutriTrace, leur rôle et leur statut.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-user-plus mr-1" aria-hidden="true"></i> Nouvel utilisateur
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        @foreach ([
            ['valeur' => $stats['total'], 'libelle' => 'Comptes au total', 'icone' => 'fa-users', 'classe' => ''],
            ['valeur' => $stats['admins'], 'libelle' => 'Administrateurs', 'icone' => 'fa-user-shield', 'classe' => 'nt-kpi-icon-gold'],
            ['valeur' => $stats['actifs'], 'libelle' => 'Comptes actifs', 'icone' => 'fa-user-check', 'classe' => 'nt-kpi-icon-info'],
            ['valeur' => $stats['nouveaux'], 'libelle' => 'Inscrits sur 30 jours', 'icone' => 'fa-chart-line', 'classe' => 'nt-kpi-icon-muted'],
        ] as $kpi)
            <div class="col-sm-6 col-xl-3 mb-4">
                <div class="card h-100">
                    <div class="nt-kpi">
                        <span class="nt-kpi-icon {{ $kpi['classe'] }}"><i class="fa-solid {{ $kpi['icone'] }}" aria-hidden="true"></i></span>
                        <div>
                            <div class="nt-kpi-value">{{ $kpi['valeur'] }}</div>
                            <div class="nt-kpi-label">{{ $kpi['libelle'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-admin.table-card :items="$users" title="Liste des comptes" search-placeholder="Nom ou e-mail…" :filter-keys="['q', 'role', 'statut']">
        <x-slot:filters>
            <label for="filter-role" class="sr-only">Rôle</label>
            <select id="filter-role" name="role" class="custom-select">
                <option value="">Tous les rôles</option>
                @foreach (\App\Models\User::ROLES as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('role') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <label for="filter-statut" class="sr-only">Statut</label>
            <select id="filter-statut" name="statut" class="custom-select">
                <option value="">Tous les statuts</option>
                <option value="actif" @selected(request('statut') === 'actif')>Actifs</option>
                <option value="suspendu" @selected(request('statut') === 'suspendu')>Suspendus</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <th scope="col">Utilisateur</th>
            <th scope="col">Rôle</th>
            <th scope="col">Statut</th>
            <th scope="col">Inscription</th>
            <th scope="col">Dernière connexion</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($users as $compte)
            <tr>
                <td>
                    <div class="d-flex align-items-center" style="gap: 0.75rem;">
                        <span class="nt-avatar">{{ $compte->initials() }}</span>
                        <div>
                            <div class="nt-cell-title">
                                {{ $compte->name }}
                                @if ($compte->is(auth()->user()))
                                    <span class="nt-badge nt-badge-muted ml-1">Vous</span>
                                @endif
                            </div>
                            <div class="nt-cell-sub">{{ $compte->email }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="nt-badge {{ $compte->isAdmin() ? 'nt-badge-gold' : '' }}">
                        @if ($compte->isAdmin())<i class="fa-solid fa-shield-halved" aria-hidden="true"></i>@endif
                        {{ $compte->roleLabel() }}
                    </span>
                </td>
                <td>
                    @if ($compte->active)
                        <span class="nt-badge"><i class="fa-solid fa-circle" style="font-size: 0.45rem;" aria-hidden="true"></i> Actif</span>
                    @else
                        <span class="nt-badge nt-badge-danger"><i class="fa-solid fa-ban" aria-hidden="true"></i> Suspendu</span>
                    @endif
                </td>
                <td class="text-muted">{{ $compte->created_at?->format('d/m/Y') }}</td>
                <td class="text-muted">
                    @if ($compte->last_login_at)
                        <span data-toggle="tooltip" title="{{ $compte->last_login_at->format('d/m/Y à H:i') }}">{{ $compte->last_login_at->diffForHumans() }}</span>
                    @else
                        Jamais
                    @endif
                </td>
                <td class="text-right">
                    <x-admin.row-actions
                        :edit="route('admin.users.edit', $compte)"
                        :delete="$compte->is(auth()->user()) ? null : route('admin.users.destroy', $compte)"
                        :confirm="'Supprimer définitivement le compte de ' . $compte->name . ' ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-people" title="Aucun compte utilisateur">
                Créez un premier compte ou laissez les visiteurs s'inscrire depuis le site public.
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
