@extends('layouts.admin')

@section('title', 'Nouvel indicateur')

@section('content')
    <x-admin.page-header title="Nouvel indicateur" module="Module 4 · Empreinte environnementale"
                         subtitle="Ajoutez une mesure d'impact à une empreinte carbone."
                         :back="route('admin.indicateurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Mesure d'impact"
                               :action="route('admin.indicateurs.store')"
                               :cancel="route('admin.indicateurs.index')">
                @include('pages.admin.indicateurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.indicateurs._aide')
        </div>
    </div>
@endsection
