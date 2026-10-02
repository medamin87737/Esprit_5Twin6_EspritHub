@extends('layouts.admin')

@section('title', 'Nouveau produit')

@section('content')
    <x-admin.page-header title="Nouveau produit" module="Module 1 · Produits & Catégories"
                         subtitle="Référencez un produit et rattachez-le à sa catégorie."
                         :back="route('admin.produits.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche produit" files
                               :action="Route::has('admin.produits.store') ? route('admin.produits.store') : null"
                               :cancel="route('admin.produits.index')">
                @include('pages.admin.produits._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
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
