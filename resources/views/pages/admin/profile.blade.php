@extends('layouts.admin')

@section('title', 'Mon profil')

@section('content')
    <x-admin.page-header title="Mon profil" module="Compte" subtitle="Gérez vos informations personnelles et la sécurité de votre compte." />

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check mt-1" aria-hidden="true"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <span class="nt-avatar mb-3" style="width: 5rem; height: 5rem; font-size: 1.6rem;">{{ $user->initials() }}</span>
                    <h2 class="h5 font-weight-600 mb-1">{{ $user->name }}</h2>
                    <div class="text-muted small mb-3">{{ $user->email }}</div>
                    <span class="nt-badge nt-badge-gold"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> {{ $user->roleLabel() }}</span>
                </div>
                <div class="card-footer">
                    <div class="nt-role-row"><span class="text-muted">Membre depuis</span><strong>{{ $user->created_at?->translatedFormat('j F Y') }}</strong></div>
                    <div class="nt-role-row"><span class="text-muted">Dernière connexion</span><strong>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <form method="POST" action="{{ route('profile.update') }}" novalidate class="card mb-4">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="nt-card-title"><i class="fa-solid fa-id-card text-muted mr-2" aria-hidden="true"></i>Informations personnelles</h2></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Nom complet</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="form-control @error('name', 'profile') is-invalid @enderror" autocomplete="name" required>
                            @error('name', 'profile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Adresse e-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control @error('email', 'profile') is-invalid @enderror" autocomplete="email" required>
                            @error('email', 'profile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i> Enregistrer</button>
                </div>
            </form>

            <form method="POST" action="{{ route('profile.password') }}" novalidate class="card mb-4">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="nt-card-title"><i class="fa-solid fa-lock text-muted mr-2" aria-hidden="true"></i>Sécurité</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input id="current_password" name="current_password" type="password" class="form-control @error('current_password', 'password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('current_password', 'password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">Nouveau mot de passe</label>
                            <input id="password" name="password" type="password" class="form-control @error('password', 'password') is-invalid @enderror" autocomplete="new-password" required>
                            @error('password', 'password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @else
                                <small class="form-text">8 caractères min., avec des lettres et des chiffres.</small>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password_confirmation">Confirmation</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key mr-1" aria-hidden="true"></i> Changer le mot de passe</button>
                </div>
            </form>
        </div>
    </div>
@endsection
