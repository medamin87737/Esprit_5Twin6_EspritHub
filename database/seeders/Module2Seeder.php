<?php

namespace Database\Seeders;

use App\Models\Analyse;
use App\Models\Laboratoire;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Database\Seeder;

class Module2Seeder extends Seeder
{
    /**
     * Laboratoires et analyses qualité des lots (Module 2 — Amin).
     * Les lots viennent du Module 3 : lancer Module3Seeder avant.
     */
    public function run(): void
    {
        $laboratoires = collect([
            ['Laboratoire Central d\'Analyses et d\'Essais', 'Tunis', 'TUNAC ISO/IEC 17025 n° 1-0012', 'contact@lcae.tn', '+216 71 236 100'],
            ['Institut Pasteur de Tunis — Hygiène alimentaire', 'Tunis', 'TUNAC ISO/IEC 17025 n° 1-0004', 'hygiene@pasteur.tn', '+216 71 783 022'],
            ['Laboratoire Régional d\'Hygiène de Sfax', 'Sfax', 'TUNAC ISO/IEC 17025 n° 1-0057', 'lrh.sfax@sante.tn', '+216 74 241 511'],
            ['Agrolab Sahel', 'Sousse', 'TUNAC ISO/IEC 17025 n° 1-0089', 'analyses@agrolab-sahel.tn', '+216 73 225 940'],
        ])->map(fn (array $l) => Laboratoire::factory()->create([
            'nom' => $l[0],
            'ville' => $l[1],
            'pays' => 'Tunisie',
            'accreditation' => $l[2],
            'email' => $l[3],
            'telephone' => $l[4],
        ]));

        [$lcae, $pasteur, $sfax, $agrolab] = $laboratoires->all();
        $admin = User::firstWhere('role', 'admin');

        // [lot, laboratoire, type, jours après production, résultat, commentaire]
        $analyses = [
            ['LOT-2026-0001', $pasteur, 'microbiologique', 1, 'conforme', 'Listeria et Salmonella non détectées dans 25 g.'],
            ['LOT-2026-0001', $lcae, 'pesticides', 2, 'conforme', 'Résidus inférieurs aux limites maximales.'],
            ['LOT-2026-0002', $sfax, 'pesticides', 2, 'conforme', 'Aucun résidu quantifiable.'],
            ['LOT-2026-0001', $sfax, 'metaux_lourds', 3, 'conforme', 'Plomb et cadmium sous les seuils réglementaires.'],
            ['LOT-2026-0003', $pasteur, 'microbiologique', 1, 'conforme', 'Flore totale conforme.'],
            ['LOT-2026-0004', $agrolab, 'mycotoxines', 3, 'non_conforme', 'Aflatoxine B1 mesurée à 2,6 µg/kg pour une limite de 2 µg/kg.'],
            ['LOT-2026-0005', $lcae, 'pesticides', 2, 'conforme', null],
            ['LOT-2026-0006', $agrolab, 'pesticides', 2, 'en_attente', null],
        ];

        foreach ($analyses as $index => [$numeroLot, $laboratoire, $type, $jours, $resultat, $commentaire]) {
            $lot = Lot::firstWhere('numero_lot', $numeroLot);

            if (! $lot) {
                continue;
            }

            $prelevement = $lot->date_production->copy()->addDays($jours)->min(today());

            $analyse = Analyse::factory()->for($lot)->for($laboratoire)->make([
                'numero' => 'ANA-'.now()->year.'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'type' => $type,
                'date_prelevement' => $prelevement,
                'date_resultat' => $resultat === 'en_attente' ? null : $prelevement->copy()->addDays(3)->min(today()),
                'resultat' => $resultat,
                'commentaire' => $commentaire,
            ]);
            $analyse->declarant()->associate($admin);
            $analyse->save();
        }
    }
}
