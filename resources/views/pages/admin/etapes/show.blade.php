@extends('layouts.admin')

@section('title', $etape->typeLabel())

@section('content')
    <x-admin.page-header :title="$etape->typeLabel() . ' · ' . $etape->lot?->numero_lot" module="Module 3 · Étape"
                         :subtitle="$etape->lieu . ' — ' . $etape->date_heure?->format('d/m/Y H:i')"
                         :back="route('admin.etapes.index')">
        <x-slot:actions>
            <a href="{{ route('admin.etapes.edit', $etape) }}" class="btn btn-primary">
                <i class="bi bi-pencil mr-1" aria-hidden="true"></i> Modifier
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="nt-card-title">Déroulement</h2></div>
                <div class="card-body">
                    <div class="nt-role-row"><span class="text-muted">Type</span><span class="nt-badge">{{ $etape->typeLabel() }}</span></div>
                    <div class="nt-role-row"><span class="text-muted">Date et heure</span><strong>{{ $etape->date_heure?->format('d/m/Y H:i') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Lieu</span><strong>{{ $etape->lieu }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Transport</span><strong>{{ $etape->transportLabel() }}</strong></div>
                    <p class="mt-3 mb-0 {{ $etape->remarques ? '' : 'text-muted' }}">{{ $etape->remarques ?: 'Aucune remarque.' }}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="nt-card-title">Lot</h2></div>
                <div class="card-body">
                    @if ($etape->lot)
                        <div class="nt-role-row">
                            <span class="text-muted">Numéro</span>
                            <a href="{{ route('admin.lots.show', $etape->lot) }}" class="nt-badge">{{ $etape->lot->numero_lot }}</a>
                        </div>
                        <div class="nt-role-row">
                            <span class="text-muted">Produit</span>
                            @if ($etape->lot->produit)
                                <a href="{{ route('admin.produits.show', $etape->lot->produit) }}">{{ $etape->lot->produit->nom }}</a>
                            @endif
                        </div>
                        <div class="nt-role-row"><span class="text-muted">Production</span><strong>{{ $etape->lot->date_production?->format('d/m/Y') }}</strong></div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="nt-card-title">Acteur</h2></div>
                <div class="card-body">
                    @if ($etape->acteur)
                        <div class="nt-role-row">
                            <span class="text-muted">Nom</span>
                            <a href="{{ route('admin.acteurs.show', $etape->acteur) }}" class="nt-badge">{{ $etape->acteur->nom }}</a>
                        </div>
                        <div class="nt-role-row"><span class="text-muted">Type</span><strong>{{ $etape->acteur->typeActeur?->libelle }}</strong></div>
                        <div class="nt-role-row"><span class="text-muted">Pays</span><strong>{{ $etape->acteur->pays }}</strong></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
