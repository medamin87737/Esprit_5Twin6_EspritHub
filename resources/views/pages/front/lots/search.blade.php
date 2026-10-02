@extends('layouts.front')

@section('title', 'Tracer un lot')

@section('content')
    @php
        $lot = $lot ?? null;
        $numero = trim((string) request('numero'));
        $types = config('nutritrace.options.etape_types');
        $transports = config('nutritrace.options.modes_transport');
        $icones = ['production' => 'bi-flower3', 'transformation' => 'bi-gear-wide-connected', 'distribution' => 'bi-truck', 'vente' => 'bi-shop'];
    @endphp

    <x-front.page-header eyebrow="Traçabilité" title="Tracer un lot" image="distributeur-camion.webp"
                         subtitle="Saisissez le numéro imprimé sur l'emballage pour découvrir chaque étape du parcours de votre produit." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <form class="filter-bar search-hero" method="GET" action="{{ route('front.lots.search') }}" role="search">
                <label class="form-label" for="numero">Numéro de lot</label>
                <div class="search-hero-row">
                    <div class="field-icon flex-grow-1">
                        <i class="bi bi-upc-scan" aria-hidden="true"></i>
                        <input class="form-control form-control-lg" id="numero" type="search" name="numero" value="{{ $numero }}"
                               placeholder="Ex. LOT-2026-0001" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-search me-2" aria-hidden="true"></i>Tracer</button>
                </div>
            </form>

            @if ($lot)
                <div class="lot-summary">
                    <div>
                        <span class="eyebrow mb-1">Lot trouvé</span>
                        <h2 class="h3 mb-1">{{ $lot->produit?->nom }}</h2>
                        <div class="text-muted"><i class="bi bi-upc me-1" aria-hidden="true"></i>{{ $lot->numero_lot }}</div>
                    </div>
                    <dl class="lot-facts">
                        <div><dt>Quantité</dt><dd>{{ number_format($lot->quantite, 0, ',', ' ') }} unités</dd></div>
                        <div><dt>Production</dt><dd>{{ $lot->date_production?->format('d/m/Y') }}</dd></div>
                        <div><dt>Péremption</dt><dd>{{ $lot->date_peremption?->format('d/m/Y') }}</dd></div>
                    </dl>
                </div>

                @if ($lot->etapes->isEmpty())
                    <x-front.empty-state icon="bi-hourglass-split" title="Parcours en cours de saisie">
                        Aucune étape n'a encore été enregistrée pour ce lot.
                    </x-front.empty-state>
                @else
                    <ol class="timeline mt-5">
                        @foreach ($lot->etapes->sortBy('date_heure') as $etape)
                            <x-front.timeline-step :number="$loop->iteration" :icon="$icones[$etape->type_etape] ?? 'bi-geo-alt'"
                                                   :title="$types[$etape->type_etape] ?? ucfirst($etape->type_etape)"
                                                   :status="$etape->date_heure?->format('d/m/Y H:i')"
                                                   :meta="$etape->lieu">
                                {{ $etape->acteur?->nom }}@if ($etape->mode_transport && $etape->mode_transport !== 'aucun') · {{ $transports[$etape->mode_transport] ?? $etape->mode_transport }}@endif
                            </x-front.timeline-step>
                        @endforeach
                    </ol>
                @endif
            @elseif ($numero !== '')
                <x-front.empty-state icon="bi-question-circle" title="Aucun lot ne correspond à « {{ $numero }} »" class="mt-5">
                    Vérifiez le numéro imprimé sur l'emballage, près de la date de péremption, puis réessayez.
                </x-front.empty-state>
            @else
                <div class="row g-4 mt-4">
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                            <h2 class="h6">Repérez le numéro</h2>
                            <p>Il est imprimé sur l'emballage, généralement à côté de la date limite de consommation.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-keyboard" aria-hidden="true"></i></span>
                            <h2 class="h6">Saisissez-le</h2>
                            <p>Recopiez-le tel quel dans le champ ci-dessus, au format <strong>LOT-AAAA-NNNN</strong>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                            <h2 class="h6">Suivez le parcours</h2>
                            <p>Production, transformation, distribution et vente s'affichent dans l'ordre chronologique.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
