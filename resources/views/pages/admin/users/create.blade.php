@extends('layouts.admin')

@section('title', 'Nouvel utilisateur')

@section('content')
    <x-admin.page-header title="Nouvel utilisateur" module="Administration"
                         subtitle="Créez un compte et attribuez-lui un rôle sur la plateforme."
                         :back="route('admin.users.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du compte" submit-label="Créer le compte"
                               :action="route('admin.users.store')" :cancel="route('admin.users.index')">
                @include('pages.admin.users._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.users._roles')
        </div>
    </div>
@endsection
