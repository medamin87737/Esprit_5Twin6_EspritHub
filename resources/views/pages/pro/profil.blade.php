@extends('layouts.pro')

@section('title', 'Profil société')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            @unless ($acteur->exists)
                <div class="notice-panel mb-4">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <div>Votre fiche acteur sera publiée dans l'annuaire. Elle est nécessaire pour recevoir des lots et saisir vos étapes.</div>
                </div>
            @endunless

            <form method="POST" action="{{ route('pro.profil.update') }}" class="account-card" novalidate>
                @csrf
                @method('PUT')
                <h2 class="account-card-title"><i class="bi bi-building" aria-hidden="true"></i> Informations de la société</h2>

                <div class="row gx-3">
                    <x-front.champ class="col-md-7" name="nom" label="Nom de la société" required :value="$acteur->nom" maxlength="150" />
                    <x-front.champ class="col-md-5" name="telephone" label="Téléphone" type="tel" required :value="$acteur->telephone" maxlength="30" placeholder="+216 71 000 000" />
                    <x-front.champ class="col-md-6" name="email" label="E-mail de contact" type="email" required :value="$acteur->email" />
                    <x-front.champ class="col-md-6" name="pays" label="Pays" required :value="$acteur->pays ?? 'Tunisie'" maxlength="80" />
                    <x-front.champ class="col-12" name="adresse" label="Adresse" required :value="$acteur->adresse" maxlength="255" />
                    <x-front.champ class="col-md-6" name="latitude" label="Latitude" type="number" step="0.000001" min="-90" max="90" :value="$acteur->latitude"
                                   help="Facultatif : position sur la carte de l'annuaire et des parcours." />
                    <x-front.champ class="col-md-6" name="longitude" label="Longitude" type="number" step="0.000001" min="-180" max="180" :value="$acteur->longitude" />
                </div>

                <div class="form-actions">
                    <a href="{{ route('pro.dashboard') }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection
