@extends('layouts.pro')

@php($edition = $analyse->exists)

@section('title', $edition ? 'Modifier l\'analyse' : 'Déclarer une analyse')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('pro.lots.show', $lot) }}" class="small d-inline-block mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Lot {{ $lot->numero_lot }}</a>

            <form method="POST" class="account-card" enctype="multipart/form-data" novalidate
                  action="{{ $edition ? route('pro.analyses.update', $analyse) : route('pro.analyses.store', $lot) }}">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif

                <h2 class="account-card-title"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> {{ $lot->produit?->nom }} · {{ $lot->numero_lot }}</h2>

                @if ($laboratoires === [])
                    <div class="notice-panel mb-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <div>Aucun laboratoire n'est encore référencé : contactez l'administrateur NutriTrace.</div>
                    </div>
                @endif

                <div class="row gx-3">
                    <x-front.champ class="col-md-7" name="laboratoire_id" label="Laboratoire" type="select" required
                                   :options="$laboratoires" :value="$analyse->laboratoire_id" placeholder="Choisir le laboratoire…" />
                    <x-front.champ class="col-md-5" name="numero" label="Numéro d'analyse" required :value="$analyse->numero"
                                   maxlength="20" help="Format ANA-AAAA-NNNN." />
                    <x-front.champ class="col-md-7" name="type" label="Type d'analyse" type="select" required
                                   :options="config('nutritrace.options.analyse_types')" :value="$analyse->type" />
                    <x-front.champ class="col-md-5" name="date_prelevement" label="Date de prélèvement" type="date" required
                                   :value="$analyse->date_prelevement?->format('Y-m-d')"
                                   :min="$lot->date_production?->format('Y-m-d')" :max="today()->format('Y-m-d')" />
                    <x-front.champ class="col-md-7" name="resultat" label="Résultat" type="select" required
                                   :options="config('nutritrace.options.analyse_resultats')" :value="$analyse->resultat ?? 'en_attente'"
                                   help="Laissez « En attente » tant que le laboratoire n'a pas rendu son résultat." />
                    <x-front.champ class="col-md-5" name="date_resultat" label="Date du résultat" type="date"
                                   :value="$analyse->date_resultat?->format('Y-m-d')" :max="today()->format('Y-m-d')" />
                </div>
                <x-front.champ name="commentaire" label="Commentaire du laboratoire" type="textarea" rows="3" :value="$analyse->commentaire"
                               maxlength="1000" placeholder="Ex. Salmonella non détectée dans 25 g" help="Obligatoire en cas de non-conformité." />
                <x-front.champ name="rapport" label="Rapport PDF" type="file" accept="application/pdf"
                               :help="$analyse->rapport ? 'Un rapport est déjà joint : un nouveau fichier le remplacera.' : 'Facultatif · PDF, 5 Mo maximum. Téléchargeable par les membres connectés.'" />

                <div class="form-actions">
                    <a href="{{ route('pro.lots.show', $lot) }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">{{ $edition ? 'Enregistrer' : 'Déclarer l\'analyse' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
