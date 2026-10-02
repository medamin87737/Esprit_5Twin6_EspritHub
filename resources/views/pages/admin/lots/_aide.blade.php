<x-admin.help-card parent="Lot" child="Étapes" :rules="[
    'Numéro de lot' => 'obligatoire, unique, lettres, chiffres et tirets (mis en majuscules).',
    'Produit' => 'doit exister dans le catalogue (Module 1).',
    'Quantité' => 'entier supérieur à 0.',
    'Production' => 'aujourd\'hui ou avant.',
    'Péremption' => 'postérieure à la date de production.',
]" />

<x-admin.help-card title="Relations du lot" icon="bi-diagram-2" :rules="[
    'Produit → Lots' => 'un produit du catalogue peut avoir plusieurs lots.',
    'Lot → Étapes' => 'supprimer un lot supprime aussi ses étapes.',
]" />
