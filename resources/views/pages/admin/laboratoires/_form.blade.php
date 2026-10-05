@php($laboratoire = $laboratoire ?? null)

<div class="form-row">
    <div class="col-md-7">
        <x-admin.form.input name="nom" label="Nom du laboratoire" :value="$laboratoire?->nom" required placeholder="Ex. Laboratoire Central d'Analyses" />
    </div>
    <div class="col-md-5">
        <x-admin.form.input name="accreditation" label="Accréditation" :value="$laboratoire?->accreditation" required placeholder="Ex. TUNAC ISO/IEC 17025 n° 1-0042" />
    </div>
</div>

<div class="form-row">
    <div class="col-md-6">
        <x-admin.form.input name="ville" label="Ville" :value="$laboratoire?->ville" required placeholder="Ex. Tunis" />
    </div>
    <div class="col-md-6">
        <x-admin.form.input name="pays" label="Pays" :value="$laboratoire?->pays ?? 'Tunisie'" required />
    </div>
</div>

<div class="form-row">
    <div class="col-md-7">
        <x-admin.form.input name="email" type="email" label="E-mail" :value="$laboratoire?->email" required placeholder="contact@laboratoire.tn" />
    </div>
    <div class="col-md-5">
        <x-admin.form.input name="telephone" type="tel" label="Téléphone" :value="$laboratoire?->telephone" required placeholder="+216 71 000 000" />
    </div>
</div>
