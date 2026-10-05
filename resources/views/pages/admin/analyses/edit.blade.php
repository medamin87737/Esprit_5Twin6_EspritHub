@extends('layouts.admin')

@section('title', 'Modifier ' . $analyse->numero)

@section('content')
    <x-admin.page-header :title="$analyse->numero" module="Module 2 · Modifier l'analyse"
                         subtitle="Mettez à jour le résultat ou le rapport de cette analyse."
                         :back="route('admin.analyses.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de l'analyse" method="PUT" submit-label="Enregistrer les modifications" files
                               :action="route('admin.analyses.update', $analyse)"
                               :cancel="route('admin.analyses.index')">
                @include('pages.admin.analyses._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.analyses._aide')
        </div>
    </div>
@endsection
