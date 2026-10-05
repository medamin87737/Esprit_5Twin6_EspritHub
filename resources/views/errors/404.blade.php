@extends('layouts.front')

@section('title', 'Page introuvable')

@section('content')
    <x-front.page-header eyebrow="Erreur 404" title="Page introuvable" />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="error-panel">
                <span class="empty-panel-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                <h2 class="h5">Cette page n'existe pas ou a été déplacée</h2>
                <p>Vérifiez l'adresse, ou repartez du catalogue ou de la recherche d'un lot.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('front.produits.index') }}" class="btn btn-primary rounded-pill px-4">Catalogue</a>
                    <a href="{{ route('front.lots.search') }}" class="btn btn-outline-secondary rounded-pill px-4">Tracer un lot</a>
                </div>
            </div>
        </div>
    </section>
@endsection
