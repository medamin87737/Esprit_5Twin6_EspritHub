<x-admin.help-card title="Les rôles" icon="bi-person-gear" :rules="[
    'Administrateur' => 'accès complet au Back Office et aux comptes.',
    'Producteur' => 'gère ses produits, crée les lots et saisit l\'étape de production.',
    'Transformateur' => 'gère ses produits transformés et saisit l\'étape de transformation.',
    'Distributeur' => 'saisit les étapes de distribution et de vente des lots reçus.',
    'Consommateur' => 'consulte le catalogue, scanne les lots et signale un problème.',
]" />

<x-admin.help-card :rules="[
    'Nom' => 'obligatoire, 2 à 100 caractères.',
    'E-mail' => 'adresse valide et unique.',
    'Mot de passe' => '8 caractères min., lettres et chiffres.',
]" />
