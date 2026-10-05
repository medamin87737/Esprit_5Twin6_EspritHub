@extends('layouts.pro')

@section('title', 'Déclarer une certification')

@section('pro_content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if ($produits->isEmpty())
                <x-front.empty-state icon="bi-box-seam" title="Ajoutez d'abord un produit">
                    Une certification est toujours attribuée à l'un de vos produits.
                </x-front.empty-state>
            @else
                <form method="POST" action="{{ route('pro.certifications.store') }}" class="account-card" novalidate>
                    @csrf
                    <h2 class="account-card-title"><i class="bi bi-award" aria-hidden="true"></i> Déclaration de certification</h2>

                    <div class="row gx-3">
                        <x-front.champ class="col-md-6" name="produit_id" label="Produit" type="select" required :options="$produits" placeholder="Choisir un de vos produits…" />
                        <x-front.champ class="col-md-6" name="organisme_id" label="Organisme certificateur" type="select" required :options="$organismes" />
                        <x-front.champ class="col-md-5" name="type" label="Type de label" type="select" required :options="config('nutritrace.options.certification_types')" />
                        <x-front.champ class="col-md-7" name="numero" label="Numéro du certificat" required maxlength="40" placeholder="Ex. BIO-TN-2026-015" />
                        <x-front.champ class="col-md-6" name="date_obtention" label="Date d'obtention" type="date" required />
                        <x-front.champ class="col-md-6" name="date_expiration" label="Date d'expiration" type="date" required />
                    </div>

                    <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>La certification sera enregistrée <strong>en attente</strong> : elle n'apparaîtra sur la fiche publique qu'après vérification.</p>

                    <div class="form-actions">
                        <a href="{{ route('pro.certifications.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Envoyer la déclaration</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
