@php($analyse = $analyse ?? null)

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="lot_id" label="Lot analysé" required placeholder="Choisir un lot…"
                                 :options="$lots->mapWithKeys(fn ($l) => [$l->id => $l->numero_lot . ' · ' . $l->produit?->nom])"
                                 :value="$analyse?->lot_id ?? request('lot')"
                                 empty-message="Aucun lot disponible : le module 3 doit d'abord en créer." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="laboratoire_id" label="Laboratoire" required placeholder="Choisir un laboratoire…"
                                 :options="$laboratoires->mapWithKeys(fn ($l) => [$l->id => $l->nom . ' (' . $l->ville . ')'])"
                                 :value="$analyse?->laboratoire_id ?? request('laboratoire')"
                                 empty-message="Aucun laboratoire disponible : créez-en un d'abord." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Analyse</h3>
    <div class="form-row">
        <div class="col-md-4">
            <x-admin.form.input name="numero" label="Numéro" :value="$analyse?->numero ?? $prochainNumero" required placeholder="ANA-2026-0001" />
        </div>
        <div class="col-md-4">
            <x-admin.form.select name="type" label="Type d'analyse" :options="config('nutritrace.options.analyse_types')"
                                 :value="$analyse?->type" placeholder="Choisir…" required />
        </div>
        <div class="col-md-4">
            <x-admin.form.input name="date_prelevement" type="date" label="Date de prélèvement" required
                                :value="$analyse?->date_prelevement?->format('Y-m-d')" help="Après la production du lot." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-clipboard2-check" aria-hidden="true"></i> Résultat</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="resultat" label="Résultat" :options="config('nutritrace.options.analyse_resultats')"
                                 :value="$analyse?->resultat ?? 'en_attente'" placeholder="Choisir…" required />
        </div>
        <div class="col-md-6">
            <x-admin.form.input name="date_resultat" type="date" label="Date du résultat"
                                :value="$analyse?->date_resultat?->format('Y-m-d')" help="Vide tant que le résultat est en attente." />
        </div>
    </div>
    <x-admin.form.textarea name="commentaire" label="Commentaire du laboratoire" rows="3" :value="$analyse?->commentaire"
                           help="Obligatoire si le résultat est non conforme." />
    <x-admin.form.file name="rapport" label="Rapport PDF" accept="application/pdf"
                       :help="$analyse?->rapport ? 'Un rapport est déjà joint : un nouveau fichier le remplacera.' : 'PDF, 5 Mo maximum.'" />
</div>
