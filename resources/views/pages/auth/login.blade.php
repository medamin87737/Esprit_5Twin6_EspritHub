@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Connexion</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Accès sécurisé</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Bon retour parmi nous</h1>
    <p class="nt-auth-subtitle">Connectez-vous pour accéder à votre espace NutriTrace.</p>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <div class="nt-field">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nom@exemple.tn" autocomplete="email" required autofocus>
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="nt-field">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input id="password" type="password" name="password"
                       class="form-control has-toggle @error('password') is-invalid @enderror"
                       placeholder="Votre mot de passe" autocomplete="current-password" required>
                <button type="button" class="nt-field-toggle" data-password-toggle="password" aria-pressed="false" aria-label="Afficher le mot de passe">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="remember" name="remember" value="1" @checked(old('remember'))>
                <label class="custom-control-label font-weight-normal text-muted" for="remember">Rester connecté</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">
            Se connecter <i class="bi bi-arrow-right ml-1" aria-hidden="true"></i>
        </button>
    </form>

    <p class="nt-auth-switch">
        Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a>
    </p>
@endsection
