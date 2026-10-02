@extends('layouts.admin')

@section('title', 'Modifier ' . $typeActeur->libelle)

@section('content')
    <x-admin.page-header :title="$typeActeur->libelle" module="Module 2 · Modifier le type d'acteur"
                         subtitle="Mettez à jour ce rôle de la chaîne d'approvisionnement."
                         :back="route('admin.type-acteurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du type" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.type-acteurs.update', $typeActeur)"
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
