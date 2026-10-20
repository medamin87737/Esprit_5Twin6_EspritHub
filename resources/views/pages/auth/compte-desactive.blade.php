@extends('layouts.auth')

@section('title', 'Compte en pause')

@section('content')
    <div class="text-center">
        <span class="nt-avatar mx-auto mb-3" style="width: 4.5rem; height: 4.5rem; font-size: 1.5rem;">{{ $user->initials() }}</span>
        <h1 class="h4 font-weight-bold mb-2">Votre compte est en pause</h1>
        <p class="text-muted mb-4">
            Bonjour {{ $user->name }}, vous avez désactivé votre compte NutriTrace
            @if ($user->deactivated_at)
                le {{ $user->deactivated_at->format('d/m/Y') }}.
            @endif
            Vos données sont conservées : réactivez-le en un clic pour retrouver votre espace.
        </p>

        <form method="POST" action="{{ route('account.reactivate') }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-circle-play mr-1" aria-hidden="true"></i>
                Réactiver mon compte
            </button>
        </form>

        <a href="{{ route('login') }}" class="btn btn-link">Retour à la connexion</a>
    </div>
@endsection
