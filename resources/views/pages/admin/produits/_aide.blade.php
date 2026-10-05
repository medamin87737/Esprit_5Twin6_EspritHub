<x-admin.help-card parent="Catégorie" child="Produit" :rules="[
    'Nom' => 'obligatoire, 150 caractères max.',
    'Code-barres' => 'exactement 13 chiffres, unique.',
    'Catégorie' => 'doit exister dans la liste.',
    'Propriétaire' => 'facultatif, compte producteur ou transformateur.',
    'Origine' => 'obligatoire.',
    'Image' => 'facultative, image de 2 Mo max.',
]" />

<x-admin.help-card title="Qui gère ce produit ?" icon="bi-people" parent="Producteur / transformateur" child="Produit" :rules="[
    'Producteur' => 'gère uniquement ses propres produits et crée les lots.',
    'Transformateur' => 'gère uniquement ses propres produits transformés.',
    'Administrateur' => 'gère tout le catalogue et attribue un produit à son propriétaire.',
]" />
