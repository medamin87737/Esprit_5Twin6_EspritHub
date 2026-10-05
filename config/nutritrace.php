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
            'titre' => 'Analyses qualité',
            'icone' => 'bi-clipboard2-pulse',
            'responsable' => 'Amin',
            'entites' => [
                ['libelle' => 'Laboratoires', 'route' => 'laboratoires', 'table' => 'laboratoires', 'icone' => 'bi-building-check'],
                ['libelle' => 'Analyses', 'route' => 'analyses', 'table' => 'analyses', 'icone' => 'bi-clipboard2-check'],
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
            'responsable' => 'Sahar',
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
            'en_attente' => 'En attente',
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
        'analyse_types' => [
            'microbiologique' => 'Microbiologique',
            'pesticides' => 'Résidus de pesticides',
            'metaux_lourds' => 'Métaux lourds',
            'mycotoxines' => 'Mycotoxines',
        ],
        'analyse_resultats' => [
            'en_attente' => 'En attente',
            'conforme' => 'Conforme',
            'non_conforme' => 'Non conforme',
        ],
        'signalement_motifs' => [
            'information_trompeuse' => 'Information trompeuse',
            'label_douteux' => 'Label ou certification douteux',
            'origine_incorrecte' => 'Origine incorrecte',
            'qualite' => 'Problème de qualité',
            'autre' => 'Autre',
        ],
        'signalement_statuts' => [
            'en_attente' => 'En attente',
            'valide' => 'Validé',
            'rejete' => 'Rejeté',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recalcul de l'empreinte carbone (kg CO₂e par unité d'indicateur)
    |--------------------------------------------------------------------------
    |
    | Chaque indicateur saisi par un professionnel ajoute
    | valeur × facteur / quantité du lot au CO₂ par unité du lot.
    |
    */

    'facteurs_co2' => [
        'eau' => 0.0003,
        'energie' => 0.47,
        'transport' => 0.9,
        'emballage' => 2.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comparaison de produits : nombre maximal de produits
    |--------------------------------------------------------------------------
    */

    'comparaison' => [
        'visiteur' => 2,
        'connecte' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Espace professionnel (Front Office, routes /pro)
    |--------------------------------------------------------------------------
    |
    | Rôles ayant accès à l'espace pro et types d'étape que chacun peut
    | ajouter au parcours d'un lot (clés de options.etape_types).
    |
    */

    'roles_pro' => [
        'producteur' => [
            'etapes' => ['production'],
            'gere_produits' => true,
            'cree_lots' => true,
        ],
        'transformateur' => [
            'etapes' => ['transformation'],
            'gere_produits' => true,
            'cree_lots' => true,
        ],
        'distributeur' => [
            'etapes' => ['distribution', 'vente'],
            'gere_produits' => false,
            'cree_lots' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menus du Front Office par profil
    |--------------------------------------------------------------------------
    |
    | Utilisés par la barre de navigation et le pied de page (App\Support\MenuFront).
    | « droit » : Gate à respecter pour afficher le lien (null = toujours).
    | « actif » : motifs de noms de routes qui surlignent le lien.
    |
    */

    'menus' => [
        'visiteur' => [
            ['route' => 'front.produits.index', 'actif' => ['front.produits.*'], 'libelle' => 'Catalogue', 'icone' => 'bi-basket2', 'droit' => null],
            ['route' => 'front.lots.search', 'actif' => ['front.lots.*'], 'libelle' => 'Tracer un lot', 'icone' => 'bi-upc-scan', 'droit' => null],
            ['route' => 'front.acteurs.index', 'actif' => ['front.acteurs.*'], 'libelle' => 'Acteurs', 'icone' => 'bi-people', 'droit' => null],
            ['route' => 'front.comparaison', 'actif' => ['front.comparaison'], 'libelle' => 'Comparer', 'icone' => 'bi-bar-chart', 'droit' => null],
            ['route' => 'front.certifications.index', 'actif' => ['front.certifications.*'], 'libelle' => 'Labels', 'icone' => 'bi-award', 'droit' => null],
        ],
        'consommateur' => [
            ['route' => 'front.produits.index', 'actif' => ['front.produits.*'], 'libelle' => 'Catalogue', 'icone' => 'bi-basket2', 'droit' => null],
            ['route' => 'front.lots.search', 'actif' => ['front.lots.*'], 'libelle' => 'Scanner un lot', 'icone' => 'bi-upc-scan', 'droit' => null],
            ['route' => 'front.acteurs.index', 'actif' => ['front.acteurs.*'], 'libelle' => 'Acteurs', 'icone' => 'bi-people', 'droit' => null],
            ['route' => 'front.comparaison', 'actif' => ['front.comparaison'], 'libelle' => 'Comparer', 'icone' => 'bi-bar-chart', 'droit' => null],
            ['route' => 'front.certifications.index', 'actif' => ['front.certifications.*'], 'libelle' => 'Labels', 'icone' => 'bi-award', 'droit' => null],
            ['route' => 'consommateur.signalements.index', 'actif' => ['consommateur.signalements.*'], 'libelle' => 'Mes signalements', 'icone' => 'bi-flag', 'droit' => 'espace-consommateur'],
        ],
        'pro' => [
            ['route' => 'pro.dashboard', 'actif' => ['pro.dashboard'], 'libelle' => 'Tableau de bord', 'icone' => 'bi-speedometer2', 'droit' => 'espace-pro'],
            ['route' => 'pro.produits.index', 'actif' => ['pro.produits.*'], 'libelle' => 'Mes produits', 'icone' => 'bi-box-seam', 'droit' => 'pro-produits'],
            ['route' => 'pro.lots.index', 'actif' => ['pro.lots.*', 'pro.etapes.*', 'pro.indicateurs.*'], 'libelle' => 'Mes lots', 'icone' => 'bi-upc-scan', 'droit' => 'espace-pro'],
            ['route' => 'pro.certifications.index', 'actif' => ['pro.certifications.*'], 'libelle' => 'Certifications', 'icone' => 'bi-award', 'droit' => 'pro-produits'],
            ['route' => 'pro.signalements.index', 'actif' => ['pro.signalements.*'], 'libelle' => 'Signalements', 'icone' => 'bi-flag', 'droit' => 'pro-produits'],
        ],
    ],

];
