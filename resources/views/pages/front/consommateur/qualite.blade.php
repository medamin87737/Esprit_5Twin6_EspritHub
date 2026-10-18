@extends('layouts.front')

@section('title', 'Qualité')

@section('content')
    <x-front.page-header eyebrow="Mon espace" title="Qualité" image="consommateur-bowl.webp"
                         subtitle="Consultez les analyses d'un lot et obtenez un résumé IA du rapport (TXT / DOCX)." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="account-card">
                        <h2 class="account-card-title"><i class="bi bi-upc-scan" aria-hidden="true"></i> Trouver un lot</h2>
                        <form method="GET" action="{{ route('consommateur.qualite.index') }}" class="mb-0">
                            <x-front.champ name="numero" label="Numéro de lot" required placeholder="LOT-2026-0001"
                                           :value="$numero" help="Saisissez le numéro figurant sur le produit." />
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1" aria-hidden="true"></i>Voir les analyses</button>
                        </form>
                    </div>

                    <div class="account-card mt-4">
                        <h2 class="account-card-title"><i class="bi bi-stars" aria-hidden="true"></i> Résumer un rapport</h2>
                        <p class="small text-muted">Uploadez un rapport TXT ou DOCX : l'IA en fait une synthèse qualité.</p>
                        <form method="POST" action="{{ route('consommateur.qualite.resumer-fichier') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="numero_lot" value="{{ $numero }}">
                            <x-front.dropzone-rapport name="rapport" label="Rapport (TXT / DOCX)" />
                            <button type="submit" class="btn btn-success w-100 mt-2">
                                <i class="bi bi-stars me-1" aria-hidden="true"></i>Résumer avec l'IA
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    @if ($introuvable)
                        <x-front.empty-state icon="bi-exclamation-circle" title="Lot introuvable">
                            Aucun lot ne correspond à « {{ $numero }} ». Vérifiez le numéro puis réessayez.
                        </x-front.empty-state>
                    @elseif ($lot)
                        <div class="panel mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <span class="eyebrow">{{ $lot->produit?->categorie?->nom }}</span>
                                    <h2 class="h4 mb-1">{{ $lot->produit?->nom }}</h2>
                                    <div class="text-muted small"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
                                </div>
                                <a href="{{ route('front.lots.show', $lot) }}" class="btn btn-sm btn-outline-primary">Parcours du lot</a>
                            </div>
                        </div>

                        @if ($lot->analyses->isEmpty())
                            <x-front.empty-state icon="bi-clipboard2" title="Aucune analyse">
                                Ce lot n'a pas encore d'analyse laboratoire déclarée.
                            </x-front.empty-state>
                        @else
                            <div class="vstack gap-3">
                                @foreach ($lot->analyses as $analyse)
                                    <div class="panel">
                                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                            <div>
                                                <strong><i class="bi {{ $analyse->icone() }} me-1 text-success" aria-hidden="true"></i>{{ $analyse->typeLabel() }}</strong>
                                                <div class="small text-muted">
                                                    {{ $analyse->laboratoire?->nom ?? 'Laboratoire' }}
                                                    · prélevé le {{ $analyse->date_prelevement?->format('d/m/Y') ?? '—' }}
                                                </div>
                                            </div>
                                            <span class="status-pill {{ $analyse->resultatClasse() }}">{{ $analyse->resultatLabel() }}</span>
                                        </div>

                                        @if ($analyse->commentaire)
                                            <p class="small mb-2">{{ $analyse->commentaire }}</p>
                                        @endif

                                        @if ($analyse->resume_ia || (session('analyse_resumee_id') == $analyse->id && session('resume_ia_affiche')))
                                            <div class="notice-panel mb-2">
                                                <i class="bi bi-stars" aria-hidden="true"></i>
                                                <div>
                                                    <strong>Résumé IA</strong>
                                                    <p class="mb-0 mt-1">{{ session('analyse_resumee_id') == $analyse->id ? session('resume_ia_affiche') : $analyse->resume_ia }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="d-flex flex-wrap gap-2">
                                            @can('telechargerRapport', $analyse)
                                                <a href="{{ route('front.analyses.rapport', $analyse) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-download me-1" aria-hidden="true"></i>Télécharger le rapport
                                                </a>
                                            @endcan
                                            <form method="POST" action="{{ route('consommateur.qualite.resumer', $analyse) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-stars me-1" aria-hidden="true"></i>
                                                    {{ $analyse->resume_ia ? 'Régénérer le résumé IA' : 'Résumer avec l\'IA' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <x-front.empty-state icon="bi-clipboard2-pulse" title="Qualité NutriTrace">
                            Entrez un numéro de lot pour voir ses analyses, ou uploadez un rapport TXT/DOCX pour un résumé IA immédiat.
                        </x-front.empty-state>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/assets/admin/js/analyse-ia.js')
@endpush
