@extends('layouts.front')

@php($edition = $signalement->exists)

@section('title', $edition ? 'Modifier mon signalement' : 'Signaler un problème')

@section('content')
    <x-front.page-header eyebrow="Signalement" :title="$edition ? 'Modifier mon signalement' : 'Signaler un problème'" image="consommateur-salade.webp"
                         :subtitle="'Produit : ' . $produit->nom" />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form method="POST" enctype="multipart/form-data" class="account-card" novalidate
                          action="{{ $edition ? route('consommateur.signalements.update', $signalement) : route('consommateur.signalements.store', $produit) }}">
                        @csrf
                        @if ($edition)
                            @method('PUT')
                        @endif

                        <h2 class="account-card-title"><i class="bi bi-flag" aria-hidden="true"></i> Votre signalement</h2>

                        <x-front.champ name="motif" label="Motif" type="select" required :value="$signalement->motif"
                                       :options="config('nutritrace.options.signalement_motifs')" placeholder="Choisir un motif…" />

                        <x-front.champ name="description" label="Description" type="textarea" required rows="5" :value="$signalement->description"
                                       placeholder="Décrivez précisément le problème constaté (au moins 10 caractères)." maxlength="2000" />

                        <x-front.champ name="preuve" label="Preuve (facultative)" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                       :help="$edition && $signalement->preuve ? 'Une preuve est déjà jointe : choisissez un fichier pour la remplacer. Image ou PDF, 4 Mo max.' : 'Photo de l\'emballage, ticket… Image ou PDF, 4 Mo max.'" />

                        <div class="form-actions">
                            <a href="{{ $edition ? route('consommateur.signalements.index') : route('front.produits.show', $produit) }}" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">{{ $edition ? 'Enregistrer les modifications' : 'Envoyer le signalement' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
