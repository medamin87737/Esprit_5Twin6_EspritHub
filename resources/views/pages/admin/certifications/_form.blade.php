@php
    $certification = $certification ?? null;
    $organismes = $organismes ?? collect();
    $produits = $produits ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="produit_id" label="Produit certifié" :options="$produits->pluck('nom', 'id')"
                                 :value="$certification?->produit_id" placeholder="Choisir un produit…" required
                                 empty-message="Aucun produit disponible : le module 1 doit d'abord en créer." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="organisme_id" label="Organisme certificateur" :options="$organismes->pluck('nom', 'id')"
                                 :value="$certification?->organisme_id" placeholder="Choisir un organisme…" required
                                 empty-message="Aucun organisme disponible : créez-en un d'abord." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-award" aria-hidden="true"></i> Label</h3>
    <div class="form-row">
        <div class="col-md-4">
            <x-admin.form.select name="type" label="Type de label" :options="config('nutritrace.options.certification_types')"
                                 :value="$certification?->type" placeholder="Choisir…" required />
        </div>
        <div class="col-md-4">
            <x-admin.form.input name="numero" label="Numéro" :value="$certification?->numero" required placeholder="Ex. BIO-TN-2026-001" />
        </div>
        <div class="col-md-4">
            <x-admin.form.select name="statut" label="Statut" :options="config('nutritrace.options.certification_statuts')"
                                 :value="$certification?->statut" placeholder="Choisir…" required />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="date_obtention" type="date" label="Date d'obtention"
                                :value="$certification?->date_obtention?->format('Y-m-d')" required />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="date_expiration" type="date" label="Date d'expiration"
                                :value="$certification?->date_expiration?->format('Y-m-d')" required help="Postérieure à la date d'obtention." />
        </div>
    </div>
</div>
