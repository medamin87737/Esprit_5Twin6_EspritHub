@extends('layouts.admin')

@section('title', 'Modifier l\'étape')

@section('content')
    <x-admin.page-header :title="$etape->typeLabel() . ' · ' . $etape->lot?->numero_lot" module="Module 3 · Modifier l'étape"
                         subtitle="Corrigez le lot, l'acteur, la date ou les conditions de ce passage."
                         :back="route('admin.lots.show', $etape->lot_id)" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Détail de l'étape" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.etapes.update', $etape)"
                               :cancel="route('admin.lots.show', $etape->lot_id)">
                @include('pages.admin.etapes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.etapes._aide')
        </div>
    </div>
@endsection
