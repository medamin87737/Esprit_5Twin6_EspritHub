<x-admin.help-card parent="Laboratoire" child="Analyses" :rules="[
    'Lot et laboratoire' => 'obligatoires.',
    'Numéro' => 'format ANA-AAAA-NNNN, unique.',
    'Prélèvement' => 'entre la production du lot et aujourd\'hui.',
    'Date du résultat' => 'obligatoire sauf « En attente », après le prélèvement.',
    'Commentaire' => 'obligatoire si « Non conforme ».',
    'Rapport' => 'TXT ou DOCX, 5 Mo maximum, téléchargeable par les membres connectés.',
]" />

<x-admin.help-card title="Fonctions avancées & IA" icon="bi-stars" :rules="[
    'Lire le rapport · Résumé qualité' => 'Fichier TXT / DOCX → lecture + résumé qualité animé et préremplissage.',
    'Résumé IA' => 'disponible sur la fiche analyse après enregistrement.',
    'Score de risque' => 'sur la fiche lot : faible / moyen / élevé selon l\'historique des analyses.',
    'Export CSV' => 'télécharge les analyses filtrées depuis la liste.',
    'Clé API' => 'OPENROUTER_API_KEY dans le fichier .env.',
]" />

<x-admin.help-card title="Relations" icon="bi-diagram-3" :rules="[
    'Laboratoire → Analyses' => 'chaque analyse est réalisée par un laboratoire.',
    'Lot → Analyses' => 'un lot (Module 3) peut recevoir plusieurs analyses ; supprimer le lot supprime ses analyses.',
    'Front Office' => 'le résultat apparaît sur la page publique du lot et la fiche produit.',
]" />
