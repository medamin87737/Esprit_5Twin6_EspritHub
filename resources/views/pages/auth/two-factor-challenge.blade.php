@extends('layouts.auth')

@section('title', 'Double authentification')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">2FA</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> TOTP</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Code de vérification</h1>
    <p class="nt-auth-subtitle">Ouvrez votre application d'authentification (Google Authenticator, Authy…) ou saisissez un code de récupération.</p>

    <form method="POST" action="{{ route('two-factor.authenticate') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="code">Code à 6 chiffres</label>
            <div class="nt-field">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                       class="form-control @error('code') is-invalid @enderror" required autofocus>
            </div>
            @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">Valider</button>
    </form>

    <p class="nt-auth-switch"><a href="{{ route('login') }}">Retour à la connexion</a></p>
@endsection
