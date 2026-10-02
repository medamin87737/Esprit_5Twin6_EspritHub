@php
    $empreinte = $empreinte ?? null;
    $lots = $lots ?? collect();
@endphp

<div class="form-row">
    <div class="col-md-6">
        <x-admin.form.select name="lot_id" label="Lot"
                             :options="$lots->mapWithKeys(fn ($lot) => [$lot->id => $lot->numero_lot . ' · ' . $lot->produit?->nom])"
                             :value="$empreinte?->lot_id ?? request('lot')" placeholder="Choisir un lot…" required
                             help="Seuls les lots sans empreinte sont proposés (une empreinte par lot)."
                             empty-message="Aucun lot disponible : tous ont déjà une empreinte, ou le module 3 doit d'abord en créer." />
    </div>
    <div class="col-md-6">
        <x-admin.form.input name="co2_total" type="number" min="0" step="0.01" label="CO₂ total (kg CO₂e)"
                            :value="$empreinte?->co2_total" required placeholder="Ex. 1.25" data-score-source />
    </div>
</div>

<div class="form-row">
    <div class="col-md-6">
        <x-admin.form.input name="date_calcul" type="date" label="Date du calcul"
                            :value="$empreinte?->date_calcul?->format('Y-m-d')" required />
    </div>
    <div class="col-md-6">
        <x-admin.form.input name="methode" label="Méthode de calcul" :value="$empreinte?->methode" required
                            list="methodes" placeholder="Ex. Agribalyse" />
        <datalist id="methodes">
            <option value="ACV">
            <option value="Agribalyse">
            <option value="Bilan Carbone">
        </datalist>
    </div>
</div>

<div class="nt-relation mt-2" aria-live="polite">
    <i class="bi bi-magic" aria-hidden="true"></i>
    <span>Éco-score attribué automatiquement :</span>
    <span class="nt-score nt-score-{{ strtolower($empreinte?->score ?? 'a') }} {{ $empreinte ? '' : 'd-none' }}" data-score-preview>{{ $empreinte?->score }}</span>
    <span class="text-muted {{ $empreinte ? 'd-none' : '' }}" data-score-placeholder>saisissez le CO₂ total</span>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.querySelector('[data-score-source]');
            const preview = document.querySelector('[data-score-preview]');
            const placeholder = document.querySelector('[data-score-placeholder]');
            if (!input || !preview) return;

            const scoreFor = (kg) => kg < 1 ? 'A' : kg < 2 ? 'B' : kg < 4 ? 'C' : kg < 7 ? 'D' : 'E';
            const update = () => {
                const kg = parseFloat(input.value);
                const valid = !Number.isNaN(kg) && kg >= 0;
                preview.classList.toggle('d-none', !valid);
                placeholder.classList.toggle('d-none', valid);
                if (valid) {
                    const score = scoreFor(kg);
                    preview.textContent = score;
                    preview.className = 'nt-score nt-score-' + score.toLowerCase();
                }
            };
            input.addEventListener('input', update);
            update();
        });
    </script>
@endpush
