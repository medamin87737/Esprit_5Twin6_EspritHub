@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Sécurité</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Lien par e-mail</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Mot de passe oublié</h1>
    <p class="nt-auth-subtitle">Indiquez votre e-mail : nous vous enverrons un lien de réinitialisation (expire après {{ config('auth.passwords.users.expire') }} minutes).</p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if (session('reset_url'))
        <div class="alert alert-warning" role="status">
            <strong>Lien de test (mode debug) :</strong><br>
            <a href="{{ session('reset_url') }}">{{ session('reset_url') }}</a>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <div class="nt-field">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nom@exemple.tn" autocomplete="email" required autofocus>
            </div>
            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">Envoyer le lien</button>
    </form>

    <p class="nt-auth-switch"><a href="{{ route('login') }}">Retour à la connexion</a></p>
@endsection
