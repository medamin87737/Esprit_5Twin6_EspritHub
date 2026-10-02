<x-admin.help-card title="Barème de l'éco-score" icon="bi-bar-chart-steps" parent="Empreinte" child="Indicateurs">
    <ul class="nt-rule-list mt-n2">
        @foreach (config('nutritrace.options.scores') as $score => $seuil)
            <li class="align-items-center">
                <span class="nt-score nt-score-{{ strtolower($score) }}">{{ $score }}</span>
                <span>{{ $seuil }}</span>
            </li>
        @endforeach
    </ul>
</x-admin.help-card>

<x-admin.help-card :rules="[
    'Lot' => 'doit exister et ne pas avoir déjà d\'empreinte.',
    'CO₂ total' => 'nombre positif, en kg CO₂e par kg de produit.',
    'Date du calcul' => 'entre la production du lot et aujourd\'hui.',
    'Méthode' => 'obligatoire (ACV, Agribalyse…).',
]" />

<x-admin.help-card title="Relations de l'empreinte" icon="bi-diagram-2" :rules="[
    'Lot ↔ Empreinte' => 'chaque lot (Module 3) a au plus une empreinte (1 ↔ 1).',
    'Empreinte → Indicateurs' => 'supprimer une empreinte supprime ses indicateurs.',
]" />
