@extends('layouts.admin')

@section('title', 'Empreinte ' . $empreinte->lot?->numero_lot)

@section('content')
    @php
        $types = config('nutritrace.options.indicateur_types');
        $totaux = $empreinte->indicateurs->groupBy('type');
    @endphp

    <x-admin.page-header :title="'Empreinte · ' . $empreinte->lot?->numero_lot" module="Module 4 · Empreinte carbone"
                         :subtitle="$empreinte->lot?->produit?->nom" :back="route('admin.empreintes.index')">
        <x-slot:actions>
            <a href="{{ route('admin.empreintes.edit', $empreinte) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Bilan carbone</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="nt-score nt-score-{{ strtolower($empreinte->score) }} mr-3" style="width: 3rem; height: 3rem; font-size: 1.5rem;">{{ $empreinte->score }}</span>
                        <div>
                            <div class="h4 mb-0">{{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg</div>
                            <div class="text-muted small">CO₂e par kg de produit</div>
                        </div>
                    </div>
                    <div class="nt-role-row"><span class="text-muted">Méthode</span><strong>{{ $empreinte->methode }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Calculée le</span><strong>{{ $empreinte->date_calcul?->format('d/m/Y') }}</strong></div>
                    <div class="nt-role-row">
                        <span class="text-muted">Facteurs d'émission</span>
                        <a href="{{ route('admin.facteurs-climatiq.index') }}" class="badge badge-success px-2 py-1">
                            <i class="bi bi-cloud-check mr-1" aria-hidden="true"></i> Certifiés Climatiq API
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Lot concerné</h2></div>
                <div class="card-body">
                    @if ($empreinte->lot)
                        <div class="nt-role-row">
                            <span class="text-muted">Lot</span>
                            <a href="{{ route('admin.lots.show', $empreinte->lot) }}" class="nt-badge">{{ $empreinte->lot->numero_lot }}</a>
                        </div>
                        <div class="nt-role-row">
                            <span class="text-muted">Produit</span>
                            @if ($empreinte->lot->produit)
                                <a href="{{ route('admin.produits.show', $empreinte->lot->produit) }}">{{ $empreinte->lot->produit->nom }}</a>
                            @endif
                        </div>
                        <div class="nt-role-row"><span class="text-muted">Catégorie</span><strong>{{ $empreinte->lot->produit?->categorie?->nom }}</strong></div>
                        <div class="nt-role-row"><span class="text-muted">Étapes tracées</span><strong>{{ $empreinte->lot->etapes->count() }}</strong></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            @if ($totaux->isNotEmpty())
                <div class="row">
                    @foreach ($totaux as $type => $groupe)
                        <div class="col-sm-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi {{ $groupe->first()->icone() }} text-primary" style="font-size: 1.5rem;" aria-hidden="true"></i>
                                    <div class="h5 mb-0 mt-2">{{ number_format($groupe->sum('valeur'), 0, ',', ' ') }} {{ $groupe->first()->unite }}</div>
                                    <div class="text-muted small">{{ $types[$type] ?? $type }} · {{ $groupe->count() }} mesure(s)</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <x-admin.table-card :items="$empreinte->indicateurs" title="Indicateurs de l'empreinte">
                <x-slot:head>
                    <th scope="col">Type</th>
                    <th scope="col" class="text-right">Valeur</th>
                    <th scope="col">Source Climatiq API</th>
                    <th scope="col">Étape liée</th>
                    <th scope="col" class="text-right">Actions</th>
                </x-slot:head>

                @foreach ($empreinte->indicateurs as $indicateur)
                    <tr>
                        <td><span class="nt-badge"><i class="bi {{ $indicateur->icone() }}" aria-hidden="true"></i> {{ $indicateur->typeLabel() }}</span></td>
                        <td class="text-right font-weight-600">{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</td>
                        <td>
                            <div class="small font-weight-600 text-dark">{{ $indicateur->sourceClimatiq() }}</div>
                            <span class="badge badge-light border text-muted">{{ number_format($indicateur->facteurValeur(), 4, ',', ' ') }} kg CO₂e / {{ $indicateur->unite }}</span>
                        </td>
                        <td class="text-muted">
                            @if ($indicateur->etape)
                                <a href="{{ route('admin.etapes.show', $indicateur->etape) }}">{{ $indicateur->etape->typeLabel() }} · {{ $indicateur->etape->lieu }}</a>
                            @else
                                Aucune (lot entier)
                            @endif
                        </td>
                        <td class="text-right">
                            <x-admin.row-actions
                                :show="route('admin.indicateurs.show', $indicateur)"
                                :edit="route('admin.indicateurs.edit', $indicateur)"
                                :delete="route('admin.indicateurs.destroy', $indicateur)"
                                confirm="Supprimer cet indicateur ?" />
                        </td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-admin.empty-state icon="bi-speedometer2" title="Aucun indicateur">
                        Détaillez cette empreinte par poste d'impact : eau, énergie, transport et emballage.
                    </x-admin.empty-state>
                </x-slot:empty>
            </x-admin.table-card>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('admin.indicateurs.create', ['empreinte' => $empreinte->id]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un indicateur
                </a>
            </div>

            {{-- 🤖 Module 4 — Conseiller IA en Décarbonation & Plan d'Action Éco-Score --}}
            @php
                $auditIa = session('audit_ia') ?? Illuminate\Support\Facades\Cache::get("empreinte_audit_ia_{$empreinte->id}");
            @endphp

            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f7fdf9 100%); border-left: 4px solid #10b981 !important;">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center pb-0">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white mr-3" style="width: 2.75rem; height: 2.75rem; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                            <i class="bi bi-robot" style="font-size: 1.35rem;"></i>
                        </div>
                        <div>
                            <h3 class="h5 font-weight-bold mb-0 text-dark">Conseiller IA Décarbonation</h3>
                            <div class="small text-muted">Audit intelligent et optimisation de l'Éco-Score basé sur les indicateurs Climatiq</div>
                        </div>
                    </div>

                    <div class="mt-2 mt-md-0 d-flex align-items-center">
                        @if ($auditIa)
                            @if (($auditIa['source'] ?? '') === 'openrouter_live')
                                <span class="badge badge-success px-3 py-2 mr-2">
                                    <i class="bi bi-cpu mr-1"></i> IA Connectée ({{ $auditIa['modele_utilise'] ?? 'Gemini' }})
                                </span>
                            @else
                                <span class="badge badge-info px-3 py-2 mr-2">
                                    <i class="bi bi-shield-check mr-1"></i> {{ $auditIa['modele_utilise'] ?? 'Moteur Expert' }}
                                </span>
                            @endif

                            <form action="{{ route('admin.empreintes.audit-ia', $empreinte) }}" method="POST" class="d-inline" onsubmit="document.getElementById('btn-relancer-ia').disabled = true; document.getElementById('spinner-relance').classList.remove('d-none');">
                                @csrf
                                <input type="hidden" name="force" value="1">
                                <button type="submit" id="btn-relancer-ia" class="btn btn-sm btn-outline-secondary">
                                    <span id="spinner-relance" class="spinner-border spinner-border-sm d-none mr-1" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-arrow-clockwise mr-1"></i> Réanalyser
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if ($auditIa)
                        {{-- Diagnostic du point critique --}}
                        <div class="alert alert-warning border-0 d-flex align-items-start mb-4" style="background-color: #fffbeb; color: #92400e; border-radius: 8px;">
                            <i class="bi bi-exclamation-triangle-fill mr-3 mt-1" style="font-size: 1.5rem; color: #d97706;"></i>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold" style="font-size: 1.05rem;">
                                    Point noir identifié : <span class="badge badge-warning text-dark px-2 py-1 ml-1">{{ $auditIa['point_critique'] }}</span>
                                    <span class="small font-weight-normal text-muted ml-2">({{ $auditIa['pourcentage_critique'] }}% des émissions globales)</span>
                                </div>
                                <div class="mt-1 small">{{ $auditIa['synthese'] }}</div>
                            </div>
                        </div>

                        {{-- Projection de progression de l'Éco-Score --}}
                        @if (isset($auditIa['projection']))
                            <div class="card bg-white border mb-4 shadow-none" style="border-radius: 8px;">
                                <div class="card-body py-3">
                                    <div class="row align-items-center text-center text-md-left">
                                        <div class="col-md-5 mb-3 mb-md-0">
                                            <div class="text-muted small text-uppercase font-weight-bold">Trajectoire Éco-Score</div>
                                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mt-2">
                                                <span class="nt-score nt-score-{{ strtolower($auditIa['projection']['score_actuel'] ?? $empreinte->score) }}" style="width: 2.4rem; height: 2.4rem; font-size: 1.1rem;">{{ $auditIa['projection']['score_actuel'] ?? $empreinte->score }}</span>
                                                <i class="bi bi-arrow-right mx-3 text-muted" style="font-size: 1.4rem;"></i>
                                                <span class="nt-score nt-score-{{ strtolower($auditIa['projection']['nouveau_score'] ?? 'B') }}" style="width: 2.8rem; height: 2.8rem; font-size: 1.3rem; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);">{{ $auditIa['projection']['nouveau_score'] ?? 'B' }}</span>
                                                <span class="badge badge-success ml-3 px-2 py-1 font-weight-bold">
                                                    -{{ $auditIa['projection']['reduction_totale_pct'] }}% CO₂e
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-7 border-left-md pl-md-4">
                                            <div class="small font-weight-600 text-dark mb-1">Impact projeté :</div>
                                            <div class="small text-muted mb-0">
                                                {{ $auditIa['projection']['verdict'] ?? '' }}
                                            </div>
                                            <div class="small text-muted mt-1">
                                                Cible estimée : <strong>{{ number_format((float) ($auditIa['projection']['co2_cible'] ?? 0), 2, ',', ' ') }} kg CO₂e / unité</strong> (contre {{ number_format((float) ($auditIa['projection']['co2_actuel'] ?? $empreinte->co2_total), 2, ',', ' ') }} kg actuellement).
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 3 Actions de Décarbonation --}}
                        <h4 class="h6 font-weight-bold text-dark mb-3">
                            <i class="bi bi-lightning-charge text-success mr-1"></i> Plan d'action prioritaire recommandé par l'IA :
                        </h4>

                        <div class="row">
                            @foreach ($auditIa['actions'] ?? [] as $index => $action)
                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100 bg-white border shadow-none" style="border-radius: 8px;">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge badge-dark font-weight-bold">#{{ $index + 1 }}</span>
                                                <span class="badge badge-light border text-uppercase" style="font-size: 0.7rem;">{{ $action['poste'] ?? 'Général' }}</span>
                                            </div>
                                            <h5 class="h6 font-weight-bold text-dark mb-2">{{ $action['titre'] }}</h5>
                                            <p class="small text-muted mb-3 flex-grow-1">{{ $action['description'] }}</p>

                                            <div class="border-top pt-2 mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small text-muted">Gain estimé</span>
                                                    <strong class="text-success small">-{{ number_format((float) ($action['gain_co2_estime'] ?? 0), 3, ',', ' ') }} kg CO₂e</strong>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge badge-light text-muted border" style="font-size: 0.7rem;">{{ $action['faisabilite'] ?? 'Facile' }}</span>
                                                    <span class="small text-muted" style="font-size: 0.75rem;"><i class="bi bi-clock mr-1"></i>{{ $action['delai'] ?? 'Court terme' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- État initial avant déclenchement de l'IA --}}
                        <div class="text-center py-4">
                            <p class="text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
                                Déclenchez l'analyse IA pour scanner les indicateurs d'émissions (eau, énergie, transport, emballage), identifier les goulots d'étranglement carbone et générer une feuille de route pour atteindre l'Éco-Score A ou B.
                            </p>

                            <form action="{{ route('admin.empreintes.audit-ia', $empreinte) }}" method="POST" id="form-lancer-ia" onsubmit="document.getElementById('btn-lancer-ia').disabled = true; document.getElementById('spinner-lancer').classList.remove('d-none'); document.getElementById('btn-text').innerText = 'Analyse de décarbonation en cours...';">
                                @csrf
                                <button type="submit" id="btn-lancer-ia" class="btn btn-success px-4 py-2 font-weight-bold shadow-sm">
                                    <span id="spinner-lancer" class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true"></span>
                                    <span id="btn-text"><i class="bi bi-magic mr-2"></i> Lancer l'audit IA de décarbonation</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
