<x-admin.help-card parent="Laboratoire" child="Analyses" :rules="[
    'Lot et laboratoire' => 'obligatoires.',
    'Numéro' => 'format ANA-AAAA-NNNN, unique.',
    'Prélèvement' => 'entre la production du lot et aujourd\'hui.',
    'Date du résultat' => 'obligatoire sauf « En attente », après le prélèvement.',
    'Commentaire' => 'obligatoire si « Non conforme ».',
    'Rapport' => 'PDF de 5 Mo maximum, téléchargeable par les membres connectés.',
]" />

<x-admin.help-card title="Relations" icon="bi-diagram-3" :rules="[
    'Laboratoire → Analyses' => 'chaque analyse est réalisée par un laboratoire.',
    'Lot → Analyses' => 'un lot (Module 3) peut recevoir plusieurs analyses ; supprimer le lot supprime ses analyses.',
    'Front Office' => 'le résultat apparaît sur la page publique du lot et la fiche produit.',
]" />
