@extends('layouts.pro')

@section('title', 'Ajouter un indicateur')

@section('pro_content')
    @php($empreinte = $etape->lot->empreinteCarbone)

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <a href="{{ route('pro.lots.show', $etape->lot) }}" class="small d-inline-block mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Lot {{ $etape->lot->numero_lot }}</a>

            <form method="POST" action="{{ route('pro.indicateurs.store', $etape) }}" class="account-card" novalidate>
                @csrf
                <h2 class="account-card-title"><i class="bi bi-speedometer2" aria-hidden="true"></i> Étape {{ $etape->typeLabel() }} du {{ $etape->date_heure?->format('d/m/Y') }}</h2>

                <div class="row gx-3" data-unites='@json(\App\Models\Indicateur::UNITES)'>
                    <x-front.champ class="col-md-5" name="type" label="Type" type="select" required data-type-indicateur
                                   :options="config('nutritrace.options.indicateur_types')" placeholder="Choisir…" />
                    <x-front.champ class="col-md-4" name="valeur" label="Valeur" type="number" required min="0.01" step="0.01" />
                    <x-front.champ class="col-md-3" name="unite" label="Unité" type="select" required data-unite-indicateur
                                   :options="config('nutritrace.options.unites')" placeholder="—" />
                </div>
                <p class="small text-muted mb-0">Valeur totale pour le lot : eau en litres, énergie en kWh, distance de transport en km, emballage en kg.</p>

                <div class="form-actions">
                    <a href="{{ route('pro.lots.show', $etape->lot) }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer et recalculer le score</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="panel mb-4">
                <h2 class="panel-title"><i class="bi bi-globe-europe-africa" aria-hidden="true"></i>Score actuel du lot</h2>
                <x-front.score-badge :score="$empreinte?->score" :co2="$empreinte?->co2_total" taille="lg" />
                <p class="small text-muted mt-3 mb-0">Chaque indicateur ajoute sa part de CO₂e, rapportée aux {{ number_format($etape->lot->quantite, 0, ',', ' ') }} unités du lot.</p>
            </div>
            <div class="panel">
                <h2 class="panel-title"><i class="bi bi-list-check" aria-hidden="true"></i>Déjà saisis sur cette étape</h2>
                @forelse ($etape->indicateurs as $indicateur)
                    <span class="indicateur-chip mb-2"><i class="bi {{ $indicateur->icone() }}" aria-hidden="true"></i>{{ $indicateur->typeLabel() }} : <strong>{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</strong></span>
                @empty
                    <p class="text-muted mb-0">Aucun indicateur pour l'instant.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
