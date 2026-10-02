@extends('layouts.admin')

@section('title', 'Nouvelle étape')

@section('content')
    <x-admin.page-header title="Nouvelle étape" module="Module 3 · Traçabilité des lots"
                         subtitle="Ajoutez un passage du lot chez un acteur de la chaîne."
                         :back="route('admin.etapes.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Détail de l'étape"
                               :action="Route::has('admin.etapes.store') ? route('admin.etapes.store') : null"
                               :cancel="route('admin.etapes.index')">
                @include('pages.admin.etapes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Lot" child="Étape" :rules="[
                'Lot et acteur' => 'doivent exister.',
                'Type d\'étape' => 'production, transformation, distribution ou vente.',
                'Date et heure' => 'obligatoire.',
                'Transport' => 'une valeur de la liste.',
                'Remarques' => 'facultatives.',
            ]" />
        </div>
    </div>
@endsection
