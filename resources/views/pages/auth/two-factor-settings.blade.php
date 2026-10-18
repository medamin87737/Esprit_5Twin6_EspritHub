@extends('layouts.front')

@section('title', 'Double authentification')

@section('content')
    <x-front.page-header eyebrow="Sécurité" title="Authentification à deux facteurs" image="legumes-planche.webp"
                         subtitle="Scannez le QR code avec Google Authenticator ou Authy, puis confirmez avec un code à 6 chiffres." />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if ($user->hasTwoFactorEnabled())
                        <div class="account-card mb-4">
                            <h2 class="account-card-title"><i class="bi bi-shield-check" aria-hidden="true"></i> 2FA activée</h2>
                            <p class="mb-3"><span class="status-pill status-valid">Protection active</span></p>
                            <p class="text-muted small">Un code TOTP est demandé à chaque connexion.</p>

                            <form method="POST" action="{{ route('two-factor.disable') }}" class="row g-3 align-items-end mt-2"
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
                        </div>

                        <div class="account-card">
                            <h2 class="account-card-title">Codes de récupération</h2>
                            @if ($recoveryCodes)
                                <p class="small text-muted">Conservez-les en lieu sûr. Chaque code ne peut être utilisé qu'une fois.</p>
                                <ul class="list-unstyled font-monospace mb-3">
                                    @foreach ($recoveryCodes as $code)
                                        <li>{{ $code }}</li>
                                    @endforeach
                                </ul>
                            @endif
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
                    @elseif ($qr && $secret)
                        <div class="account-card mb-4">
                            <h2 class="account-card-title"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> 1. Scannez le QR code</h2>
                            <div class="text-center mb-3">
                                <img id="nt-2fa-qr-img" src="{{ $qr }}" alt="QR code 2FA" width="220" height="220"
                                     class="img-fluid border rounded bg-white p-2 shadow-sm"
                                     onerror="this.classList.add('d-none'); document.getElementById('nt-2fa-qr-canvas').classList.remove('d-none');">
                                <canvas id="nt-2fa-qr-canvas" class="d-none border rounded bg-white p-2" width="220" height="220"
                                        data-otpauth="{{ $otpauth }}"></canvas>
                            </div>
                            <p class="small text-muted mb-1">Google Authenticator, Authy, Microsoft Authenticator…</p>
                            <p class="small mb-0">Clé manuelle : <code class="user-select-all">{{ $secret }}</code></p>
                        </div>

                        @if ($recoveryCodes)
                            <div class="account-card mb-4">
                                <h2 class="account-card-title text-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Codes de récupération</h2>
                                <p class="small text-muted">Notez-les avant de confirmer. Ils servent si vous perdez votre téléphone.</p>
                                <ul class="list-unstyled font-monospace mb-0">
                                    @foreach ($recoveryCodes as $code)
                                        <li>{{ $code }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="account-card mb-4">
                            <h2 class="account-card-title">2. Confirmez avec un code</h2>
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <label class="form-label" for="code">Code à 6 chiffres</label>
                                    <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
                                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
                                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button class="btn btn-primary" type="submit">Activer la 2FA</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm px-0">Régénérer un autre QR code</button>
                            </form>
                        </div>

                        @push('scripts')
                            <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js" defer></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    var canvas = document.getElementById('nt-2fa-qr-canvas');
                                    if (!canvas || typeof QRCode === 'undefined') return;
                                    QRCode.toCanvas(canvas, canvas.getAttribute('data-otpauth'), { width: 220, margin: 2 }, function () {
                                        canvas.classList.remove('d-none');
                                    });
                                });
                            </script>
                        @endpush
                    @endif

                    <p class="mt-4"><a href="{{ route('profile.edit') }}"><i class="bi bi-arrow-left me-1"></i>Retour au profil</a></p>
                </div>
            </div>
        </div>
    </section>
@endsection
