@php
    $produit = $produit ?? null;
    $categories = $categories ?? collect();
    $fournisseurs = $fournisseurs ?? collect();
    $espace = $espace ?? 'admin';
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-info-circle" aria-hidden="true"></i> Identification</h3>
    <div class="form-row">
        <div class="col-md-7">
            <x-admin.form.input name="nom" label="Nom du produit" :value="$produit?->nom" required placeholder="Ex. Yaourt nature bio 125 g" />
        </div>
        <div class="col-md-5">
            <x-admin.form.input name="code_barres" label="Code-barres (EAN-13)" :value="$produit?->code_barres" required
                                inputmode="numeric" maxlength="13" placeholder="13 chiffres" />
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="categorie_id" label="Catégorie" :options="$categories->pluck('nom', 'id')"
                                 :value="$produit?->categorie_id" placeholder="Choisir une catégorie…" required
                                 empty-message="Aucune catégorie disponible : créez-en une d'abord." />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="origine" label="Origine" :value="$produit?->origine" required placeholder="Ex. Béja, Tunisie" />
        </div>
    </div>
    @if ($espace === 'admin')
        <x-admin.form.select name="fournisseur_id" label="Fournisseur" :options="$fournisseurs->pluck('name', 'id')"
                             :value="$produit?->fournisseur_id" placeholder="Aucun (produit géré par l'administration)"
                             help="Le fournisseur choisi pourra modifier et supprimer ce produit depuis son espace." />
    @else
        <p class="small text-muted mb-0">
            <i class="bi bi-person-badge mr-1" aria-hidden="true"></i>
            Ce produit sera rattaché à votre compte fournisseur : <strong>{{ auth()->user()->name }}</strong>.
        </p>
    @endif
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-card-text" aria-hidden="true"></i> Description</h3>
    <x-admin.form.textarea name="description" label="Description" :value="$produit?->description" rows="3"
                           placeholder="Présentation courte du produit" />
    <x-admin.form.textarea name="composition" label="Composition" :value="$produit?->composition" rows="3"
                           placeholder="Liste des ingrédients" />
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-image" aria-hidden="true"></i> Visuel</h3>
    <x-admin.form.file name="image" label="Image du produit" accept="image/*" help="Facultatif — JPG, PNG ou WebP, 2 Mo maximum." />
</div>
