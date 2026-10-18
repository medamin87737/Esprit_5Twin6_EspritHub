@extends('layouts.auth')

@section('title', 'Vérifier l\'e-mail')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Vérification</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-clock" aria-hidden="true"></i> Lien signé · {{ config('auth.verification.expire', 60) }} min</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Confirmez votre e-mail</h1>
    <p class="nt-auth-subtitle">
        Un message a été envoyé à <strong>{{ auth()->user()->email }}</strong>.
        Cliquez sur le lien pour activer votre compte (MustVerifyEmail).
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if (session('verification_url'))
        <div class="alert alert-warning" role="status">
            <p class="mb-2">Lien de vérification (développement) :</p>
            <a href="{{ session('verification_url') }}" class="btn btn-sm btn-primary">Activer mon compte</a>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg btn-block">Renvoyer l'e-mail</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link btn-block">Se déconnecter</button>
    </form>
@endsection
