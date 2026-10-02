<x-admin.help-card parent="Organisme" child="Certifications" :rules="[
    'Nom' => 'obligatoire, unique, 120 caractères max.',
    'Pays' => 'obligatoire.',
    'Site web' => 'URL valide (https:// ajouté si absent).',
    'Accréditation' => 'obligatoire (ex. COFRAC n° 7-0001).',
]" />

<x-admin.help-card title="Relations" icon="bi-diagram-3" :rules="[
    'Organisme → Certifications' => 'un organisme délivre plusieurs certifications.',
    'Organisme ↔ Produits' => 'via ses certifications, un organisme certifie plusieurs produits.',
    'Suppression' => 'impossible tant que l\'organisme a délivré des certifications.',
]" />
