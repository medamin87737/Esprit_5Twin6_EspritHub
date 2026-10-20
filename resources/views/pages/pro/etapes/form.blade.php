@extends('layouts.pro')

@php($edition = $etape->exists)

@section('title', $edition ? 'Modifier mon étape' : 'Ajouter mon étape')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('pro.lots.show', $lot) }}" class="small d-inline-block mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Lot {{ $lot->numero_lot }}</a>

            <form method="POST" class="account-card" novalidate
                  action="{{ $edition ? route('pro.etapes.update', $etape) : route('pro.etapes.store', $lot) }}">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif

                <h2 class="account-card-title"><i class="bi bi-signpost-split" aria-hidden="true"></i> {{ $lot->produit?->nom }} · {{ $lot->numero_lot }}</h2>

                <div class="row gx-3">
                    <x-front.champ class="col-md-6" name="type_etape" label="Type d'étape" type="select" required :options="$types"
                                   :value="$etape->type_etape ?? (count($types) === 1 ? array_key_first($types) : null)"
                                   help="Limité aux étapes autorisées pour votre rôle." />
                    <x-front.champ class="col-md-6" name="date_heure" label="Date et heure" type="datetime-local" required
                                   :value="$etape->date_heure?->format('Y-m-d\TH:i')" />
                    <x-front.champ class="col-md-7" name="lieu" label="Lieu" required :value="$etape->lieu" maxlength="150" />
                    <x-front.champ class="col-md-5" name="mode_transport" label="Mode de transport" type="select" required
                                   :value="$etape->mode_transport" :options="config('nutritrace.options.modes_transport')" />
                </div>
                <x-front.champ name="remarques" label="Remarques" type="textarea" rows="3" :value="$etape->remarques" maxlength="1000"
                               placeholder="Conditions de stockage, contrôle qualité…" />

                <div class="form-actions">
                    <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">{{ $edition ? 'Enregistrer' : 'Ajouter l\'étape' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
