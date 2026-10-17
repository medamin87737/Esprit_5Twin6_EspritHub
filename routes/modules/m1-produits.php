<?php

/*
|--------------------------------------------------------------------------
| Module 1 — Produits & Catégories (Ghada)
|--------------------------------------------------------------------------
|
| L'administrateur gère tout le catalogue ici ; producteurs et
| transformateurs gèrent leurs propres produits depuis /pro (routes/pro.php).
|
| ORDRE IMPORTANT : les routes statiques (/produits/comparer, /ia/produits/*)
| sont déclarées AVANT la route dynamique /produits/{produit} pour éviter que
| Laravel n'interprète « comparer » comme un identifiant de produit.
|
*/

use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\ProduitController;
use App\Http\Controllers\Front\CatalogueController;
use App\Http\Controllers\Front\ComparateurController;
use App\Http\Controllers\Front\ProduitController as FrontProduitController;
use App\Http\Controllers\Module1\IaCategoriserController;
use App\Http\Controllers\Module1\IaOcrController;
use App\Http\Controllers\Module1\IaRecommandationsController;
use Illuminate\Support\Facades\Route;

// ── Catalogue public ──────────────────────────────────────────────────────────
Route::get('/produits', CatalogueController::class)->name('front.produits.index');

// ── Comparateur fiches produits (A2) — AVANT {produit} ────────────────────────
Route::get('/produits/comparer', [ComparateurController::class, 'index'])->name('front.produits.comparer');
Route::post('/produits/comparaison/ajouter', [ComparateurController::class, 'ajouter'])->name('front.produits.comparaison.ajouter');
Route::delete('/produits/comparaison/retirer/{id}', [ComparateurController::class, 'retirer'])
    ->name('front.produits.comparaison.retirer')
    ->whereNumber('id');
Route::delete('/produits/comparaison/vider', [ComparateurController::class, 'vider'])->name('front.produits.comparaison.vider');

// ── Fiche produit publique — {produit} contraint en entier ────────────────────
Route::get('/produits/{produit}', [FrontProduitController::class, 'show'])
    ->name('front.produits.show')
    ->whereNumber('produit');

// ── Détail d'un lot (JSON) pour les onglets de la fiche (A3) ──────────────────
Route::get('/produits/{produit}/lots/{lot}', [FrontProduitController::class, 'lotJson'])
    ->name('front.produits.lot_json')
    ->whereNumber(['produit', 'lot'])
    ->scopeBindings();

// ── Recommandations IA (A3/IA3) — JSON différé ───────────────────────────────
Route::get('/produits/{produit}/recommandations', [IaRecommandationsController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('front.produits.recommandations')
    ->whereNumber('produit');

// ── Routes IA (auth + Gate create Produit + throttle) ────────────────────────
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('/ia/produits/categoriser', [IaCategoriserController::class, 'categoriser'])
        ->name('ia.produits.categoriser');

    Route::post('/ia/produits/ocr', [IaOcrController::class, 'lire'])
        ->name('ia.produits.ocr');
});

// ── Administration (auth + admin) ─────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Sans parameters(), Laravel nommerait le paramètre {category} et la liaison avec $categorie échouerait.
    Route::resource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);
    Route::resource('produits', ProduitController::class);
});
