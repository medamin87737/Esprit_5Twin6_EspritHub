@extends('layouts.admin')

@section('title', 'Modifier ' . $user->name)

@section('content')
    <x-admin.page-header :title="$user->name" module="Administration · Modifier le compte"
                         :subtitle="'Membre depuis le ' . $user->created_at?->format('d/m/Y') . ($user->last_login_at ? ' · dernière connexion ' . $user->last_login_at->diffForHumans() : ' · jamais connecté')"
                         :back="route('admin.users.index')" />

    @if ($user->is(auth()->user()))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-info mt-1" aria-hidden="true"></i>
            <div>Vous modifiez votre propre compte : votre rôle d'administrateur et votre statut actif ne peuvent pas être retirés.</div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Informations du compte" method="PUT" submit-label="Enregistrer les modifications"
                               :action="route('admin.users.update', $user)" :cancel="route('admin.users.index')">
                @include('pages.admin.users._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            @include('pages.admin.users._roles')
        </div>
    </div>
@endsection
