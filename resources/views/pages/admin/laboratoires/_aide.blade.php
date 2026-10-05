<x-admin.help-card parent="Laboratoire" child="Analyses" :rules="[
    'Nom' => 'obligatoire, unique, 120 caractères max.',
    'Ville et pays' => 'obligatoires.',
    'Accréditation' => 'obligatoire (ex. TUNAC ISO/IEC 17025 n° 1-0042).',
    'E-mail' => 'obligatoire, valide et unique.',
    'Téléphone' => 'chiffres, espaces, points, tirets (8 caractères min.).',
]" />

<x-admin.help-card title="Relations" icon="bi-diagram-3" :rules="[
    'Laboratoire → Analyses' => 'un laboratoire réalise plusieurs analyses.',
    'Laboratoire ↔ Lots' => 'via ses analyses, un laboratoire contrôle plusieurs lots (Module 3).',
    'Suppression' => 'impossible tant que le laboratoire a des analyses.',
]" />
