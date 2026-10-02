@php($espace = $espace ?? 'admin')

<x-admin.help-card parent="Catégorie" child="Produit" :rules="array_filter([
    'Nom' => 'obligatoire, 150 caractères max.',
    'Code-barres' => 'exactement 13 chiffres, unique.',
    'Catégorie' => 'doit exister dans la liste.',
    'Fournisseur' => $espace === 'admin' ? 'facultatif, compte de rôle fournisseur.' : null,
    'Origine' => 'obligatoire.',
    'Image' => 'facultative, image de 2 Mo max.',
])" />

<x-admin.help-card title="Qui gère ce produit ?" icon="bi-people" parent="Fournisseur" child="Produit" :rules="[
    'Fournisseur' => 'ajoute, modifie et supprime uniquement ses propres produits.',
    'Administrateur' => 'gère tout le catalogue et peut attribuer un produit à un fournisseur.',
]" />
