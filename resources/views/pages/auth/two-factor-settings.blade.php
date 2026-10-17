@extends('layouts.front')

@section('title', 'Double authentification')

@section('content')
    <x-front.page-header eyebrow="Sécurité" title="Authentification à deux facteurs" image="legumes-planche.webp"
                         subtitle="Protégez votre compte avec un code TOTP (Google Authenticator, Authy…) et des codes de récupération." />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <div class="account-card mb-4">
                        <h2 class="account-card-title"><i class="bi bi-shield-lock" aria-hidden="true"></i> État actuel</h2>
                        @if ($user->hasTwoFactorEnabled())
                            <p class="mb-3"><span class="status-pill status-valid">2FA activée</span></p>
                            <form method="POST" action="{{ route('two-factor.disable') }}" class="row g-3 align-items-end"
                                  onsubmit="return confirm('Désactiver la double authentification ?');">
                                @csrf
                                @method('DELETE')
                                <div class="col-md-8">
                                    <label class="form-label" for="password">Mot de passe pour confirmer</label>
                                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button class="btn btn-outline-danger" type="submit">Désactiver</button>
                                </div>
                            </form>
                        @else
                            <p class="text-muted">La 2FA n'est pas encore activée.</p>
                            <form method="POST" action="{{ route('two-factor.enable') }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Activer la 2FA</button>
                            </form>
                        @endif
                    </div>

                    @if ($qr && $secret)
                        <div class="account-card mb-4">
                            <h2 class="account-card-title">1. Scannez le QR code</h2>
                            <div class="text-center mb-3">
                                <img src="{{ $qr }}" alt="QR code 2FA" width="200" height="200" class="img-fluid border rounded">
                            </div>
                            <p class="small text-muted">Ou saisissez manuellement la clé : <code>{{ $secret }}</code></p>

                            <h3 class="h6 mt-4">2. Confirmez avec un code</h3>
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <label class="form-label" for="code">Code à 6 chiffres</label>
                                    <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" inputmode="numeric" required>
                                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button class="btn btn-primary" type="submit">Confirmer</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if ($recoveryCodes)
                        <div class="account-card mb-4">
                            <h2 class="account-card-title text-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Codes de récupération</h2>
                            <p class="small text-muted">Conservez-les en lieu sûr. Chaque code ne peut être utilisé qu'une fois.</p>
                            <ul class="list-unstyled font-monospace">
                                @foreach ($recoveryCodes as $code)
                                    <li>{{ $code }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($user->hasTwoFactorEnabled())
                        <div class="account-card">
                            <h2 class="account-card-title">Régénérer les codes</h2>
                            <form method="POST" action="{{ route('two-factor.recovery') }}" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <label class="form-label" for="password_regen">Mot de passe</label>
                                    <input type="password" name="password" id="password_regen" class="form-control" required>
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button class="btn btn-outline-primary" type="submit">Régénérer</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <p class="mt-4"><a href="{{ route('profile.edit') }}"><i class="bi bi-arrow-left me-1"></i>Retour au profil</a></p>
                </div>
            </div>
        </div>
    </section>
@endsection
