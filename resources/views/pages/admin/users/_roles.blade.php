<x-admin.help-card title="Les rôles" icon="bi-person-gear" :rules="[
    'Administrateur' => 'accès complet au Back Office et aux comptes.',
    'Fournisseur' => 'gère ses propres produits (ajout, modification, suppression) depuis l\'espace fournisseur.',
    'Producteur, transformateur, distributeur' => 'acteurs de la chaîne, accès au site public.',
    'Consommateur' => 'consulte le catalogue, la traçabilité et les labels.',
]" />

<x-admin.help-card :rules="[
    'Nom' => 'obligatoire, 2 à 100 caractères.',
    'E-mail' => 'adresse valide et unique.',
    'Mot de passe' => '8 caractères min., lettres et chiffres.',
]" />
