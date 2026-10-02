@extends('layouts.admin')

@section('title', 'Modifier ' . $categorie->nom)

@section('content')
    <x-admin.page-header :title="$categorie->nom" module="Module 1 · Modifier la catégorie"
                         subtitle="Mettez à jour les informations de cette famille de produits."
                         :back="route('admin.categories.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de la catégorie" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.categories.update', $categorie)"
                               :cancel="route('admin.categories.index')">
                @include('pages.admin.categories._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Catégorie" child="Produits" :rules="[
                'Nom' => 'obligatoire, unique, 100 caractères max.',
                'Type' => 'une valeur de la liste proposée.',
                'Description' => 'facultative.',
            ]" />
        </div>
    </div>
@endsection
