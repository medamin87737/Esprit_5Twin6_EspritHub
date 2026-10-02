@php
    $lot = $lot ?? null;
    $produits = $produits ?? collect();
@endphp

<div class="form-row">
    <div class="col-md-6">
        <x-admin.form.input name="numero_lot" label="Numéro de lot" :value="$lot?->numero_lot" required
                            placeholder="LOT-2026-0001" help="Format conseillé : LOT-AAAA-NNNN." />
    </div>
    <div class="col-md-6">
        <x-admin.form.select name="produit_id" label="Produit" :options="$produits->pluck('nom', 'id')"
                             :value="$lot?->produit_id" placeholder="Choisir un produit…" required
                             empty-message="Aucun produit disponible : le module 1 doit d'abord en créer." />
    </div>
</div>

<div class="form-row">
    <div class="col-md-4">
        <x-admin.form.input name="quantite" type="number" min="1" step="1" label="Quantité" :value="$lot?->quantite" required
                            placeholder="Ex. 500" help="Nombre d'unités produites." />
    </div>
    <div class="col-md-4">
        <x-admin.form.input name="date_production" type="date" label="Date de production"
                            :value="$lot?->date_production?->format('Y-m-d')" required />
    </div>
    <div class="col-md-4">
        <x-admin.form.input name="date_peremption" type="date" label="Date de péremption"
                            :value="$lot?->date_peremption?->format('Y-m-d')" required help="Postérieure à la production." />
    </div>
</div>
