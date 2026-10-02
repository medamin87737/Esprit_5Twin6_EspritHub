<x-admin.help-card parent="Empreinte" child="Indicateur" :rules="[
    'Empreinte' => 'doit exister.',
    'Étape' => 'facultative, mais du même lot que l\'empreinte.',
    'Type' => 'eau, énergie, transport ou emballage.',
    'Valeur' => 'nombre positif.',
    'Unité' => 'cohérente avec le type : eau en L, énergie en kWh, transport en km, emballage en kg.',
]" />

<x-admin.help-card title="Relations de l'indicateur" icon="bi-diagram-2" :rules="[
    'Empreinte → Indicateurs' => 'chaque indicateur détaille une empreinte carbone.',
    'Étape → Indicateurs' => 'un indicateur peut être mesuré sur une étape du parcours (Module 3) ; si l\'étape est supprimée, l\'indicateur est conservé sans étape.',
]" />
