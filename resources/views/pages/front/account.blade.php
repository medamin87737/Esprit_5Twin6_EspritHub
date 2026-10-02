@extends('layouts.front')

@section('title', 'Mon compte')

@section('content')
    <x-front.page-header eyebrow="Mon compte" :title="'Bonjour ' . \Illuminate\Support\Str::of($user->name)->explode(' ')->first()" image="legumes-planche.webp"
                         subtitle="Gérez vos informations personnelles et la sécurité de votre compte NutriTrace." />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4 account-grid">
                <div class="col-lg-4">
                    <aside class="account-card account-summary">
                        <span class="account-avatar">{{ $user->initials() }}</span>
                        <h2 class="h5 mb-1">{{ $user->name }}</h2>
                        <p class="text-muted small mb-3">{{ $user->email }}</p>
                        <span class="label-chip"><i class="bi bi-person-badge" aria-hidden="true"></i> {{ $user->roleLabel() }}</span>

                        <dl class="account-facts">
                            <div><dt>Membre depuis</dt><dd>{{ $user->created_at?->translatedFormat('j F Y') }}</dd></div>
                            <div><dt>Dernière connexion</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                        </dl>

                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary w-100 mt-3">
                                <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Espace d'administration
                            </a>
                        @endif
                    </aside>
                </div>

                <div class="col-lg-8">
                    @if (session('status'))
                        <div class="alert alert-success d-flex align-items-center gap-2" role="status">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" class="account-card mb-4" novalidate>
                        @csrf
                        @method('PUT')
                        <h2 class="account-card-title"><i class="bi bi-person-vcard" aria-hidden="true"></i> Informations personnelles</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nom complet</label>
                                <input class="form-control @error('name', 'profile') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                                @error('name', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Adresse e-mail</label>
                                <input class="form-control @error('email', 'profile') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                                @error('email', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="account-card-footer">
                            <button class="btn btn-primary" type="submit">Enregistrer</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('profile.password') }}" class="account-card mb-4" novalidate>
                        @csrf
                        @method('PUT')
                        <h2 class="account-card-title"><i class="bi bi-shield-lock" aria-hidden="true"></i> Mot de passe</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="current_password">Mot de passe actuel</label>
                                <input class="form-control @error('current_password', 'password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                                @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Nouveau mot de passe</label>
                                <input class="form-control @error('password', 'password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                                @error('password', 'password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">8 caractères min., avec des lettres et des chiffres.</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirmation</label>
                                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="account-card-footer">
                            <button class="btn btn-primary" type="submit">Changer le mot de passe</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('profile.destroy') }}" class="account-card account-danger" novalidate
                          onsubmit="return confirm('Supprimer définitivement votre compte NutriTrace ?');">
                        @csrf
                        @method('DELETE')
                        <h2 class="account-card-title"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i> Supprimer mon compte</h2>
                        <p class="text-muted small">Cette action est définitive : votre compte et vos informations seront effacés.</p>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label" for="delete_password">Confirmez avec votre mot de passe</label>
                                <input class="form-control @error('delete_password', 'deletion') is-invalid @enderror" id="delete_password" name="delete_password" type="password" autocomplete="current-password" required>
                                @error('delete_password', 'deletion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5 d-grid">
                                <button class="btn btn-outline-danger" type="submit">Supprimer mon compte</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
