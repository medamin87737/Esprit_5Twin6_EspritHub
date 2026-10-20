<?php

namespace Tests\Feature;

use App\Models\Analyse;
use App\Models\Certification;
use App\Models\Produit;
use Database\Seeders\CatalogueDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Catalogue de démonstration : chaque produit a lots, rapports et certificats.
 */
class CatalogueDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_demo_complet_pour_presentation(): void
    {
        $this->seed(CatalogueDemoSeeder::class);

        $this->assertGreaterThanOrEqual(20, Produit::count());
        $this->assertSame(0, Produit::doesntHave('lots')->count(), 'Chaque produit doit avoir au moins un lot.');
        $this->assertSame(0, Produit::doesntHave('certifications')->count(), 'Chaque produit doit avoir au moins une certification.');

        $terminees = Analyse::where('resultat', '!=', 'en_attente')->get();
        $this->assertNotEmpty($terminees);
        foreach ($terminees as $analyse) {
            $this->assertNotEmpty($analyse->rapport, "Rapport manquant pour {$analyse->numero}");
            $this->assertTrue(Storage::disk(Analyse::DISQUE)->exists($analyse->rapport), "Fichier rapport absent pour {$analyse->numero}");
            $this->assertNotEmpty($analyse->resume_ia, "Synthèse manquante pour {$analyse->numero}");
        }

        foreach (Certification::all() as $certification) {
            $this->assertNotEmpty($certification->document_pdf);
            $this->assertTrue(Storage::disk(Certification::DISQUE)->exists($certification->document_pdf));
        }

        // Cas pédagogiques présents pour la démo
        $this->assertTrue(Analyse::where('resultat', 'non_conforme')->exists());
        $this->assertTrue(Analyse::where('resultat', 'en_attente')->exists());
        $this->assertTrue(Produit::where('nom', 'Boga cidre')->exists());
    }
}
