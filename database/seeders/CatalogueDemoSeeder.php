<?php

namespace Database\Seeders;

use App\Models\Analyse;
use App\Models\Categorie;
use App\Models\Certification;
use App\Models\Laboratoire;
use App\Models\Lot;
use App\Models\Organisme;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Catalogue de démonstration professionnel (présentation devant jury).
 *
 * Idempotent : relançable sans doublons (updateOrCreate partout).
 * Chaque produit reçoit : fiche complète + code-barres EAN-13 valide,
 * 2 lots tracés, des analyses de laboratoire AVEC rapport téléchargeable
 * et synthèse, et au moins une certification valide avec document.
 */
class CatalogueDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->nettoyerProduitsDeTest();

        $labos = $this->laboratoires();
        $admin = User::firstWhere('role', 'admin');
        $disque = Storage::disk(Analyse::DISQUE);

        foreach ($this->catalogue() as $i => $def) {
            [$nom, $catNom, $catType, $origine, $description, $composition, $conservationJours, $types, $certType] = $def;

            $categorie = Categorie::updateOrCreate(
                ['nom' => $catNom],
                ['type' => $catType, 'description' => 'Catalogue NutriTrace — '.$catNom]
            );

            $image = $this->imagePour($nom);
            $produit = Produit::updateOrCreate(
                ['nom' => $nom],
                [
                    'categorie_id' => $categorie->id,
                    'origine' => $origine,
                    'description' => $description,
                    'composition' => $composition,
                    'code_barres' => $this->ean13(619000000000 + ($i + 1) * 137),
                ] + ($image ? ['image' => $image] : [])
            );

            // ── 2 lots par produit ────────────────────────────────────────
            foreach ([1, 2] as $rang) {
                $seq = ($i * 2) + $rang;
                $production = today()->subDays($rang === 1 ? 35 + ($i % 10) : 8);
                $lot = Lot::updateOrCreate(
                    ['numero_lot' => 'LOT-2026-'.str_pad((string) (1000 + $seq), 4, '0', STR_PAD_LEFT)],
                    [
                        'produit_id' => $produit->id,
                        'quantite' => 800 + (($i * 173 + $rang * 311) % 3200),
                        'date_production' => $production,
                        'date_peremption' => $production->copy()->addDays($conservationJours),
                    ]
                );

                // ── 2 analyses par lot, rapport + synthèse pour chacune ──
                foreach ([0, 1] as $k) {
                    $type = $types[($k + $rang - 1) % count($types)];
                    $numero = 'ANA-2026-'.str_pad((string) (1000 + ($seq * 2) + $k), 4, '0', STR_PAD_LEFT);
                    $labo = $labos[($seq + $k) % count($labos)];

                    $resultat = 'conforme';
                    if ($nom === 'Couscous moyen 1 kg' && $rang === 1 && $type === 'mycotoxines') {
                        $resultat = 'non_conforme'; // cas pédagogique n°1
                    }
                    if ($nom === 'Tomates de plein champ' && $rang === 2 && $type === 'pesticides') {
                        $resultat = 'non_conforme'; // cas pédagogique n°2
                    }
                    if ($rang === 2 && $k === 1) {
                        $resultat = 'en_attente'; // analyse récente, résultat à venir
                    }

                    $prelevement = $production->copy()->addDays(2 + $k)->min(today()->subDay());
                    $analyse = Analyse::updateOrCreate(
                        ['numero' => $numero],
                        [
                            'lot_id' => $lot->id,
                            'laboratoire_id' => $labo->id,
                            'type' => $type,
                            'date_prelevement' => $prelevement,
                            'date_resultat' => $resultat === 'en_attente' ? null : $prelevement->copy()->addDays(3)->min(today()),
                            'resultat' => $resultat,
                            'commentaire' => $this->commentaire($type, $resultat),
                            'resume_ia' => $resultat === 'en_attente' ? null : $this->resume($nom, $lot->numero_lot, $type, $resultat, $labo->nom),
                            'ia_genere_at' => $resultat === 'en_attente' ? null : now(),
                        ]
                    );
                    if ($admin) {
                        $analyse->declarant()->associate($admin);
                    }

                    if ($resultat !== 'en_attente') {
                        $chemin = 'analyses/rapport-'.$numero.'.html';
                        $disque->put($chemin, $this->rapportHtml($analyse->fresh(['lot.produit', 'laboratoire']), $nom, $lot->numero_lot, $labo));
                        $analyse->rapport = $chemin;
                    }
                    $analyse->save();
                }
            }

            $this->certification($produit, $certType, $i, $disque);
            $this->completerExistant($produit, $disque);
        }
    }

    /**
     * Rattrapage : toute analyse terminée du produit doit avoir un rapport
     * téléchargeable et une synthèse, toute certification un document,
     * y compris les données créées par les anciens seeders.
     */
    private function completerExistant(Produit $produit, $disque): void
    {
        foreach ($produit->lots()->with('analyses.laboratoire')->get() as $lot) {
            foreach ($lot->analyses as $analyse) {
                if ($analyse->resultat === 'en_attente' || ! $analyse->laboratoire) {
                    continue;
                }
                if (! $analyse->resume_ia) {
                    $analyse->resume_ia = $this->resume($produit->nom, $lot->numero_lot, $analyse->type, $analyse->resultat, $analyse->laboratoire->nom);
                    $analyse->ia_genere_at = now();
                }
                if (! $analyse->rapport || ! $disque->exists($analyse->rapport)) {
                    $chemin = 'analyses/rapport-'.$analyse->numero.'.html';
                    $disque->put($chemin, $this->rapportHtml($analyse, $produit->nom, $lot->numero_lot, $analyse->laboratoire));
                    $analyse->rapport = $chemin;
                }
                $analyse->save();
            }
        }

        foreach ($produit->certifications()->with('organisme')->get() as $certification) {
            if (! $certification->document_pdf || ! $disque->exists($certification->document_pdf)) {
                $chemin = 'certifications/documents/certificat-'.$certification->numero.'.txt';
                $disque->put($chemin, $this->certificatTexte($certification->numero, $certification->type, $produit, $certification->organisme?->nom ?? 'Organisme certificateur'));
                $certification->update(['document_pdf' => $chemin]);
            }
        }
    }

    /** Image professionnelle du produit (si le fichier a été déposé dans storage/app/public/produits). */
    private function imagePour(string $nom): ?string
    {
        $slug = [
            'Dattes Deglet Nour' => 'dattes-deglet-nour',
            'Oranges Maltaises' => 'oranges-maltaises',
            'Grenades' => 'grenades',
            'Tomates de plein champ' => 'tomates-plein-champ',
            'Piments verts' => 'piments-verts',
            'Pommes de terre' => 'pommes-de-terre',
            'Yaourt nature' => 'yaourt-nature',
            'Lait demi-écrémé UHT 1 L' => 'lait-uht',
            'Fromage frais' => 'fromage-frais',
            'Couscous moyen 1 kg' => 'couscous',
            'Pâtes spaghetti 500 g' => 'spaghetti',
            'Farine de blé' => 'farine-de-ble',
            'Huile d\'olive extra vierge 1 L' => 'huile-olive',
            'Harissa traditionnelle' => 'harissa',
            'Câpres au vinaigre' => 'capres',
            'Eau minérale naturelle 1,5 L' => 'eau-minerale',
            'Jus d\'orange 1 L' => 'jus-orange',
            'Citronnade' => 'citronnade',
            'Boga cidre' => 'boga-cidre',
            'Miel d\'eucalyptus 250 g' => 'miel',
            'Confiture de figues 220 g' => 'confiture-figues',
            'Thon à l\'huile d\'olive 160 g' => 'thon',
        ][$nom] ?? null;

        if ($slug === null) {
            return null;
        }

        $chemin = 'produits/'.$slug.'.webp';

        return Storage::disk('public')->exists($chemin) ? $chemin : null;
    }

    /** Supprime les produits de test manuel qui polluent la démo. */
    private function nettoyerProduitsDeTest(): void
    {
        $jetables = Produit::whereIn('nom', ['tofzeh', 'tomates', 'xvxv', 'pomme rougetttt', 'pomme de terre', "jus d'orange"])
            ->where('code_barres', 'not like', '619%')
            ->get();

        foreach ($jetables as $produit) {
            foreach ($produit->lots as $lot) {
                $lot->analyses->each->supprimerRapport();
                $lot->analyses()->delete();
                $lot->empreinteCarbone()->delete();
                $lot->etapes()->delete();
                $lot->delete();
            }
            foreach ($produit->certifications as $certification) {
                if ($certification->document_pdf) {
                    Storage::disk(Certification::DISQUE)->delete($certification->document_pdf);
                }
                $certification->delete();
            }
            $produit->acteurs()->detach();
            $produit->delete();
        }
    }

    /** @return list<Laboratoire> */
    private function laboratoires(): array
    {
        $defs = [
            ['Laboratoire Central d\'Analyses et d\'Essais', 'Tunis', 36.8065, 10.1815, 'TUNAC ISO/IEC 17025 n° 1-0012', 'contact@lcae.tn', '+216 71 236 100'],
            ['Institut Pasteur de Tunis — Hygiène alimentaire', 'Tunis', 36.8110, 10.1780, 'TUNAC ISO/IEC 17025 n° 1-0004', 'hygiene@pasteur.tn', '+216 71 783 022'],
            ['Laboratoire Régional d\'Hygiène de Sfax', 'Sfax', 34.7406, 10.7603, 'TUNAC ISO/IEC 17025 n° 1-0057', 'lrh.sfax@sante.tn', '+216 74 241 511'],
            ['Agrolab Sahel', 'Sousse', 35.8256, 10.6084, 'TUNAC ISO/IEC 17025 n° 1-0089', 'analyses@agrolab-sahel.tn', '+216 73 225 940'],
        ];

        return collect($defs)->map(fn (array $l) => Laboratoire::updateOrCreate(
            ['nom' => $l[0]],
            ['ville' => $l[1], 'pays' => 'Tunisie', 'latitude' => $l[2], 'longitude' => $l[3], 'accreditation' => $l[4], 'email' => $l[5], 'telephone' => $l[6]]
        ))->all();
    }

    private function certification(Produit $produit, string $type, int $index, $disque): void
    {
        $organisme = Organisme::updateOrCreate(
            ['nom' => match ($type) {
                'bio' => 'CTAB — Centre Technique de l\'Agriculture Biologique',
                'equitable' => 'FLOCERT',
                default => 'Bureau Veritas Tunisie',
            }],
            ['pays' => 'Tunisie', 'site_web' => 'https://nutritrace.tn', 'accreditation' => 'Organisme de certification agréé']
        );

        $numero = 'CERT-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
        $chemin = 'certifications/documents/certificat-'.$numero.'.txt';
        $disque->put($chemin, $this->certificatTexte($numero, $type, $produit, $organisme->nom));

        Certification::updateOrCreate(
            ['numero' => $numero],
            [
                'organisme_id' => $organisme->id,
                'produit_id' => $produit->id,
                'type' => $type,
                'statut' => 'valide',
                'date_obtention' => today()->subMonths(4 + ($index % 6)),
                'date_expiration' => today()->addMonths(14 + ($index % 8)),
                'document_pdf' => $chemin,
            ]
        );
    }

    private function commentaire(string $type, string $resultat): ?string
    {
        if ($resultat === 'en_attente') {
            return null;
        }
        if ($resultat === 'non_conforme') {
            return 'Un paramètre dépasse la limite réglementaire : lot bloqué en attendant la contre-expertise.';
        }

        return match ($type) {
            'microbiologique' => 'Aucun germe pathogène détecté ; flore totale dans les limites.',
            'pesticides' => 'Résidus quantifiés inférieurs aux limites maximales autorisées (LMR).',
            'metaux_lourds' => 'Plomb, cadmium et mercure sous les seuils réglementaires.',
            'mycotoxines' => 'Aflatoxines et ochratoxine A inférieures aux limites en vigueur.',
            default => 'Paramètres conformes au référentiel.',
        };
    }

    private function resume(string $produit, string $lot, string $type, string $resultat, string $labo): string
    {
        $label = config('nutritrace.options.analyse_types')[$type] ?? $type;
        $verdict = $resultat === 'conforme'
            ? "l'analyse {$label} du lot {$lot} (« {$produit} ») est conforme aux limites réglementaires."
            : "l'analyse {$label} du lot {$lot} (« {$produit} ») est NON conforme : le lot est bloqué et ne doit pas être consommé en l'état.";

        return "Verdict : {$verdict}\n"
            ."Points confirmés : contrôle réalisé par {$labo} (laboratoire accrédité), prélèvement tracé sur le lot {$lot}, paramètres mesurés dans le rapport joint.\n"
            .($resultat === 'conforme'
                ? "Points de vigilance : aucun dépassement détecté sur cette analyse ; rester attentif aux conditions de stockage et à la date de péremption du lot.\n"
                : "Points de vigilance : dépassement confirmé sur au moins un paramètre ; ne pas mettre le lot en vente et attendre la contre-expertise du laboratoire.\n")
            .($resultat === 'conforme'
                ? "Recommandation : le produit peut être consommé normalement en respectant la date de péremption et les conseils de conservation."
                : "Recommandation : écarter ce lot, privilégier un autre lot du même produit dont les analyses sont conformes.");
    }

    /** @return list<array{0: string, 1: string, 2: string}> [paramètre, mesure, limite] */
    private function parametres(string $type, string $resultat): array
    {
        $ko = $resultat === 'non_conforme';

        return match ($type) {
            'microbiologique' => [
                ['Salmonella spp. (25 g)', 'Absence', 'Absence'],
                ['Listeria monocytogenes (25 g)', 'Absence', 'Absence'],
                ['Escherichia coli', $ko ? '480 UFC/g' : '< 10 UFC/g', '≤ 100 UFC/g'],
                ['Flore mésophile totale', $ko ? '9,2 × 10⁵ UFC/g' : '3,1 × 10³ UFC/g', '≤ 10⁵ UFC/g'],
            ],
            'pesticides' => [
                ['Glyphosate', $ko ? '0,42 mg/kg' : '0,02 mg/kg', '≤ 0,10 mg/kg (LMR)'],
                ['Chlorpyrifos', '< 0,01 mg/kg', '≤ 0,01 mg/kg (LMR)'],
                ['Cyperméthrine', $ko ? '0,18 mg/kg' : '0,03 mg/kg', '≤ 0,05 mg/kg (LMR)'],
                ['Imidaclopride', '0,01 mg/kg', '≤ 0,05 mg/kg (LMR)'],
            ],
            'metaux_lourds' => [
                ['Plomb (Pb)', $ko ? '0,34 mg/kg' : '0,05 mg/kg', '≤ 0,20 mg/kg'],
                ['Cadmium (Cd)', '0,02 mg/kg', '≤ 0,05 mg/kg'],
                ['Mercure (Hg)', $ko ? '0,09 mg/kg' : '0,01 mg/kg', '≤ 0,05 mg/kg'],
                ['Arsenic inorganique', '0,03 mg/kg', '≤ 0,10 mg/kg'],
            ],
            default => [
                ['Aflatoxine B1', $ko ? '2,6 µg/kg' : '0,7 µg/kg', '≤ 2,0 µg/kg'],
                ['Aflatoxines totales (B1+B2+G1+G2)', $ko ? '4,9 µg/kg' : '1,3 µg/kg', '≤ 4,0 µg/kg'],
                ['Ochratoxine A', '0,9 µg/kg', '≤ 3,0 µg/kg'],
                ['Déoxynivalénol (DON)', '210 µg/kg', '≤ 750 µg/kg'],
            ],
        };
    }

    private function rapportHtml(Analyse $analyse, string $produit, string $lotNumero, Laboratoire $labo): string
    {
        $conforme = $analyse->resultat === 'conforme';
        $classe = $conforme ? 'conforme' : 'non';
        $verdict = $conforme ? '✓ CONFORME' : '✕ NON CONFORME';
        $lignes = collect($this->parametres($analyse->type, $analyse->resultat))
            ->map(fn (array $r) => "<tr><td>{$r[0]}</td><td><strong>{$r[1]}</strong></td><td>{$r[2]}</td></tr>")
            ->implode('');
        $typeLabel = $analyse->typeLabel();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr"><head><meta charset="utf-8">
        <title>Rapport {$analyse->numero} — NutriTrace</title>
        <style>
        body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#eef3f0;color:#202b28}
        .page{max-width:820px;margin:32px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 18px 50px rgba(6,78,53,.18)}
        header{background:linear-gradient(120deg,#064e35,#0a8f52);color:#fff;padding:28px 36px}
        header .m{font-size:.72rem;letter-spacing:.14em;font-weight:800;text-transform:uppercase;color:#ffd97a}
        header h1{margin:6px 0 2px;font-size:1.55rem} header p{margin:0;color:#c8f0de}
        .v{margin:24px 36px 0;border-radius:12px;padding:16px 20px;font-size:1.05rem}
        .v.conforme{background:#e5f6ec;color:#0b5d33;border:1px solid #b5e3c8}
        .v.non{background:#fdecec;color:#8c1d18;border:1px solid #f5c2c0}
        main{padding:8px 36px 10px} h2{font-size:1.02rem;color:#064e35;margin:22px 0 8px;border-bottom:2px solid #e5f6ec;padding-bottom:5px}
        table{width:100%;border-collapse:collapse} td,th{padding:7px 6px;border-bottom:1px solid #eef2ef;font-size:.93rem;text-align:left}
        td.k{color:#65756a;width:38%} footer{padding:18px 36px 26px;color:#8a978d;font-size:.8rem;display:flex;justify-content:space-between}
        </style></head><body><div class="page">
        <header><div class="m">NutriTrace · Rapport d'analyse laboratoire</div>
        <h1>{$typeLabel} — {$analyse->numero}</h1><p>{$produit} · Lot {$lotNumero}</p></header>
        <div class="v {$classe}"><strong>Résultat : {$verdict}</strong></div>
        <main>
        <h2>Informations</h2><table>
        <tr><td class="k">Laboratoire</td><td>{$labo->nom} ({$labo->ville}) — {$labo->accreditation}</td></tr>
        <tr><td class="k">Date de prélèvement</td><td>{$analyse->date_prelevement?->format('d/m/Y')}</td></tr>
        <tr><td class="k">Date du résultat</td><td>{$analyse->date_resultat?->format('d/m/Y')}</td></tr>
        <tr><td class="k">Commentaire</td><td>{$analyse->commentaire}</td></tr></table>
        <h2>Paramètres mesurés</h2><table>
        <tr><th>Paramètre</th><th>Résultat mesuré</th><th>Limite réglementaire</th></tr>{$lignes}</table>
        </main>
        <footer><span>Document officiel généré par NutriTrace le {$analyse->date_resultat?->format('d/m/Y')}</span><span>{$analyse->numero}</span></footer>
        </div></body></html>
        HTML;
    }

    private function certificatTexte(string $numero, string $type, Produit $produit, string $organisme): string
    {
        $label = config('nutritrace.options.certification_types')[$type] ?? $type;
        $expiration = today()->addMonths(18)->format('d/m/Y');

        return "CERTIFICAT {$label} — NutriTrace\n"
            ."================================\n"
            ."Numéro : {$numero}\n"
            ."Organisme certificateur : {$organisme}\n"
            ."Produit : {$produit->nom}\n"
            ."Origine : {$produit->origine}\n"
            ."Type de certification : {$label}\n"
            ."Statut : Valide\n"
            ."Valable jusqu'au : {$expiration}\n\n"
            ."Ce certificat atteste que le produit ci-dessus a été contrôlé et "
            ."jugé conforme au référentiel « {$label} » par l'organisme susmentionné.";
    }

    /** EAN-13 valide (chiffre de contrôle calculé) à partir d'une base de 12 chiffres. */
    private function ean13(int $base12): string
    {
        $douze = str_pad(substr((string) $base12, 0, 12), 12, '0', STR_PAD_LEFT);
        $somme = 0;
        for ($pos = 0; $pos < 12; $pos++) {
            $somme += ((int) $douze[$pos]) * ($pos % 2 === 0 ? 1 : 3);
        }

        return $douze.((10 - ($somme % 10)) % 10);
    }

    /**
     * [nom, catégorie, type cat., origine, description, composition, conservation (jours), types d'analyses, certification]
     *
     * @return list<array>
     */
    private function catalogue(): array
    {
        $micro = 'microbiologique';
        $pest = 'pesticides';
        $metaux = 'metaux_lourds';
        $myco = 'mycotoxines';

        return [
            ['Dattes Deglet Nour', 'Fruits de saison', 'fruits', 'Tozeur, Tunisie', 'Dattes Deglet Nour de la palmeraie de Tozeur, séchées naturellement au soleil, sans sucre ajouté ni conservateurs.', 'Dattes Deglet Nour 100 % naturelles (sucres naturels du fruit).', 180, [$pest, $micro], 'bio'],
            ['Oranges Maltaises', 'Fruits de saison', 'fruits', 'Nabeul (Cap Bon), Tunisie', 'Oranges maltaises demi-sanguines du Cap Bon, récoltées à maturité et calibrées.', 'Oranges fraîches entières, non traitées après récolte.', 30, [$pest, $micro], 'local'],
            ['Grenades', 'Fruits de saison', 'fruits', 'Gabès, Tunisie', 'Grenades de Gabès à grains tendres et jus abondant, récoltées à pleine maturité.', 'Grenades fraîches entières.', 45, [$pest, $metaux], 'bio'],
            ['Tomates de plein champ', 'Légumes frais', 'legumes', 'Kairouan, Tunisie', 'Tomates de plein champ cultivées en rotation culturale, cueillies à coloration complète.', 'Tomates fraîches entières.', 20, [$pest, $micro], 'local'],
            ['Piments verts', 'Légumes frais', 'legumes', 'Sidi Bouzid, Tunisie', 'Piments verts frais, fermes et brillants, récoltés le matin même de l\'expédition.', 'Piments verts frais entiers.', 15, [$pest, $micro], 'local'],
            ['Pommes de terre', 'Légumes frais', 'legumes', 'Jendouba, Tunisie', 'Pommes de terre de consommation (variété Spunta), calibre homogène, non germées.', 'Pommes de terre fraîches, terre adhérente retirée.', 60, [$pest, $metaux], 'local'],
            ['Yaourt nature', 'Produits laitiers', 'laitiers', 'Béja, Tunisie', 'Yaourt nature brassé au lait entier pasteurisé de collectes locales, sans arômes ni colorants.', 'Lait entier pasteurisé, ferments lactiques (Streptococcus thermophilus, Lactobacillus bulgaricus).', 21, [$micro, $pest], 'local'],
            ['Lait demi-écrémé UHT 1 L', 'Produits laitiers', 'laitiers', 'Mateur, Tunisie', 'Lait de vache demi-écrémé stérilisé UHT, source de calcium et de vitamine D.', 'Lait de vache demi-écrémé (1,5 % MG), stérilisé UHT.', 90, [$micro, $metaux], 'local'],
            ['Fromage frais', 'Produits laitiers', 'laitiers', 'Bizerte, Tunisie', 'Fromage frais à tartiner au lait pasteurisé, texture fondante, goût lactique doux.', 'Lait pasteurisé, crème, sel, présure, ferments lactiques.', 30, [$micro, $metaux], 'local'],
            ['Couscous moyen 1 kg', 'Céréales et dérivés', 'cereales', 'Sfax, Tunisie', 'Couscous moyen précuit à la vapeur, grain régulier, séché lentement pour une cuisson homogène.', 'Semoule de blé dur 100 %, eau.', 365, [$myco, $pest], 'local'],
            ['Pâtes spaghetti 500 g', 'Céréales et dérivés', 'cereales', 'Sousse, Tunisie', 'Spaghetti de semoule de blé dur, tréfilés au bronze, séchage lent basse température.', 'Semoule de blé dur, eau.', 540, [$myco, $metaux], 'local'],
            ['Farine de blé', 'Céréales et dérivés', 'cereales', 'Béja, Tunisie', 'Farine de blé tendre type 55, mouture sur cylindres, pour pains et pâtisseries.', 'Farine de blé tendre T55.', 365, [$myco, $pest], 'local'],
            ['Huile d\'olive extra vierge 1 L', 'Huiles et condiments', 'autres', 'Sfax, Tunisie', 'Huile d\'olive extra vierge première pression à froid, acidité inférieure à 0,5 %, notes d\'amande et d\'herbe fraîche.', 'Huile d\'olive extra vierge 100 % (olives Chemlali).', 540, [$pest, $metaux], 'bio'],
            ['Harissa traditionnelle', 'Huiles et condiments', 'autres', 'Nabeul (Cap Bon), Tunisie', 'Harissa traditionnelle du Cap Bon, piments rouges séchés et broyés à la meule, relevée à l\'ail et à la coriandre.', 'Piments rouges (85 %), ail, sel, huile d\'olive, coriandre, carvi.', 365, [$micro, $pest], 'local'],
            ['Câpres au vinaigre', 'Huiles et condiments', 'autres', 'Kairouan, Tunisie', 'Câpres fines conservées au vinaigre, croquantes, pour assaisonnements et sauces.', 'Câpres, vinaigre de vin, eau, sel.', 365, [$metaux, $micro], 'local'],
            ['Eau minérale naturelle 1,5 L', 'Boissons', 'boissons', 'Zaghouan, Tunisie', 'Eau minérale naturelle faiblement minéralisée, captée à la source de Zaghouan, adaptée à l\'hydratation quotidienne.', 'Eau minérale naturelle (calcium 78 mg/L, magnésium 24 mg/L, bicarbonates 310 mg/L).', 365, [$micro, $metaux], 'local'],
            ['Jus d\'orange 1 L', 'Boissons', 'boissons', 'Nabeul (Cap Bon), Tunisie', 'Jus d\'orange à base de concentré, sans colorants, source de vitamine C.', 'Jus d\'orange à base de concentré, eau, vitamine C.', 270, [$micro, $pest], 'local'],
            ['Citronnade', 'Boissons', 'boissons', 'Tunis, Tunisie', 'Citronnade artisanale au jus de citron frais pressé, sucrée modérément, à servir très fraîche.', 'Eau, jus de citron (12 %), sucre, zeste de citron.', 90, [$micro, $metaux], 'local'],
            ['Boga cidre', 'Boissons', 'boissons', 'Tunis, Tunisie', 'Boisson gazeuse aromatisée au goût cidre, pétillante, à consommer fraîche et avec modération (boisson sucrée).', 'Eau gazéifiée, sucre, acidifiant : acide citrique, arômes, colorant : caramel.', 270, [$micro, $metaux], 'local'],
            ['Miel d\'eucalyptus 250 g', 'Douceurs et terroir', 'terroir', 'Aïn Draham, Tunisie', 'Miel d\'eucalyptus récolté dans les forêts de Kroumirie, non chauffé, filtré à froid.', 'Miel d\'eucalyptus 100 % pur et naturel.', 720, [$micro, $pest], 'bio'],
            ['Confiture de figues 220 g', 'Douceurs et terroir', 'terroir', 'Testour, Tunisie', 'Confiture de figues artisanale, fruits cuits au chaudron avec une teneur réduite en sucre.', 'Figues (65 %), sucre, jus de citron.', 365, [$micro, $metaux], 'bio'],
            ['Thon à l\'huile d\'olive 160 g', 'Conserves', 'conserves', 'Bizerte, Tunisie', 'Thon entier conservé à l\'huile d\'olive, pêche saisonnière méditerranéenne, chair ferme.', 'Thon, huile d\'olive (30 %), sel.', 720, [$metaux, $micro], 'equitable'],
        ];
    }
}
