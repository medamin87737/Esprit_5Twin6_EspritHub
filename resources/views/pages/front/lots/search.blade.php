@extends('layouts.front')

@section('title', 'Tracer un lot')

@section('content')
    <x-front.page-header eyebrow="Traçabilité" title="Tracer un lot" image="distributeur-camion.webp"
                         subtitle="Scannez le code de l'emballage ou saisissez son numéro de lot pour découvrir tout son parcours." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="filter-bar">
                <x-front.recherche-lot :valeur="$numero" />
                @if ($exemples->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small text-muted">
                        <span>Exemples :</span>
                        @foreach ($exemples as $exemple)
                            <a href="{{ route('front.lots.search', ['numero' => $exemple]) }}" class="label-chip text-decoration-none">{{ $exemple }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($numero !== '')
                <x-front.empty-state icon="bi-question-circle" :title="'Aucun lot ne correspond à « ' . $numero . ' »'" class="mt-5">
                    Vérifiez le numéro imprimé sur l'emballage, près de la date de péremption, puis réessayez.
                </x-front.empty-state>
            @else
                <div class="row g-4 mt-4">
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-camera" aria-hidden="true"></i></span>
                            <h2 class="h6">Scannez le code</h2>
                            <p>Sur téléphone, appuyez sur « Scanner » et visez le code imprimé sur l'emballage.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-keyboard" aria-hidden="true"></i></span>
                            <h2 class="h6">Ou saisissez-le</h2>
                            <p>Recopiez le numéro tel quel, au format <strong>LOT-AAAA-NNNN</strong>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-step">
                            <span class="info-step-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                            <h2 class="h6">Suivez le parcours</h2>
                            <p>Production, transformation, distribution et vente s'affichent dans l'ordre, sur une carte.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
