@extends('layouts.admin')

@section('title', 'Modifier ' . $lot->numero_lot)

@section('content')
    <x-admin.page-header :title="$lot->numero_lot" module="Module 3 · Modifier le lot"
                         subtitle="Mettez à jour le produit, la quantité ou les dates de ce lot."
                         :back="route('admin.lots.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du lot" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.lots.update', $lot)"
                               :cancel="route('admin.lots.index')">
                @include('pages.admin.lots._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.lots._aide')
        </div>
    </div>
@endsection
