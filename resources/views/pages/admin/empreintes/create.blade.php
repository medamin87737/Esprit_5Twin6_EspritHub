@extends('layouts.admin')

@section('title', 'Nouvelle empreinte')

@section('content')
    <x-admin.page-header title="Nouvelle empreinte carbone" module="Module 4 · Empreinte environnementale"
                         subtitle="Enregistrez le bilan CO₂ d'un lot."
                         :back="route('admin.empreintes.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Bilan carbone du lot"
                               :action="route('admin.empreintes.store')"
                               :cancel="route('admin.empreintes.index')">
                @include('pages.admin.empreintes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.empreintes._aide')
        </div>
    </div>
@endsection
