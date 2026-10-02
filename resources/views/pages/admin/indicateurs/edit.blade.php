@extends('layouts.admin')

@section('title', 'Modifier l\'indicateur')

@section('content')
    <x-admin.page-header :title="$indicateur->typeLabel() . ' · ' . $indicateur->empreinteCarbone?->lot?->numero_lot" module="Module 4 · Modifier l'indicateur"
                         subtitle="Corrigez la mesure, son unité ou l'étape à laquelle elle se rapporte."
                         :back="route('admin.empreintes.show', $indicateur->empreinte_carbone_id)" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Mesure d'impact" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.indicateurs.update', $indicateur)"
                               :cancel="route('admin.empreintes.show', $indicateur->empreinte_carbone_id)">
                @include('pages.admin.indicateurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.indicateurs._aide')
        </div>
    </div>
@endsection
