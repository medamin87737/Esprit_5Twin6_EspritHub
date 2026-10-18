@extends('layouts.admin')

@section('title', $lot->numero_lot)

@section('content')
    <x-admin.page-header :title="$lot->numero_lot" module="Module 3 · Lot"
                         :subtitle="$lot->produit?->nom" :back="route('admin.lots.index')">
        <x-slot:actions>
            <a href="{{ route('front.lots.search', ['numero' => $lot->numero_lot]) }}" class="btn btn-light" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right mr-1" aria-hidden="true"></i> Vue publique
            </a>
            <a href="{{ route('admin.lots.edit', $lot) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Informations du lot</h2></div>
                <div class="card-body">
                    <div class="nt-role-row">
                        <span class="text-muted">Produit</span>
                        @if ($lot->produit)
                            <a href="{{ route('admin.produits.show', $lot->produit) }}" class="nt-badge">{{ $lot->produit->nom }}</a>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $lot->produit?->categorie?->nom ?? '—' }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Quantité</span><strong>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Production</span><strong>{{ $lot->date_production?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Péremption</span>
                        @if ($lot->estPerime())
                            <span class="nt-badge nt-badge-danger">{{ $lot->date_peremption->format('d/m/Y') }} · périmé</span>
                        @else
                            <strong>{{ $lot->date_peremption?->format('d/m/Y') }}</strong>
                        @endif
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Étapes</span><strong>{{ $lot->etapes->count() }}</strong></div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="nt-card-title mb-0"><i class="bi bi-stars text-success mr-1"></i> Histoire IA du produit</h2>
                    <form action="{{ route('admin.lots.recit-ia', $lot) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-clockwise mr-1"></i> Régénérer
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    @if ($lot->recit_ia)
                        <p class="font-italic text-secondary mb-0 small">« {{ $lot->recit_ia }} »</p>
                    @else
                        <p class="text-muted small mb-0">Aucun récit généré pour le moment. Cliquez sur <em>Régénérer</em> ou ajoutez des étapes.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h2 class="nt-card-title">Empreinte carbone</h2></div>
                <div class="card-body">
                    @if ($lot->empreinteCarbone)
                        <div class="d-flex align-items-center mb-3">
                            <span class="nt-score nt-score-{{ strtolower($lot->empreinteCarbone->score) }} mr-3">{{ $lot->empreinteCarbone->score }}</span>
                            <div>
                                <strong>{{ number_format($lot->empreinteCarbone->co2_total, 2, ',', ' ') }} kg CO₂e</strong>
                                <div class="text-muted small">{{ $lot->empreinteCarbone->methode }} · {{ $lot->empreinteCarbone->indicateurs_count }} indicateur(s)</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.empreintes.show', $lot->empreinteCarbone) }}" class="btn btn-light btn-sm">
                            <i class="bi bi-cloud-haze2 mr-1" aria-hidden="true"></i> Voir l'empreinte
                        </a>
                    @else
                        <p class="text-muted">Aucune empreinte calculée pour ce lot.</p>
                        <a href="{{ route('admin.empreintes.create', ['lot' => $lot->id]) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Calculer l'empreinte
                        </a>
                    @endif
                </div>
            </div>

            @include('pages.admin.analyses._synthese-lot', ['lot' => $lot])

            <div class="card mt-4">
                <div class="card-header"><h2 class="nt-card-title">Analyses qualité</h2></div>
                <div class="card-body">
                    @forelse ($lot->analyses as $analyse)
                        <div class="nt-role-row">
                            <a href="{{ route('admin.analyses.show', $analyse) }}">
                                <i class="bi {{ $analyse->icone() }} mr-1" aria-hidden="true"></i>{{ $analyse->typeLabel() }}
                                <span class="nt-cell-sub d-block">{{ $analyse->laboratoire?->nom }} · {{ $analyse->date_prelevement?->format('d/m/Y') }}</span>
                                @if ($analyse->resume_ia)
                                    <span class="nt-cell-sub d-block text-muted">{{ \Illuminate\Support\Str::limit($analyse->resume_ia, 90) }}</span>
                                @endif
                            </a>
                            <span>
                                @include('pages.admin.analyses._resultat')
                                @if ($analyse->estEnRetard())
                                    <span class="nt-badge nt-badge-danger d-block mt-1">Retard</span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-muted">Aucune analyse enregistrée pour ce lot.</p>
                    @endforelse
                    <a href="{{ route('admin.analyses.create', ['lot' => $lot->id]) }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Enregistrer une analyse
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <x-admin.table-card :items="$lot->etapes" title="Parcours du lot (ordre chronologique)">
                <x-slot:head>
                    <th scope="col">#</th>
                    <th scope="col">Type</th>
                    <th scope="col">Acteur</th>
                    <th scope="col">Lieu</th>
                    <th scope="col">Date et heure</th>
                    <th scope="col">Transport</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($lot->etapes as $etape)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td><span class="nt-badge">{{ $etape->typeLabel() }}</span></td>
                        <td>
                            @if ($etape->acteur)
                                <a href="{{ route('admin.acteurs.show', $etape->acteur) }}" class="nt-cell-title">{{ $etape->acteur->nom }}</a>
                                <div class="nt-cell-sub">{{ $etape->acteur->typeActeur?->libelle }}</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $etape->lieu }}</td>
                        <td class="text-muted">{{ $etape->date_heure?->format('d/m/Y H:i') }}</td>
                        <td class="text-muted">{{ $etape->transportLabel() }}</td>
                        <td class="text-right">
                            <x-admin.row-actions
                                :show="route('admin.etapes.show', $etape)"
                                :edit="route('admin.etapes.edit', $etape)"
                                :delete="route('admin.etapes.destroy', $etape)"
                                confirm="Supprimer cette étape du parcours ?" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-signpost-split" title="Aucune étape pour ce lot">
                        Ajoutez la production, la transformation, la distribution puis la vente pour reconstituer le parcours.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('admin.etapes.create', ['lot' => $lot->id]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter une étape à ce lot
                </a>
            </div>

            {{-- Encadré Intelligence Artificielle & Traçabilité (Module 3) --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h2 class="nt-card-title mb-0">
                        <i class="bi bi-cpu-fill text-primary mr-1"></i> Intelligence Artificielle & Traçabilité
                    </h2>
                    <div class="text-muted small mt-1">
                        Auditeur anti-fraude de cohérence de parcours et conseiller intelligent anti-gaspillage
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Option 1: Auditeur IA de cohérence / Anti-fraude -->
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="p-3 border rounded h-100 bg-white d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <strong class="d-block text-dark">
                                                <i class="bi bi-shield-check text-primary mr-1"></i> Auditeur de Cohérence & Anti-fraude
                                            </strong>
                                            <span class="text-muted small">Contrôle temporel, vitesses GPS, chaîne du froid & péremption</span>
                                        </div>
                                        <form action="{{ route('admin.lots.audit-ia', $lot) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $lot->audit_ia ? 'btn-outline-primary' : 'btn-primary' }}" title="Lancer ou réactualiser l'audit">
                                                <i class="bi {{ $lot->audit_ia ? 'bi-arrow-clockwise' : 'bi-play-fill' }} mr-1"></i>
                                                {{ $lot->audit_ia ? 'Ré-auditer' : 'Lancer l\'audit' }}
                                            </button>
                                        </form>
                                    </div>

                                    @if ($lot->audit_ia)
                                        <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded bg-light">
                                            <div>
                                                <span class="font-weight-bold text-muted small mr-1">Score :</span>
                                                <span class="h5 mb-0 font-weight-bold">{{ $lot->audit_ia['score'] }} / 100</span>
                                            </div>
                                            @if ($lot->audit_ia['statut'] === 'conforme')
                                                <span class="nt-badge nt-badge-success">
                                                    <i class="bi bi-check-circle-fill mr-1"></i> Conforme · Parcours certifié
                                                </span>
                                            @elseif ($lot->audit_ia['statut'] === 'avertissement')
                                                <span class="nt-badge nt-badge-gold">
                                                    <i class="bi bi-exclamation-triangle-fill mr-1"></i> Points de vigilance
                                                </span>
                                            @else
                                                <span class="nt-badge nt-badge-danger">
                                                    <i class="bi bi-shield-slash-fill mr-1"></i> Anomalies détectées
                                                </span>
                                            @endif
                                        </div>

                                        <p class="small text-secondary mb-3 font-italic">
                                            « {{ $lot->audit_ia['synthese'] }} »
                                        </p>

                                        @if (! empty($lot->audit_ia['anomalies']))
                                            <div class="mb-2">
                                                <div class="small font-weight-bold text-danger mb-1">
                                                    <i class="bi bi-exclamation-octagon mr-1"></i> Alertes / Incohérences :
                                                </div>
                                                <ul class="pl-3 mb-0 small text-danger">
                                                    @foreach ($lot->audit_ia['anomalies'] as $anomalie)
                                                        <li>{{ $anomalie }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @if (! empty($lot->audit_ia['points_forts']))
                                            <div class="mt-2">
                                                <div class="small font-weight-bold text-success mb-1">
                                                    <i class="bi bi-check-all mr-1"></i> Points forts validés :
                                                </div>
                                                <ul class="pl-3 mb-0 small text-success">
                                                    @foreach ($lot->audit_ia['points_forts'] as $point)
                                                        <li>{{ $point }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    @else
                                        <div class="p-3 text-center bg-light rounded text-muted small my-3">
                                            <i class="bi bi-shield-shaded d-block mb-2 text-primary" style="font-size: 2rem;"></i>
                                            Aucun audit réalisé pour ce lot.<br>
                                            Cliquez sur <strong>« Lancer l'audit »</strong> pour analyser l'authenticité et la logique du parcours.
                                        </div>
                                    @endif
                                </div>

                                @if ($lot->audit_ia && ! empty($lot->audit_ia['audite_le']))
                                    <div class="text-muted small border-top pt-2 mt-3 text-right">
                                        <i class="bi bi-clock-history mr-1"></i> Audité le {{ $lot->audit_ia['audite_le'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Option 2: Conseiller IA Anti-gaspillage & Rotation -->
                        <div class="col-lg-6">
                            <div class="p-3 border rounded h-100 bg-white d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <strong class="d-block text-dark">
                                                <i class="bi bi-recycle text-success mr-1"></i> Conseiller Anti-gaspillage & Stock
                                            </strong>
                                            <span class="text-muted small">Plan de rotation, démarque promo & valorisation selon la DLC</span>
                                        </div>
                                        <form action="{{ route('admin.lots.conseil-ia', $lot) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $lot->conseil_ia ? 'btn-outline-success' : 'btn-success' }}" title="Générer ou actualiser le plan">
                                                <i class="bi {{ $lot->conseil_ia ? 'bi-arrow-clockwise' : 'bi-magic' }} mr-1"></i>
                                                {{ $lot->conseil_ia ? 'Réactualiser' : 'Générer le plan' }}
                                            </button>
                                        </form>
                                    </div>

                                    @if ($lot->conseil_ia)
                                        <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded bg-light">
                                            <strong class="small text-dark font-weight-bold">
                                                {{ $lot->conseil_ia['titre'] }}
                                            </strong>
                                            @if ($lot->conseil_ia['urgence'] === 'critique')
                                                <span class="nt-badge nt-badge-danger">
                                                    <i class="bi bi-fire mr-1"></i> {{ $lot->conseil_ia['statut_badge'] }}
                                                </span>
                                            @elseif ($lot->conseil_ia['urgence'] === 'attention')
                                                <span class="nt-badge nt-badge-gold">
                                                    <i class="bi bi-hourglass-split mr-1"></i> {{ $lot->conseil_ia['statut_badge'] }}
                                                </span>
                                            @elseif ($lot->conseil_ia['urgence'] === 'perime')
                                                <span class="nt-badge nt-badge-danger">
                                                    <i class="bi bi-x-octagon mr-1"></i> {{ $lot->conseil_ia['statut_badge'] }}
                                                </span>
                                            @else
                                                <span class="nt-badge nt-badge-success">
                                                    <i class="bi bi-check-circle mr-1"></i> {{ $lot->conseil_ia['statut_badge'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="small text-secondary mb-3 font-italic">
                                            « {{ $lot->conseil_ia['synthese'] }} »
                                        </p>

                                        @if (! empty($lot->conseil_ia['actions']))
                                            <div>
                                                <div class="small font-weight-bold text-dark mb-1">
                                                    <i class="bi bi-lightning-charge-fill text-warning mr-1"></i> Actions opérationnelles prioritaires :
                                                </div>
                                                <ul class="pl-3 mb-0 small text-secondary">
                                                    @foreach ($lot->conseil_ia['actions'] as $action)
                                                        <li class="mb-1">{{ $action }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    @else
                                        <div class="p-3 text-center bg-light rounded text-muted small my-3">
                                            <i class="bi bi-box2-heart d-block mb-2 text-success" style="font-size: 2rem;"></i>
                                            Aucun plan anti-gaspillage généré.<br>
                                            Cliquez sur <strong>« Générer le plan »</strong> pour obtenir les actions prioritaires de rotation de stock.
                                        </div>
                                    @endif
                                </div>

                                @if ($lot->conseil_ia && ! empty($lot->conseil_ia['genere_le']))
                                    <div class="text-muted small border-top pt-2 mt-3 text-right">
                                        <i class="bi bi-clock-history mr-1"></i> Recommandation du {{ $lot->conseil_ia['genere_le'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
