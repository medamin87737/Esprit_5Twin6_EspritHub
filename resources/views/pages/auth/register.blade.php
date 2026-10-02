@extends('layouts.auth')

@section('title', 'Créer un compte')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Inscription</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-leaf" aria-hidden="true"></i> Gratuit et sans engagement</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Rejoindre NutriTrace</h1>
    <p class="nt-auth-subtitle">Créez votre compte en moins d'une minute.</p>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <div class="form-group">
            <label for="name">Nom complet</label>
            <div class="nt-field">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Prénom et nom" autocomplete="name" required autofocus>
            </div>
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <div class="nt-field">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nom@exemple.tn" autocomplete="email" required>
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <fieldset class="form-group">
            <legend class="nt-label">Je suis</legend>
            <div class="nt-profile-grid">
                @foreach ($profils as $valeur => $profil)
                    <label class="nt-profile-option">
                        <input type="radio" name="role" value="{{ $valeur }}" @checked(old('role', 'consommateur') === $valeur)>
                        <span><i class="bi {{ $profil['icone'] }}" aria-hidden="true"></i> {{ $profil['libelle'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('role')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </fieldset>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="password">Mot de passe</label>
                <div class="nt-field">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input id="password" type="password" name="password"
                           class="form-control has-toggle @error('password') is-invalid @enderror"
                           placeholder="8 caractères min." autocomplete="new-password" required>
                    <button type="button" class="nt-field-toggle" data-password-toggle="password" aria-pressed="false" aria-label="Afficher le mot de passe">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="password_confirmation">Confirmation</label>
                <div class="nt-field">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           class="form-control" placeholder="Répéter le mot de passe" autocomplete="new-password" required>
                </div>
            </div>
            @error('password')
                <div class="col-12 invalid-feedback d-block mt-n2 mb-3">{{ $message }}</div>
            @enderror
        </div>
        <small class="form-text mt-n2 mb-3">Au moins 8 caractères, avec des lettres et des chiffres.</small>

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input @error('terms') is-invalid @enderror" id="terms" name="terms" value="1" @checked(old('terms'))>
                <label class="custom-control-label font-weight-normal text-muted" for="terms">
                    J'accepte que mes données soient utilisées dans le cadre de la plateforme NutriTrace.
                </label>
            </div>
            @error('terms')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-3">
            Créer mon compte <i class="bi bi-arrow-right ml-1" aria-hidden="true"></i>
        </button>
    </form>

    <p class="nt-auth-switch">
        Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a>
    </p>
@endsection
