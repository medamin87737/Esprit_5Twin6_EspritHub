@extends('layouts.front')

@section('title', 'Accès refusé')

@section('content')
    <x-front.page-header eyebrow="Erreur 403" title="Accès refusé" />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="error-panel">
                <span class="empty-panel-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                @auth
                    <h2 class="h5">Cette page n'est pas accessible avec votre profil</h2>
                    <p>Vous êtes connecté en tant que <strong>{{ auth()->user()->roleLabel() }}</strong>. Cette page est réservée à un autre type de compte.</p>
                    <a href="{{ auth()->user()->espaceParDefaut() }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-house me-1" aria-hidden="true"></i> Retour à mon espace
                    </a>
                @else
                    <h2 class="h5">Connexion requise</h2>
                    <p>Connectez-vous avec un compte autorisé pour accéder à cette page.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i> Se connecter
                    </a>
                @endauth
            </div>
        </div>
    </section>
@endsection
