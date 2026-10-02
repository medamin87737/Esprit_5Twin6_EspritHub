<x-admin.help-card parent="Organisme" child="Certification" :rules="[
    'Produit et organisme' => 'doivent exister.',
    'Numéro' => 'unique, lettres, chiffres et tirets (mis en majuscules).',
    'Type' => 'bio, local ou équitable — une seule certification valide par type et par produit.',
    'Obtention' => 'aujourd\'hui ou dans le passé.',
    'Expiration' => 'postérieure à la date d\'obtention.',
    'Statut' => '« Valide » interdit si la date d\'expiration est dépassée.',
]">
    <p class="small text-muted mb-0 mt-3"><i class="bi bi-alarm text-danger mr-1" aria-hidden="true"></i>Les certifications qui expirent dans moins de 30 jours sont signalées en rouge.</p>
</x-admin.help-card>

<x-admin.help-card title="Relations" icon="bi-diagram-3" :rules="[
    'Organisme → Certifications' => 'un organisme délivre plusieurs certifications.',
    'Produit → Certifications' => 'un produit (module 1) peut porter plusieurs labels.',
    'Organisme ↔ Produits' => 'la certification relie un organisme à un produit.',
]" />
