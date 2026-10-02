<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules NutriTrace
    |--------------------------------------------------------------------------
    |
    | Source unique du menu latéral du Back Office et du tableau de bord.
    | « route » correspond au préfixe des routes nommées admin.{route}.*
    | et « table » au nom de la table MySQL de l'entité.
    |
    */

    'modules' => [
        [
            'numero' => 1,
            'titre' => 'Produits & Catégories',
            'icone' => 'bi-basket2',
            'responsable' => 'Ghada',
            'entites' => [
                ['libelle' => 'Catégories', 'route' => 'categories', 'table' => 'categories', 'icone' => 'bi-tags'],
                ['libelle' => 'Produits', 'route' => 'produits', 'table' => 'produits', 'icone' => 'bi-box-seam'],
            ],
        ],
        [
            'numero' => 2,
            'titre' => 'Acteurs de la chaîne',
            'icone' => 'bi-people',
            'responsable' => 'Amin',
            'entites' => [
                ['libelle' => 'Types d\'acteurs', 'route' => 'type-acteurs', 'table' => 'type_acteurs', 'icone' => 'bi-diagram-3'],
                ['libelle' => 'Acteurs', 'route' => 'acteurs', 'table' => 'acteurs', 'icone' => 'bi-person-badge'],
            ],
        ],
        [
            'numero' => 3,
            'titre' => 'Traçabilité des lots',
            'icone' => 'bi-signpost-split',
            'responsable' => 'Ons',
            'entites' => [
                ['libelle' => 'Lots', 'route' => 'lots', 'table' => 'lots', 'icone' => 'bi-upc-scan'],
                ['libelle' => 'Étapes', 'route' => 'etapes', 'table' => 'etapes', 'icone' => 'bi-geo-alt'],
            ],
        ],
        [
            'numero' => 4,
            'titre' => 'Empreinte environnementale',
            'icone' => 'bi-globe-europe-africa',
            'responsable' => 'Ons',
            'entites' => [
                ['libelle' => 'Empreintes carbone', 'route' => 'empreintes', 'table' => 'empreinte_carbones', 'icone' => 'bi-cloud-haze2'],
                ['libelle' => 'Indicateurs', 'route' => 'indicateurs', 'table' => 'indicateurs', 'icone' => 'bi-speedometer2'],
            ],
        ],
        [
            'numero' => 5,
            'titre' => 'Certifications',
            'icone' => 'bi-patch-check',
            'responsable' => 'Marwa',
            'entites' => [
                ['libelle' => 'Organismes', 'route' => 'organismes', 'table' => 'organismes', 'icone' => 'bi-bank'],
                ['libelle' => 'Certifications', 'route' => 'certifications', 'table' => 'certifications', 'icone' => 'bi-award'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Listes de valeurs fixes (selects des formulaires et filtres)
    |--------------------------------------------------------------------------
    */

    'options' => [
        'categorie_types' => [
            'fruits' => 'Fruits',
            'legumes' => 'Légumes',
            'laitiers' => 'Produits laitiers',
            'viandes' => 'Viandes',
            'cereales' => 'Céréales',
            'boissons' => 'Boissons',
            'autres' => 'Autres',
        ],
        'etape_types' => [
            'production' => 'Production',
            'transformation' => 'Transformation',
            'distribution' => 'Distribution',
            'vente' => 'Vente',
        ],
        'modes_transport' => [
            'camion' => 'Camion',
            'camion_frigorifique' => 'Camion frigorifique',
            'train' => 'Train',
            'bateau' => 'Bateau',
            'aucun' => 'Aucun',
        ],
        'indicateur_types' => [
            'eau' => 'Eau',
            'energie' => 'Énergie',
            'transport' => 'Transport',
            'emballage' => 'Emballage',
        ],
        'unites' => [
            'L' => 'Litres (L)',
            'kWh' => 'Kilowattheures (kWh)',
            'km' => 'Kilomètres (km)',
            'kg' => 'Kilogrammes (kg)',
        ],
        'certification_types' => [
            'bio' => 'Bio',
            'local' => 'Local',
            'equitable' => 'Équitable',
        ],
        'certification_statuts' => [
            'valide' => 'Valide',
            'expiree' => 'Expirée',
            'suspendue' => 'Suspendue',
        ],
        'scores' => [
            'A' => 'moins de 1 kg CO₂e',
            'B' => 'de 1 à 2 kg CO₂e',
            'C' => 'de 2 à 4 kg CO₂e',
            'D' => 'de 4 à 7 kg CO₂e',
            'E' => '7 kg CO₂e et plus',
        ],
    ],

];
