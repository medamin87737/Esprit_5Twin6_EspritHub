<?php

declare(strict_types=1);

namespace App\Console\Commands\Module1;

use App\Models\Produit;
use App\Services\Module1\OpenRouterEmbedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Module 1 — Commande artisan produits:embeddings (Ghada).
 *
 * Pré-calcule les vecteurs d'embedding pour tous les produits.
 * Les vecteurs sont écrits directement en base (update sans updated_at).
 *
 * Usage :
 *   php artisan produits:embeddings       # calcule uniquement ceux périmés
 *   php artisan produits:embeddings --force  # recalcule tout
 */
class ProduitsEmbeddingsCommand extends Command
{
    protected $signature = 'produits:embeddings {--force : Recalculer tous les embeddings}';

    protected $description = 'Pré-calculer les vecteurs d\'embedding IA3 pour les recommandations';

    public function handle(): int
    {
        $service = new OpenRouterEmbedding;
        $estRepli = $service->estRepli();

        if ($estRepli) {
            $this->warn('Aucune clé API détectée : utilisation du repli local (similarité lexicale).');
        }

        $query = Produit::query();

        if (! $this->option('force')) {
            // Seulement les produits dont l'embedding est périmé ou absent
            $query->where(function ($q) {
                $q->whereNull('embedding_hash')
                    ->orWhere('embedding_hash', '!=', 'placeholder');
            });

            // On doit comparer le hash en PHP car la fonction n'est pas en SQL
            $produits = Produit::all()->filter(fn (Produit $p) => ! $p->embeddingEstAJour());
        } else {
            $produits = Produit::all();
        }

        if ($produits->isEmpty()) {
            $this->info('Tous les embeddings sont à jour. Utilisez --force pour tout recalculer.');

            return self::SUCCESS;
        }

        $this->info("Calcul des embeddings pour {$produits->count()} produit(s)...");

        $textes = $produits->map(fn (Produit $p) => $p->texteEmbedding())->all();
        $vecteurs = $service->embeddings($textes);

        $compteur = 0;
        foreach ($produits as $i => $produit) {
            $vecteur = $vecteurs[$i] ?? [];
            $hash = $produit->hashEmbeddingAttendu();

            // update() direct sans toucher updated_at
            DB::table('produits')
                ->where('id', $produit->id)
                ->update([
                    'embedding' => json_encode($vecteur),
                    'embedding_hash' => $hash,
                ]);

            $compteur++;
            $this->line("  ✓ {$produit->nom}");
        }

        $this->info("{$compteur} embedding(s) calculé(s).");

        return self::SUCCESS;
    }
}
