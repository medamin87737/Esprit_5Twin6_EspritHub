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

    {{-- Encadré d'estimation en direct Climatiq API --}}
    <div id="climatiq-preview" class="alert alert-light border mt-3 p-3 rounded" style="display: none;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge badge-success px-2 py-1">
                <i class="bi bi-cloud-check mr-1" aria-hidden="true"></i> Facteur certifié Climatiq API
            </span>
            <code class="small" id="climatiq-activity-id"></code>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="mr-3 mb-2 mb-md-0">
                <div class="font-weight-600 text-dark" id="climatiq-source"></div>
                <div class="text-muted small">Facteur officiel d'émission : <strong id="climatiq-factor"></strong></div>
            </div>
            <div class="text-md-right">
                <div class="h5 mb-0 text-success font-weight-bold" id="climatiq-co2"></div>
                <small class="text-muted">Émissions brutes de cette mesure</small>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const type = document.querySelector('[data-unite-source]');
            const unite = document.querySelector('[data-unite-cible]');
            const valeur = document.querySelector('input[name="valeur"]');
            const preview = document.getElementById('climatiq-preview');
            const factorEl = document.getElementById('climatiq-factor');
            const sourceEl = document.getElementById('climatiq-source');
            const activityEl = document.getElementById('climatiq-activity-id');
            const co2El = document.getElementById('climatiq-co2');

            const unites = @json(\App\Models\Indicateur::UNITES);
            const climatiqFacteurs = @json(app(\App\Services\ClimatiqService::class)->obtenirFacteurs());

            function updateClimatiqPreview() {
                const t = type ? type.value : '';
                const v = valeur ? parseFloat(valeur.value) : 0;

                if (t && climatiqFacteurs[t]) {
                    const info = climatiqFacteurs[t];
                    if (unite && unites[t]) unite.value = unites[t];

                    factorEl.textContent = Number(info.valeur).toFixed(4) + ' ' + info.unite;
                    sourceEl.textContent = info.source;
                    activityEl.textContent = info.activite_id;

                    if (!isNaN(v) && v > 0) {
                        const total = (v * info.valeur).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        co2El.textContent = total + ' kg CO₂e';
                        co2El.className = 'h5 mb-0 text-success font-weight-bold';
                    } else {
                        co2El.textContent = 'Saisissez une valeur…';
                        co2El.className = 'small text-muted font-italic font-weight-normal';
                    }
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            }

            if (type) type.addEventListener('change', updateClimatiqPreview);
            if (valeur) valeur.addEventListener('input', updateClimatiqPreview);

            // Initialisation si formulaire pré-rempli
            updateClimatiqPreview();
        });
    </script>
@endpush
