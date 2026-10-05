<?php

namespace Database\Seeders;

use App\Models\Acteur;
use App\Models\Analyse;
use App\Models\Certification;
use App\Models\Etape;
use App\Models\Laboratoire;
use App\Models\Lot;
use App\Models\Organisme;
use App\Models\Produit;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de démonstration du Front Office : un parcours en cours chez chaque
 * professionnel, des signalements, une déclaration de certification et des scans.
 * Lancer après les seeders des modules 1 à 5.
 */
class FrontOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $this->acteursCourants();

        $ferme = Acteur::firstWhere('nom', 'Ferme El Baraka');
        $laiterie = Acteur::firstWhere('nom', 'Laiterie du Nord');
        $semoulerie = Acteur::firstWhere('nom', 'Semoulerie du Sahel');
        $carthage = Acteur::firstWhere('nom', 'Carthage Frais Distribution');

        if (! $ferme || ! $laiterie || ! $semoulerie || ! $carthage) {
            return;
        }

        // Chez le producteur : production saisie, lot prêt à être transféré.
        $this->lot('LOT-2026-0012', 'Tomates de plein champ', 1500, 1, $ferme, [
            ['production', $ferme, 1, 'aucun', [['eau', 750]]],
        ]);

        // Chez le transformateur : lot de son propre produit, transformation saisie, prêt à être transféré.
        $this->lot('LOT-2026-0013', 'Lait demi-écrémé UHT 1 L', 3000, 3, $laiterie, [
            ['transformation', $laiterie, 2, 'aucun', [['energie', 900]]],
        ]);

        // Chez le distributeur : produit et transformé, distribution et vente à saisir.
        $this->lot('LOT-2026-0014', 'Couscous moyen 1 kg', 200, 6, $carthage, [
            ['production', $ferme, 6, 'aucun', [['eau', 2400]]],
            ['transformation', $semoulerie, 4, 'camion', [['transport', 160], ['energie', 300]]],
        ]);

        $this->signalements();
        $this->declarationEnAttente();
        $this->scans();
        $this->analyseAComplete();
    }

    /**
     * Prélèvement déclaré par la laiterie, résultat à saisir pendant la démo.
     */
    private function analyseAComplete(): void
    {
        $lot = Lot::firstWhere('numero_lot', 'LOT-2026-0013');
        $laboratoire = Laboratoire::firstWhere('ville', 'Tunis');
        $transformateur = User::firstWhere('email', 'transformateur@nutritrace.tn');

        if (! $lot || ! $laboratoire || ! $transformateur || $lot->analyses()->exists()) {
            return;
        }

        $analyse = new Analyse([
            'laboratoire_id' => $laboratoire->id,
            'numero' => Analyse::prochainNumero(),
            'type' => 'microbiologique',
            'date_prelevement' => today()->subDays(2),
        ]);
        $analyse->lot()->associate($lot);
        $analyse->declarant()->associate($transformateur);
        $analyse->save();
    }

    /**
     * Lots existants : l'acteur courant est celui de la dernière étape.
     */
    private function acteursCourants(): void
    {
        Lot::whereNull('acteur_courant_id')->with(['etapes' => fn ($q) => $q->latest('date_heure')])->get()
            ->each(fn (Lot $lot) => $lot->etapes->isNotEmpty() && $lot->update(['acteur_courant_id' => $lot->etapes->first()->acteur_id]));
    }

    /**
     * @param  list<array{0: string, 1: Acteur, 2: int, 3: string, 4: list<array{0: string, 1: float}>}>  $etapes
     */
    private function lot(string $numero, string $nomProduit, int $quantite, int $joursDepuisProduction, Acteur $courant, array $etapes): void
    {
        $produit = Produit::firstWhere('nom', $nomProduit);

        if (! $produit || Lot::where('numero_lot', $numero)->exists()) {
            return;
        }

        DB::transaction(function () use ($numero, $produit, $quantite, $joursDepuisProduction, $courant, $etapes) {
            $production = today()->subDays($joursDepuisProduction);

            $lot = Lot::create([
                'produit_id' => $produit->id,
                'acteur_courant_id' => $courant->id,
                'numero_lot' => $numero,
                'quantite' => $quantite,
                'date_production' => $production,
                'date_peremption' => $production->copy()->addDays($produit->categorie?->type === 'cereales' ? 365 : 20),
            ]);

            foreach ($etapes as [$type, $acteur, $joursAvant, $transport, $indicateurs]) {
                $etape = Etape::create([
                    'lot_id' => $lot->id,
                    'acteur_id' => $acteur->id,
                    'type_etape' => $type,
                    'date_heure' => today()->subDays($joursAvant)->setTime(8, 30),
                    'lieu' => $acteur->adresse,
                    'mode_transport' => $transport,
                    'remarques' => $type === 'production' ? 'Lot étiqueté sur site.' : null,
                ]);

                foreach ($indicateurs as [$typeIndicateur, $valeur]) {
                    $lot->enregistrerIndicateur($etape, $typeIndicateur, $valeur);
                }
            }

            $courant->produits()->syncWithoutDetaching([$produit->id]);
        });
    }

    private function signalements(): void
    {
        $consommateur = User::firstWhere('email', 'utilisateur@nutritrace.tn');

        if (! $consommateur || $consommateur->signalements()->exists()) {
            return;
        }

        $exemples = [
            ['Tomates de plein champ', 'origine_incorrecte', 'L\'étiquette du magasin indique une origine différente de celle affichée sur NutriTrace.', 'en_attente'],
            ['Yaourt nature', 'qualite', 'Produit conforme et frais, la chaîne du froid semble bien respectée : traçabilité vérifiée en magasin.', 'valide'],
            ['Couscous moyen 1 kg', 'label_douteux', 'Le logo « Local » figure sur le paquet alors que la certification est suspendue.', 'valide'],
            ['Pâtes spaghetti 500 g', 'autre', 'Je pensais que le paquet faisait 1 kg.', 'rejete'],
        ];

        foreach ($exemples as [$nomProduit, $motif, $description, $statut]) {
            $produit = Produit::firstWhere('nom', $nomProduit);

            if (! $produit) {
                continue;
            }

            $signalement = new Signalement(['produit_id' => $produit->id, 'motif' => $motif, 'description' => $description]);
            $signalement->user()->associate($consommateur);
            $signalement->statut = $statut;
            $signalement->save();
        }
    }

    private function declarationEnAttente(): void
    {
        $produit = Produit::firstWhere('nom', 'Farine de blé');
        $organisme = Organisme::orderBy('id')->first();

        if (! $produit || ! $organisme || Certification::where('numero', 'BIO-TN-2026-031')->exists()) {
            return;
        }

        Certification::create([
            'produit_id' => $produit->id,
            'organisme_id' => $organisme->id,
            'type' => 'bio',
            'numero' => 'BIO-TN-2026-031',
            'statut' => 'en_attente',
            'date_obtention' => today()->subWeek(),
            'date_expiration' => today()->addYears(3),
        ]);
    }

    private function scans(): void
    {
        $consommateur = User::firstWhere('email', 'utilisateur@nutritrace.tn');

        if (! $consommateur || $consommateur->scans()->exists()) {
            return;
        }

        Lot::has('etapes')->orderBy('numero_lot')->limit(3)->get()->each(function (Lot $lot, int $index) use ($consommateur) {
            $scan = $consommateur->scans()->create(['lot_id' => $lot->id]);
            $scan->forceFill(['created_at' => now()->subDays(3 - $index), 'updated_at' => now()->subDays(3 - $index)])->save();
        });
    }
}
