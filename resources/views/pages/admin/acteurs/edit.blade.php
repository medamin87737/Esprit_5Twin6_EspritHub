@extends('layouts.admin')

@section('title', 'Modifier ' . $acteur->nom)

@section('content')
    <x-admin.page-header :title="$acteur->nom" module="Administration · Modifier l'acteur"
                         subtitle="Mettez à jour la fiche de cet intervenant et les produits qu'il prend en charge."
                         :back="route('admin.acteurs.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche acteur" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.acteurs.update', $acteur)"
                               :cancel="route('admin.acteurs.index')">
                @include('pages.admin.acteurs._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.acteurs._aide')
        </div>
    </div>
@endsection
