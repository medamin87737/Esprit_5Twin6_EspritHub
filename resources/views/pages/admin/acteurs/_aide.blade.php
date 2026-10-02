<x-admin.help-card parent="Type d'acteur" child="Acteur" :rules="[
    'Nom, adresse, pays' => 'obligatoires.',
    'Type' => 'doit exister dans la liste.',
    'E-mail' => 'adresse valide et unique.',
    'Téléphone' => 'format international accepté (+216 …).',
    'Date d\'inscription' => 'aujourd\'hui ou avant.',
    'Coordonnées' => 'facultatives, latitude et longitude ensemble.',
]" />

<x-admin.help-card title="Relation Acteurs - Produits (N - N)" icon="bi-diagram-2" :rules="[
    'Produits' => 'un acteur peut prendre en charge plusieurs produits, et un produit peut être géré par plusieurs acteurs.',
]" />
