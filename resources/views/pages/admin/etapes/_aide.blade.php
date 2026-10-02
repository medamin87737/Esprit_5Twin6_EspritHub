<x-admin.help-card parent="Lot" child="Étape" :rules="[
    'Lot et acteur' => 'doivent exister.',
    'Type d\'étape' => 'production, transformation, distribution ou vente.',
    'Date et heure' => 'obligatoire, pas avant la production du lot.',
    'Lieu' => 'obligatoire.',
    'Transport' => 'une valeur de la liste.',
    'Remarques' => 'facultatives.',
]" />

<x-admin.help-card title="Relations de l'étape" icon="bi-diagram-2" :rules="[
    'Lot → Étapes' => 'chaque étape appartient à un lot (Module 3).',
    'Acteur → Étapes' => 'chaque étape est réalisée par un acteur (Module 2), qui ne peut plus être supprimé tant qu\'il a des étapes.',
]" />
