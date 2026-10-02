@extends('layouts.admin')

@section('title', 'Nouvel organisme')

@section('content')
    <x-admin.page-header title="Nouvel organisme" module="Module 5 · Certifications"
                         subtitle="Ajoutez un organisme certificateur accrédité."
                         :back="route('admin.organismes.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de l'organisme"
                               :action="Route::has('admin.organismes.store') ? route('admin.organismes.store') : null"
                               :cancel="route('admin.organismes.index')">
                @include('pages.admin.organismes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Organisme" child="Certifications" :rules="[
                'Nom et pays' => 'obligatoires.',
                'Site web' => 'URL valide (https://…).',
                'Accréditation' => 'obligatoire.',
            ]" />
        </div>
    </div>
@endsection
