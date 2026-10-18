@extends('layouts.pro')

@section('title', 'Qualité')

@section('pro_content')
    <div class="pro-toolbar">
        <div>
            <h2 class="h4 mb-1"><i class="bi bi-clipboard2-pulse me-2 text-success" aria-hidden="true"></i>Qualité · Module 2</h2>
            <p class="text-muted mb-0 small">Analyses labo + fonctions avancées IA (résumé, extraction, score de risque).</p>
        </div>
    </div>

    {{-- Fonctions avancées (visibilité pour la grille) --}}
    <div class="panel mb-4">
        <h3 class="panel-title mb-3"><i class="bi bi-stars" aria-hidden="true"></i>Fonctions avancées</h3>
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="fw-semibold text-success"><i class="bi bi-file-earmark-text me-1"></i>Lire le rapport</div>
                <p class="text-muted mb-0">TXT/DOCX → préremplissage IA à la création d'analyse.</p>
            </div>
            <div class="col-md-3">
                <div class="fw-semibold text-success"><i class="bi bi-magic me-1"></i>Extraire</div>
                <p class="text-muted mb-0">Remplit type, résultat et dates depuis le rapport.</p>
            </div>
            <div class="col-md-3">
                <div class="fw-semibold text-success"><i class="bi bi-chat-quote me-1"></i>Résumé IA</div>
                <p class="text-muted mb-0">Synthèse qualité enregistrée sur l'analyse.</p>
            </div>
            <div class="col-md-3">
                <div class="fw-semibold text-success"><i class="bi bi-shield-check me-1"></i>Score de risque</div>
                <p class="text-muted mb-0">Faible / moyen / élevé selon l'historique du lot.</p>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card"><span class="kpi-value">{{ $stats['lots'] }}</span><span class="kpi-label">lot(s)</span></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card"><span class="kpi-value">{{ $stats['analyses'] }}</span><span class="kpi-label">analyse(s)</span></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card"><span class="kpi-value">{{ $stats['non_conformes'] }}</span><span class="kpi-label">non conforme(s)</span></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card"><span @class(['kpi-value', 'text-warning' => $stats['en_retard'] > 0])>{{ $stats['en_retard'] }}</span><span class="kpi-label">en retard</span></div>
        </div>
    </div>

    @if ($lots->isEmpty())
        <x-front.empty-state icon="bi-clipboard2-pulse" title="Aucun lot en votre possession">
            Créez ou recevez un lot, puis revenez ici pour y ajouter une analyse qualité.
        </x-front.empty-state>
        <div class="text-center mt-3">
            <a href="{{ route('pro.lots.index') }}" class="btn btn-outline-primary"><i class="bi bi-upc-scan me-1" aria-hidden="true"></i>Mes lots</a>
            @can('pro-creer-lot')
                <a href="{{ route('pro.lots.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Créer un lot</a>
            @endcan
        </div>
    @else
        <div class="row g-3">
            @foreach ($lots as $lot)
                @php($s = $lot->synthese)
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">{{ $lot->produit?->nom }}</div>
                                <div class="small text-muted"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
                            </div>
                            <span @class([
                                'status-pill',
                                'status-valid' => ($s['statut'] ?? null) === 'conforme',
                                'status-expired' => ($s['statut'] ?? null) === 'non_conforme',
                                'status-pending' => ! in_array($s['statut'] ?? null, ['conforme', 'non_conforme'], true),
                            ])>{{ $s['libelle'] ?? $lot->analyses_count.' analyse(s)' }}</span>
                        </div>

                        @if (! empty($s['alertes']))
                            <ul class="small text-danger mb-2 ps-3">
                                @foreach ($s['alertes'] as $alerte)
                                    <li>{{ $alerte }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($lot->analyses->isNotEmpty())
                            <ul class="small text-muted mb-3 ps-3">
                                @foreach ($lot->analyses as $analyse)
                                    <li>
                                        {{ $analyse->typeLabel() }} · {{ $analyse->resultatLabel() }}
                                        @if ($analyse->resume_ia)
                                            <span class="text-success" title="Résumé IA disponible"><i class="bi bi-stars"></i></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="small text-muted mb-3">Pas encore d'analyse sur ce lot.</p>
                        @endif

                        @if (session('risque_ia') && (int) session('risque_lot_id') === (int) $lot->id)
                            @php($risque = session('risque_ia'))
                            <div class="notice-panel mb-3">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <div>
                                    <strong>Risque IA · {{ $risque['libelle'] }} ({{ $risque['score'] }}/100)</strong>
                                    <p class="mb-0 mt-1 small">{{ $risque['justification'] }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-sm btn-outline-secondary">Voir le lot</a>

                            <form method="POST" action="{{ route('pro.lots.ia.risque', $lot) }}">
                                @csrf
                                <input type="hidden" name="from_qualite" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Score de risque IA
                                </button>
                            </form>

                            @can('ajouterAnalyse', $lot)
                                <a href="{{ route('pro.analyses.create', $lot) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-stars me-1" aria-hidden="true"></i>Analyse + IA
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $lots->links() }}</div>
    @endif
@endsection
