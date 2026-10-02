<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Organisme;
use App\Models\Produit;
use Illuminate\Database\Seeder;

class Module5Seeder extends Seeder
{
    /**
     * Organismes certificateurs et labels des produits (Module 5 — Marwa).
     */
    public function run(): void
    {
        $organismes = collect([
            ['CTAB — Centre Technique de l\'Agriculture Biologique', 'Tunisie', 'https://www.ctab.nat.tn', 'Agrément MARHP n° TN-BIO-01'],
            ['Ecocert', 'France', 'https://www.ecocert.com', 'COFRAC n° 7-0001'],
            ['Bureau Veritas Tunisie', 'Tunisie', 'https://www.bureauveritas.tn', 'TUNAC n° 3-CP-12'],
            ['FLOCERT', 'Allemagne', 'https://www.flocert.net', 'DAkkS ISO/IEC 17065'],
        ])->mapWithKeys(fn (array $o) => [$o[0] => Organisme::factory()->create([
            'nom' => $o[0],
            'pays' => $o[1],
            'site_web' => $o[2],
            'accreditation' => $o[3],
        ])]);

        $ctab = $organismes->values()[0];
        $ecocert = $organismes->values()[1];
        $veritas = $organismes->values()[2];
        $flocert = $organismes->values()[3];

        // [produit, organisme, type, numéro, statut, obtention, expiration]
        $labels = [
            ['Huile d\'olive extra vierge 1 L', $ctab, 'bio', 'BIO-TN-2026-001', 'valide', '-14 months', '+22 months'],
            ['Huile d\'olive extra vierge 1 L', $flocert, 'equitable', 'EQU-FL-2025-014', 'valide', '-20 months', '+16 months'],
            ['Dattes Deglet Nour', $ecocert, 'bio', 'BIO-EC-2025-118', 'valide', '-10 months', '+26 months'],
            ['Dattes Deglet Nour', $flocert, 'equitable', 'EQU-FL-2024-087', 'valide', '-34 months', '+20 days'],
            ['Harissa traditionnelle', $veritas, 'local', 'LOC-BV-2026-005', 'valide', '-6 months', '+30 months'],
            ['Oranges Maltaises', $ctab, 'bio', 'BIO-TN-2026-002', 'valide', '-3 months', '+33 months'],
            ['Tomates de plein champ', $veritas, 'local', 'LOC-BV-2026-011', 'valide', '-2 months', '+34 months'],
            ['Couscous moyen 1 kg', $veritas, 'local', 'LOC-BV-2025-031', 'suspendue', '-15 months', '+21 months'],
            ['Yaourt nature', $veritas, 'local', 'LOC-BV-2026-019', 'valide', '-5 months', '+31 months'],
            ['Grenades', $ecocert, 'bio', 'BIO-EC-2023-042', 'expiree', '-40 months', '-4 months'],
            ['Pâtes spaghetti 500 g', $flocert, 'equitable', 'EQU-FL-2025-052', 'valide', '-8 months', '+12 days'],
            ['Câpres au vinaigre', $ctab, 'bio', 'BIO-TN-2024-077', 'expiree', '-38 months', '-2 months'],
        ];

        foreach ($labels as [$produit, $organisme, $type, $numero, $statut, $obtention, $expiration]) {
            $produit = Produit::firstWhere('nom', $produit);

            if (! $produit) {
                continue;
            }

            Certification::factory()->for($organisme)->for($produit)->create([
                'type' => $type,
                'numero' => $numero,
                'statut' => $statut,
                'date_obtention' => today()->modify($obtention),
                'date_expiration' => today()->modify($expiration),
            ]);
        }
    }
}
