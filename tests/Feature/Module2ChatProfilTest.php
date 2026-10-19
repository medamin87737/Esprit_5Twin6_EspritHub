<?php

namespace Tests\Feature;

use App\Models\Produit;
use App\Services\Analyses\ChatQualiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 (Amin) — l'assistant ne doit jamais « conseiller » un soda
 * à un profil diabète : il trie le catalogue selon le profil déclaré.
 */
class Module2ChatProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_diabete_conseille_eau_et_evince_boga(): void
    {
        Produit::factory()->create(['nom' => 'Boga cidre', 'composition' => 'eau gazéifiée, arômes']);
        Produit::factory()->create(['nom' => 'Citronnade', 'composition' => 'eau, citron']);
        $eau = Produit::factory()->create(['nom' => 'Eau minérale naturelle 1,5 L', 'composition' => 'eau minérale naturelle']);

        $resultat = app(ChatQualiteService::class)->repondre("J'ai du diabète, quels produits me conseiller ?");

        $this->assertStringContainsString('Produits que je vous conseille', $resultat['reponse']);
        $this->assertStringContainsString('Eau minérale naturelle 1,5 L', $resultat['reponse']);
        $this->assertStringContainsString('À éviter pour votre profil', $resultat['reponse']);
        $this->assertStringContainsString('Boga cidre', $resultat['reponse']);

        $idsConseilles = array_column($resultat['produits'], 'id');
        $this->assertSame([$eau->id], $idsConseilles, 'Seule l’eau doit être proposée en fiche conseillée.');
    }

    public function test_assistant_diabete_sur_produit_cible_boga(): void
    {
        $boga = Produit::factory()->create(['nom' => 'Boga cidre', 'composition' => 'eau gazéifiée, arômes']);

        $resultat = app(ChatQualiteService::class)->repondre('J’ai du diabète, ce produit est-il adapté ?', [], $boga->id);

        $this->assertStringContainsString('Peu recommandé pour votre profil', $resultat['reponse']);
    }
}
