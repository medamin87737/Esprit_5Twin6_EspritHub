@extends('layouts.admin')

@section('title', 'Facteurs Climatiq API')

@section('content')
    <x-admin.page-header title="Facteurs d'émission Climatiq API" module="Module 4 · Empreinte environnementale (Sahar)"
                         subtitle="Intégration d'API pour les facteurs d'émission officiels et certifiés (ADEME, DEFRA, IEA, Ecoinvent).">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.facteurs-climatiq.synchroniser') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary shadow-sm">
                    <i class="bi bi-arrow-repeat mr-1" aria-hidden="true"></i> Synchroniser les facteurs
                </button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill mr-2" aria-hidden="true"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Bandeau d'état et KPIs --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Moteur Climatiq</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $mode }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi {{ $estConfigure ? 'bi-cloud-check text-success' : 'bi-cpu text-info' }}" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Postes d'impact</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($facteurs) }} indicateurs</div>
                            <div class="small text-muted">Eau, Énergie, Transport, Emballage</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-speedometer2 text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Sources certifiées</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">ADEME · DEFRA · IEA</div>
                            <div class="small text-muted">Bases de référence 2026</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-patch-check text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Mise en cache</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">Active (30 jours)</div>
                            <div class="small text-muted">Performances et quotas préservés</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hdd-network text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des facteurs officiels --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h2 class="m-0 font-weight-bold text-primary nt-card-title">
                <i class="bi bi-table mr-1"></i> Facteurs d'émission officiels synchronisés
            </h2>
            <span class="badge badge-success px-2 py-1">Standard GHG Protocol & ISO 14067</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">Indicateur</th>
                            <th scope="col" class="text-right">Facteur d'émission</th>
                            <th scope="col">Climatiq Activity ID</th>
                            <th scope="col">Source officielle certifiée</th>
                            <th scope="col">Périmètre / Région</th>
                            <th scope="col" class="text-center">Incertitude</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facteurs as $f)
                            <tr>
                                <td>
                                    <span class="nt-badge">
                                        <i class="bi {{ $f['icone'] }} mr-1"></i> {{ $f['libelle'] }}
                                    </span>
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    {{ number_format($f['valeur'], 4, ',', ' ') }}
                                    <span class="small text-muted font-weight-normal d-block">{{ $f['unite'] }}</span>
                                </td>
                                <td>
                                    <code>{{ $f['activite_id'] }}</code>
                                </td>
                                <td>
                                    <div class="font-weight-600 text-dark">{{ $f['source'] }}</div>
                                    <div class="small text-muted">Version : {{ $f['annee'] }} · Mode : {{ $f['mode'] }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border">{{ $f['region'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $f['incertitude'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer small text-muted d-flex justify-content-between">
            <span>Ces facteurs sont automatiquement injectés dans le moteur de calcul <code>CalculEmpreinte::contributionCo2()</code>.</span>
            <span>Dernière vérification : {{ current($facteurs)['derniere_synchro'] ?? now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- Widget Simulateur d'estimation en direct --}}
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h2 class="m-0 font-weight-bold text-primary nt-card-title">
                        <i class="bi bi-calculator mr-1"></i> Simulateur d'estimation Climatiq API
                    </h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Testez une requête d'émission directement via l'API Climatiq pour une quantité donnée avant de l'ajouter à un lot.
                    </p>

                    <form method="GET" action="{{ route('admin.facteurs-climatiq.index') }}">
                        <div class="form-group mb-3">
                            <label for="test_type" class="form-label font-weight-600">Type d'activité / Indicateur</label>
                            <select id="test_type" name="test_type" class="custom-select" required>
                                @foreach ($facteurs as $cle => $f)
                                    <option value="{{ $cle }}" @selected(request('test_type') === $cle)>
                                        {{ $f['libelle'] }} ({{ $f['unite_mesure'] }}) — {{ $f['source'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="test_valeur" class="form-label font-weight-600">Quantité mesurée</label>
                            <input type="number" step="0.01" min="0.01" id="test_valeur" name="test_valeur"
                                   class="form-control" value="{{ request('test_valeur', 100) }}" required placeholder="Ex: 100">
                        </div>

                        <button type="submit" class="btn btn-outline-primary btn-block">
                            <i class="bi bi-play-circle mr-1"></i> Calculer l'impact avec Climatiq
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            @if ($estimation)
                <div class="card shadow-sm border-left-success h-100">
                    <div class="card-header py-3 bg-light">
                        <h2 class="m-0 font-weight-bold text-success nt-card-title">
                            <i class="bi bi-check2-circle mr-1"></i> Résultat d'estimation retourné
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="nt-score nt-score-a mr-3" style="width: 3.5rem; height: 3.5rem; font-size: 1.5rem;">
                                <i class="bi bi-cloud-check"></i>
                            </div>
                            <div>
                                <div class="h3 mb-0 text-success font-weight-bold">{{ number_format($estimation['co2e'], 4, ',', ' ') }} kg CO₂e</div>
                                <div class="small text-muted">Émissions estimées pour {{ number_format($estimation['valeur_saisie'], 2, ',', ' ') }} {{ $facteurs[$estimation['type']]['unite_mesure'] ?? '' }}</div>
                            </div>
                        </div>

                        <hr>

                        <div class="nt-role-row">
                            <span class="text-muted">Activité Climatiq</span>
                            <code>{{ $estimation['activite_id'] }}</code>
                        </div>
                        <div class="nt-role-row">
                            <span class="text-muted">Source officielle</span>
                            <strong>{{ $estimation['source'] }}</strong>
                        </div>
                        <div class="nt-role-row">
                            <span class="text-muted">Mode d'exécution</span>
                            <span class="badge badge-success">{{ $estimation['statut'] === 'api_reelle' ? 'API Climatiq Live' : 'Sandbox Certifiée ADEME' }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="card shadow-sm h-100 border-dashed text-center p-4 d-flex align-items-center justify-content-center">
                    <div class="text-muted">
                        <i class="bi bi-arrow-left-circle" style="font-size: 2.5rem;"></i>
                        <h3 class="h6 mt-2 mb-1">Aucune simulation en cours</h3>
                        <p class="small mb-0">Remplissez le formulaire de gauche pour tester une estimation en direct.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
