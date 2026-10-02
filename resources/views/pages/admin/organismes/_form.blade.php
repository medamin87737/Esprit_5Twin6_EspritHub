@php($organisme = $organisme ?? null)

<div class="form-row">
    <div class="col-md-7">
        <x-admin.form.input name="nom" label="Nom de l'organisme" :value="$organisme?->nom" required placeholder="Ex. Ecocert" />
    </div>
    <div class="col-md-5">
        <x-admin.form.input name="pays" label="Pays" :value="$organisme?->pays" required placeholder="Ex. France" />
    </div>
</div>

<div class="form-row">
    <div class="col-md-7">
        <x-admin.form.input name="site_web" type="url" label="Site web" :value="$organisme?->site_web" required placeholder="https://…" />
    </div>
    <div class="col-md-5">
        <x-admin.form.input name="accreditation" label="Accréditation" :value="$organisme?->accreditation" required placeholder="Ex. COFRAC n° 7-0001" />
    </div>
</div>
