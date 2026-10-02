@extends('layouts.admin')

@section('title', 'Nouvelle certification')

@section('content')
    <x-admin.page-header title="Nouvelle certification" module="Module 5 · Certifications"
                         subtitle="Attribuez un label à un produit."
                         :back="route('admin.certifications.index')" />

    <div class="row">
        <div class="col-xl-8">
            <x-admin.form-card title="Détail de la certification"
                               :action="Route::has('admin.certifications.store') ? route('admin.certifications.store') : null"
                               :cancel="route('admin.certifications.index')">
                @include('pages.admin.certifications._form')
            </x-admin.form-card>
        </div>
        <div class="col-xl-4">
            <x-admin.help-card parent="Organisme" child="Certification" :rules="[
                'Produit et organisme' => 'doivent exister.',
                'Numéro' => 'obligatoire et unique.',
                'Type' => 'bio, local ou équitable.',
                'Expiration' => 'postérieure à la date d\'obtention.',
                'Statut' => 'valide, expirée ou suspendue.',
            ]">
                <p class="small text-muted mb-0 mt-3"><i class="bi bi-alarm text-danger mr-1" aria-hidden="true"></i>Les certifications qui expirent dans moins de 30 jours sont signalées en rouge dans la liste.</p>
            </x-admin.help-card>
        </div>
    </div>
@endsection
