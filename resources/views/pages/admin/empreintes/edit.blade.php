@extends('layouts.admin')

@section('title', 'Modifier l\'empreinte')

@section('content')
    <x-admin.page-header :title="'Empreinte · ' . $empreinte->lot?->numero_lot" module="Module 4 · Modifier l'empreinte"
                         subtitle="Le score est recalculé automatiquement à l'enregistrement."
                         :back="route('admin.empreintes.show', $empreinte)" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Bilan carbone du lot" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.empreintes.update', $empreinte)"
                               :cancel="route('admin.empreintes.show', $empreinte)">
                @include('pages.admin.empreintes._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.empreintes._aide')
        </div>
    </div>
@endsection
