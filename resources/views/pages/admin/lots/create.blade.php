@extends('layouts.admin')

@section('title', 'Nouveau lot')

@section('content')
    <x-admin.page-header title="Nouveau lot" module="Module 3 · Traçabilité des lots"
                         subtitle="Déclarez un lot de production pour suivre son parcours."
                         :back="route('admin.lots.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du lot"
                               :action="Route::has('admin.lots.store') ? route('admin.lots.store') : null"
                               :cancel="route('admin.lots.index')">
                @include('pages.admin.lots._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Lot" child="Étapes" :rules="[
                'Numéro de lot' => 'obligatoire et unique.',
                'Produit' => 'doit exister dans le catalogue.',
                'Quantité' => 'entier supérieur à 0.',
                'Péremption' => 'postérieure à la date de production.',
            ]" />
        </div>
    </div>
@endsection
