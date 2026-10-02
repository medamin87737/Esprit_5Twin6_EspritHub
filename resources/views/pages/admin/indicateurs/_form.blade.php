@php
    $indicateur = $indicateur ?? null;
    $empreintes = $empreintes ?? collect();
    $etapes = $etapes ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="empreinte_carbone_id" label="Empreinte carbone"
                                 :options="$empreintes->mapWithKeys(fn ($e) => [$e->id => ($e->lot?->numero_lot ?? 'Empreinte #' . $e->id) . ' — ' . $e->co2_total . ' kg'])"
                                 :value="$indicateur?->empreinte_carbone_id" placeholder="Choisir une empreinte…" required
                                 empty-message="Aucune empreinte disponible : créez-en une d'abord." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="etape_id" label="Étape liée"
                                 :options="$etapes->mapWithKeys(fn ($e) => [$e->id => ($e->lot?->numero_lot ?? '') . ' · ' . ucfirst($e->type_etape) . ' · ' . $e->lieu])"
                                 :value="$indicateur?->etape_id" placeholder="Aucune étape" help="Facultatif." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-speedometer2" aria-hidden="true"></i> Mesure</h3>
    <div class="form-row">
        <div class="col-md-4">
            <x-admin.form.select name="type" label="Type" :options="config('nutritrace.options.indicateur_types')"
                                 :value="$indicateur?->type" placeholder="Choisir…" required />
        </div>
        <div class="col-md-4">
            <x-admin.form.input name="valeur" type="number" min="0" step="0.01" label="Valeur" :value="$indicateur?->valeur" required placeholder="Ex. 12.5" />
        </div>
        <div class="col-md-4">
            <x-admin.form.select name="unite" label="Unité" :options="config('nutritrace.options.unites')"
                                 :value="$indicateur?->unite" placeholder="Choisir…" required />
        </div>
    </div>
</div>
