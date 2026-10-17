<x-admin.help-card parent="Empreinte" child="Indicateur" :rules="[
    'Empreinte' => 'doit exister.',
    'Étape' => 'facultative, mais du même lot que l\'empreinte.',
    'Type' => 'eau, énergie, transport ou emballage.',
    'Valeur' => 'nombre positif.',
    'Unité' => 'cohérente avec le type : eau en L, énergie en kWh, transport en km, emballage en kg.',
]" />

<x-admin.help-card title="Facteurs certifiés Climatiq API" icon="bi-cloud-check" :rules="[
    'Climatiq API' => 'calcule automatiquement les émissions selon les normes internationales.',
    'Eau' => 'ADEME Base Empreinte 2026 (0,0003 kg CO₂e / L).',
    'Énergie' => 'Mix électrique réseau IEA / Tunisie (0,4700 kg CO₂e / kWh).',
    'Transport' => 'DEFRA 2026 poids lourd diesel (0,9000 kg CO₂e / km).',
    'Emballage' => 'Ecoinvent 3.10 carton & composite (2,5000 kg CO₂e / kg).',
]" />
