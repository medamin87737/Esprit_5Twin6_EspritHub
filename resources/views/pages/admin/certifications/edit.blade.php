@extends('layouts.admin')

@section('title', 'Modifier ' . $certification->numero)

@section('content')
    <x-admin.page-header :title="$certification->numero" module="Module 5 · Modifier la certification"
                         subtitle="Renouvelez, suspendez ou corrigez cette certification."
                         :back="route('admin.certifications.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Détail de la certification" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.certifications.update', $certification)"
                               :cancel="route('admin.certifications.index')">
                @include('pages.admin.certifications._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.certifications._aide')
        </div>
    </div>
@endsection
