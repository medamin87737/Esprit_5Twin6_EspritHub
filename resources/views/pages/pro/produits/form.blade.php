@extends('layouts.pro')

@php($edition = $produit->exists)

@section('title', $edition ? 'Modifier ' . $produit->nom : 'Nouveau produit')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <form method="POST" enctype="multipart/form-data" class="account-card" novalidate
                  action="{{ $edition ? route('pro.produits.update', $produit) : route('pro.produits.store') }}">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif

                <h2 class="account-card-title"><i class="bi bi-box-seam" aria-hidden="true"></i> Fiche produit</h2>

                <div class="row gx-3">
                    <x-front.champ class="col-md-7" name="nom" label="Nom du produit" required :value="$produit->nom" maxlength="150" placeholder="Ex. Tomates de plein champ" />
                    <x-front.champ class="col-md-5" name="code_barres" label="Code-barres (EAN-13)" required :value="$produit->code_barres"
                                   inputmode="numeric" maxlength="13" placeholder="13 chiffres" />
                    <x-front.champ class="col-md-6" name="categorie_id" label="Catégorie" type="select" required :value="$produit->categorie_id"
                                   :options="$categories" placeholder="Choisir une catégorie…" />
                    <x-front.champ class="col-md-6" name="origine" label="Origine" required :value="$produit->origine" maxlength="120" placeholder="Ex. Béja, Tunisie" />
                </div>

                <x-front.champ name="description" label="Description" type="textarea" rows="3" :value="$produit->description" maxlength="2000" />
                <x-front.champ name="composition" label="Composition" type="textarea" rows="3" :value="$produit->composition" maxlength="2000" placeholder="Liste des ingrédients" />
                <x-front.champ name="image" label="Image" type="file" accept="image/*"
                               :help="$edition && $produit->image ? 'Une image est déjà enregistrée : choisissez un fichier pour la remplacer. JPG, PNG ou WebP, 2 Mo max.' : 'Facultative : JPG, PNG ou WebP, 2 Mo max.'" />

                <div class="form-actions">
                    <a href="{{ route('pro.produits.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">{{ $edition ? 'Enregistrer les modifications' : 'Ajouter le produit' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
