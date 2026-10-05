@extends('layouts.admin')

@section('title', 'Nouveau produit')

@section('content')
    <x-admin.page-header title="Nouveau produit" :module="$moduleLabel"
                         subtitle="Référencez un produit et rattachez-le à sa catégorie."
                         :back="route($espace . '.produits.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche produit" files
                               :action="route($espace . '.produits.store')"
                               :cancel="route($espace . '.produits.index')">
                @include('pages.admin.produits._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.produits._aide')
        </div>
    </div>
@endsection
