@extends('layouts.admin')

@section('title', 'Nouvel indicateur')

@section('content')
    <x-admin.page-header title="Nouvel indicateur" module="Module 4 · Empreinte environnementale"
                         subtitle="Ajoutez une mesure d'impact à une empreinte carbone."
                         :back="route('admin.indicateurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Mesure d'impact"
                               :action="Route::has('admin.indicateurs.store') ? route('admin.indicateurs.store') : null"
                               :cancel="route('admin.indicateurs.index')">
                @include('pages.admin.indicateurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Empreinte" child="Indicateur" :rules="[
                'Empreinte' => 'doit exister.',
                'Étape' => 'facultative.',
                'Type' => 'eau, énergie, transport ou emballage.',
                'Valeur' => 'nombre positif.',
                'Unité' => 'L, kWh, km ou kg.',
            ]" />
        </div>
    </div>
@endsection
