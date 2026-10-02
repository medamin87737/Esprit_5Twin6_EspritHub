@extends('layouts.admin')

@section('title', 'Modifier ' . $produit->nom)

@section('content')
    <x-admin.page-header :title="$produit->nom" module="Module 1 · Modifier le produit"
                         subtitle="Mettez à jour la fiche de ce produit."
                         :back="route('admin.produits.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche produit" files method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.produits.update', $produit)"
                               :cancel="route('admin.produits.index')">
                @include('pages.admin.produits._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @if ($produit->image)
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="nt-help-title"><i class="bi bi-image" aria-hidden="true"></i> Image actuelle</h2>
                        <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" class="img-fluid rounded">
                        <small class="form-text">Choisissez un nouveau fichier pour la remplacer.</small>
                    </div>
                </div>
            @endif
            <x-admin.help-card parent="Catégorie" child="Produit" :rules="[
                'Nom' => 'obligatoire, 150 caractères max.',
                'Code-barres' => 'exactement 13 chiffres, unique.',
                'Catégorie' => 'doit exister dans la liste.',
                'Origine' => 'obligatoire.',
                'Image' => 'facultative, image de 2 Mo max.',
            ]" />
        </div>
    </div>
@endsection
