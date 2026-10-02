@php
    $acteur = $acteur ?? null;
    $typeActeurs = $typeActeurs ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-person-badge" aria-hidden="true"></i> Identité</h3>
    <div class="form-row">
        <div class="col-md-7">
            <x-admin.form.input name="nom" label="Nom de l'acteur" :value="$acteur?->nom" required placeholder="Ex. Laiterie Délice" />
        </div>
        <div class="col-md-5">
            <x-admin.form.select name="type_acteur_id" label="Type d'acteur" :options="$typeActeurs->pluck('libelle', 'id')"
                                 :value="$acteur?->type_acteur_id" placeholder="Choisir un type…" required
                                 empty-message="Aucun type disponible : créez-en un d'abord." />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="email" type="email" label="E-mail" :value="$acteur?->email" required placeholder="contact@exemple.tn" />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="telephone" type="tel" label="Téléphone" :value="$acteur?->telephone" required placeholder="+216 …" />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="date_inscription" type="date" label="Date d'inscription"
                                :value="$acteur?->date_inscription?->format('Y-m-d')" required />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-geo-alt" aria-hidden="true"></i> Localisation</h3>
    <div class="form-row">
        <div class="col-md-8">
            <x-admin.form.input name="adresse" label="Adresse" :value="$acteur?->adresse" required placeholder="Rue, ville" />
        </div>
        <div class="col-md-4">
            <x-admin.form.input name="pays" label="Pays" :value="$acteur?->pays" required placeholder="Tunisie" />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="latitude" type="number" step="0.000001" min="-90" max="90" label="Latitude"
                                :value="$acteur?->latitude" placeholder="36.8065" help="Facultatif — entre -90 et 90." />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="longitude" type="number" step="0.000001" min="-180" max="180" label="Longitude"
                                :value="$acteur?->longitude" placeholder="10.1815" help="Facultatif — entre -180 et 180." />
        </div>
    </div>
</div>
