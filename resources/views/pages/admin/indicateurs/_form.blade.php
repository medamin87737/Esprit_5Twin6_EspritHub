@php
    $indicateur = $indicateur ?? null;
    $empreintes = $empreintes ?? collect();
    $etapes = $etapes ?? collect();
@endphp

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-link-45deg" aria-hidden="true"></i> Rattachement</h3>
    <div class="form-row">
        <div class="col-md-6">
            <x-admin.form.select name="empreinte_carbone_id" label="Empreinte carbone"
                                 :options="$empreintes->mapWithKeys(fn ($e) => [$e->id => ($e->lot?->numero_lot ?? 'Empreinte #' . $e->id) . ' · ' . $e->lot?->produit?->nom . ' — score ' . $e->score])"
                                 :value="$indicateur?->empreinte_carbone_id ?? request('empreinte')" placeholder="Choisir une empreinte…" required
                                 empty-message="Aucune empreinte disponible : créez-en une d'abord." />
        </div>
        <div class="col-md-6">
            <x-admin.form.select name="etape_id" label="Étape liée"
                                 :options="$etapes->mapWithKeys(fn ($e) => [$e->id => ($e->lot?->numero_lot ?? '') . ' · ' . $e->typeLabel() . ' · ' . $e->lieu])"
                                 :value="$indicateur?->etape_id" placeholder="Aucune étape (lot entier)"
                                 help="Facultatif. L'étape doit appartenir au lot de l'empreinte." />
        </div>
    </div>
</div>

<div class="nt-form-section">
    <h3 class="nt-form-section-title"><i class="bi bi-speedometer2" aria-hidden="true"></i> Mesure</h3>
    <div class="form-row">
        <div class="col-md-4">
            <x-admin.form.select name="type" label="Type" :options="config('nutritrace.options.indicateur_types')"
                                 :value="$indicateur?->type" placeholder="Choisir…" required data-unite-source />
        </div>
        <div class="col-md-4">
            <x-admin.form.input name="valeur" type="number" min="0" step="0.01" label="Valeur" :value="$indicateur?->valeur" required placeholder="Ex. 12.5" />
        </div>
        <div class="col-md-4">
            <x-admin.form.select name="unite" label="Unité" :options="config('nutritrace.options.unites')"
                                 :value="$indicateur?->unite" placeholder="Choisir…" required data-unite-cible
                                 help="Remplie automatiquement selon le type." />
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const type = document.querySelector('[data-unite-source]');
            const unite = document.querySelector('[data-unite-cible]');
            const unites = @json(\App\Models\Indicateur::UNITES);
            if (!type || !unite) return;

            type.addEventListener('change', () => {
                if (unites[type.value]) unite.value = unites[type.value];
            });
        });
    </script>
@endpush
