@extends('layouts.admin')

@section('title', 'Modifier ' . $organisme->nom)

@section('content')
    <x-admin.page-header :title="$organisme->nom" module="Module 5 · Modifier l'organisme"
                         subtitle="Mettez à jour les informations de cet organisme certificateur."
                         :back="route('admin.organismes.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations de l'organisme" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.organismes.update', $organisme)"
                               :cancel="route('admin.organismes.index')">
                @include('pages.admin.organismes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.organismes._aide')
        </div>
    </div>
@endsection
