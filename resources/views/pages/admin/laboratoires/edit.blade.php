@extends('layouts.admin')

@section('title', 'Modifier ' . $laboratoire->nom)

@section('content')
    <x-admin.page-header :title="$laboratoire->nom" module="Module 2 · Modifier le laboratoire"
                         subtitle="Mettez à jour les informations de ce laboratoire."
                         :back="route('admin.laboratoires.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du laboratoire" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.laboratoires.update', $laboratoire)"
                               :cancel="route('admin.laboratoires.index')">
                @include('pages.admin.laboratoires._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.laboratoires._aide')
        </div>
    </div>
@endsection
