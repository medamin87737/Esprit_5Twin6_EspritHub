@php($categorie = $categorie ?? null)

<div class="form-row">
    <div class="col-md-7">
        <x-admin.form.input name="nom" label="Nom de la catégorie" :value="$categorie?->nom" required
                            placeholder="Ex. Produits laitiers" maxlength="100" />
    </div>
    <div class="col-md-5">
        <x-admin.form.select name="type" label="Type" :options="config('nutritrace.options.categorie_types')"
                             :value="$categorie?->type" placeholder="Choisir un type…" required />
    </div>
</div>

<x-admin.form.textarea name="description" label="Description" :value="$categorie?->description" rows="4"
                       placeholder="Quels produits regroupe cette catégorie ?" help="Facultatif — 1 000 caractères maximum." />
