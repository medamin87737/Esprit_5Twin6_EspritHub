@extends('layouts.admin')

@section('title', 'Nouvelle analyse')

@section('content')
    <x-admin.page-header title="Nouvelle analyse" module="Module 2 · Analyses qualité"
                         subtitle="Enregistrez le contrôle sanitaire d'un lot."
                         :back="route('admin.analyses.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de l'analyse" files
                               :action="route('admin.analyses.store')"
                               :cancel="route('admin.analyses.index')">
                @include('pages.admin.analyses._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.analyses._aide')
        </div>
    </div>
@endsection
