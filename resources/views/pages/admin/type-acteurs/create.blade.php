@extends('layouts.admin')

@section('title', 'Nouveau type d\'acteur')

@section('content')
    <x-admin.page-header title="Nouveau type d'acteur" module="Administration · Acteurs de la chaîne"
                         subtitle="Ajoutez un rôle de la chaîne d'approvisionnement."
                         :back="route('admin.type-acteurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du type"
                               :action="Route::has('admin.type-acteurs.store') ? route('admin.type-acteurs.store') : null"
                               :cancel="route('admin.type-acteurs.index')">
                @include('pages.admin.type-acteurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Type d'acteur" child="Acteurs" :rules="[
                'Libellé' => 'obligatoire et unique.',
                'Rôle dans la chaîne' => 'obligatoire, 255 caractères max.',
                'Description' => 'facultative.',
            ]" />
        </div>
    </div>
@endsection
