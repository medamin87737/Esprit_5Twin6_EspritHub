@extends('layouts.admin')

@section('title', 'Nouveau produit')

@section('content')
    <x-admin.page-header title="Nouveau produit" module="Module 1 · Produits & Catégories"
                         subtitle="Référencez un produit et rattachez-le à sa catégorie."
                         :back="route('admin.produits.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Fiche produit" files
                               :action="route('admin.produits.store')"
                               :cancel="route('admin.produits.index')">
                @include('pages.admin.produits._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.produits._aide')
        </div>
    </div>
@endsection
