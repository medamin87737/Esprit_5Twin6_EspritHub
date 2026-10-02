@extends('layouts.admin')

@section('title', 'Nouvelle catégorie')

@section('content')
    <x-admin.page-header title="Nouvelle catégorie" module="Module 1 · Produits & Catégories"
                         subtitle="Ajoutez une famille de produits au catalogue."
                         :back="route('admin.categories.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de la catégorie"
                               :action="Route::has('admin.categories.store') ? route('admin.categories.store') : null"
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
