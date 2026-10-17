@extends('layouts.auth')

@section('title', 'Nouveau mot de passe')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Sécurité</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Token à usage unique</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Nouveau mot de passe</h1>
    <p class="nt-auth-subtitle">Choisissez un mot de passe solide (8 caractères min., lettres et chiffres).</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <div class="nt-field">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                       class="form-control @error('email') is-invalid @enderror" required>
            </div>
            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password">Nouveau mot de passe</label>
            <div class="nt-field">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror" required>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirmation</label>
            <div class="nt-field">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">Enregistrer</button>
    </form>
@endsection
