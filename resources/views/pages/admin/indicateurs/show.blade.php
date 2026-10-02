@extends('layouts.admin')

@section('title', $indicateur->typeLabel())

@section('content')
    @php($empreinte = $indicateur->empreinteCarbone)

    <x-admin.page-header :title="$indicateur->typeLabel() . ' · ' . number_format($indicateur->valeur, 2, ',', ' ') . ' ' . $indicateur->unite"
                         module="Module 4 · Indicateur" :subtitle="$empreinte?->lot?->produit?->nom"
                         :back="route('admin.indicateurs.index')">
        <x-slot:actions>
            <a href="{{ route('admin.indicateurs.edit', $indicateur) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="nt-card-title">Mesure</h2></div>
                <div class="card-body">
                    <div class="nt-role-row"><span class="text-muted">Type</span><span class="nt-badge"><i class="bi {{ $indicateur->icone() }}" aria-hidden="true"></i> {{ $indicateur->typeLabel() }}</span></div>
                    <div class="nt-role-row"><span class="text-muted">Valeur</span><strong>{{ number_format($indicateur->valeur, 2, ',', ' ') }} {{ $indicateur->unite }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Saisi le</span><strong>{{ $indicateur->created_at?->format('d/m/Y') }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="nt-card-title">Empreinte carbone</h2></div>
                <div class="card-body">
                    @if ($empreinte)
                        <div class="nt-role-row">
                            <span class="text-muted">Score</span>
                            <a href="{{ route('admin.empreintes.show', $empreinte) }}" class="nt-score nt-score-{{ strtolower($empreinte->score) }}">{{ $empreinte->score }}</a>
                        </div>
                        <div class="nt-role-row"><span class="text-muted">CO₂ total</span><strong>{{ number_format($empreinte->co2_total, 2, ',', ' ') }} kg</strong></div>
                        <div class="nt-role-row">
                            <span class="text-muted">Lot</span>
                            @if ($empreinte->lot)
                                <a href="{{ route('admin.lots.show', $empreinte->lot) }}" class="nt-badge">{{ $empreinte->lot->numero_lot }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="nt-card-title">Étape mesurée</h2></div>
                <div class="card-body">
                    @if ($indicateur->etape)
                        <div class="nt-role-row">
                            <span class="text-muted">Étape</span>
                            <a href="{{ route('admin.etapes.show', $indicateur->etape) }}" class="nt-badge">{{ $indicateur->etape->typeLabel() }}</a>
                        </div>
                        <div class="nt-role-row"><span class="text-muted">Lieu</span><strong>{{ $indicateur->etape->lieu }}</strong></div>
                        <div class="nt-role-row"><span class="text-muted">Acteur</span><strong>{{ $indicateur->etape->acteur?->nom }}</strong></div>
                    @else
                        <p class="text-muted mb-0">Mesure globale du lot, sans étape précise.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
