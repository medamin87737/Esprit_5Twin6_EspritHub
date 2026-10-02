@extends('layouts.admin')

@section('title', 'Nouvel acteur')

@section('content')
    <x-admin.page-header title="Nouvel acteur" module="Module 2 · Acteurs de la chaîne"
                         subtitle="Enregistrez un intervenant de la chaîne d'approvisionnement."
                         :back="route('admin.acteurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche acteur"
                               :action="Route::has('admin.acteurs.store') ? route('admin.acteurs.store') : null"
                               :cancel="route('admin.acteurs.index')">
                @include('pages.admin.acteurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Type d'acteur" child="Acteur" :rules="[
                'Nom, adresse, pays' => 'obligatoires.',
                'Type' => 'doit exister dans la liste.',
                'E-mail' => 'adresse valide.',
                'Latitude' => 'facultative, entre -90 et 90.',
                'Longitude' => 'facultative, entre -180 et 180.',
            ]" />
        </div>
    </div>
@endsection
