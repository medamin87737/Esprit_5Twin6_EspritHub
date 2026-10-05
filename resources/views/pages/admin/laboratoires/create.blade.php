@extends('layouts.admin')

@section('title', 'Nouveau laboratoire')

@section('content')
    <x-admin.page-header title="Nouveau laboratoire" module="Module 2 · Analyses qualité"
                         subtitle="Ajoutez un laboratoire d'analyse accrédité."
                         :back="route('admin.laboratoires.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du laboratoire"
                               :action="route('admin.laboratoires.store')"
                               :cancel="route('admin.laboratoires.index')">
                @include('pages.admin.laboratoires._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.laboratoires._aide')
        </div>
    </div>
@endsection
