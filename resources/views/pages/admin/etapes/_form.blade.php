@php
    $etape = $etape ?? null;
    $lots = $lots ?? collect();
    $acteurs = $acteurs ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="lot_id" label="Lot" :options="$lots->pluck('numero_lot', 'id')"
                                 :value="$etape?->lot_id" placeholder="Choisir un lot…" required
                                 empty-message="Aucun lot disponible : créez-en un d'abord." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="acteur_id" label="Acteur" :options="$acteurs->pluck('nom', 'id')"
                                 :value="$etape?->acteur_id" placeholder="Choisir un acteur…" required
                                 empty-message="Aucun acteur disponible : le module 2 doit d'abord en créer." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-signpost-split" aria-hidden="true"></i> Déroulement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="type_etape" label="Type d'étape" :options="config('nutritrace.options.etape_types')"
                                 :value="$etape?->type_etape" placeholder="Choisir un type…" required />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="date_heure" type="datetime-local" label="Date et heure"
                                :value="$etape?->date_heure?->format('Y-m-d\TH:i')" required />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="lieu" label="Lieu" :value="$etape?->lieu" required placeholder="Ex. Usine de Ben Arous" />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="mode_transport" label="Mode de transport" :options="config('nutritrace.options.modes_transport')"
                                 :value="$etape?->mode_transport" placeholder="Choisir un mode…" required />
        </div>
    </div>
    <x-admin.form.textarea name="remarques" label="Remarques" :value="$etape?->remarques" rows="3"
                           placeholder="Température, conditions particulières…" help="Facultatif." />
</div>
