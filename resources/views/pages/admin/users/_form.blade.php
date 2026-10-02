@php($user = $user ?? null)

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Identité</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="name" label="Nom complet" :value="$user?->name" required placeholder="Prénom et nom" autocomplete="off" />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="email" type="email" label="Adresse e-mail" :value="$user?->email" required placeholder="nom@exemple.tn" autocomplete="off" />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Accès</h3>
    <div class="form-row align-items-start">
        <div class="col-md-6">
            <x-admin.form.select name="role" label="Rôle" :options="\App\Models\User::ROLES" :value="$user?->role ?? 'consommateur'"
                                 placeholder="Choisir un rôle…" required help="Seuls les administrateurs accèdent au Back Office." />
        </div>
        <div class="col-md-6">
            <span class="nt-label d-block">Statut du compte</span>
            <input type="hidden" name="active" value="0">
            <div class="custom-control custom-switch mt-2">
                <input type="checkbox" class="custom-control-input" id="active" name="active" value="1" @checked((bool) old('active', $user?->active ?? true))>
                <label class="custom-control-label font-weight-normal" for="active">Compte actif</label>
            </div>
            <small class="form-text">Un compte suspendu ne peut plus se connecter.</small>
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="fa-solid fa-key" aria-hidden="true"></i> Mot de passe</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.input name="password" type="password" label="{{ $user ? 'Nouveau mot de passe' : 'Mot de passe' }}" :required="! $user"
                                autocomplete="new-password" :help="$user ? 'Laissez vide pour conserver le mot de passe actuel.' : '8 caractères min., avec des lettres et des chiffres.'" />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="password_confirmation" type="password" label="Confirmation" :required="! $user" autocomplete="new-password" />
        </div>
    </div>
</div>
