<?php

/*
|--------------------------------------------------------------------------
| Module 1 — Produits & Catégories (Ghada)
|--------------------------------------------------------------------------
|
| L'administrateur gère tout le catalogue ici ; producteurs et
| transformateurs gèrent leurs propres produits depuis /pro (routes/pro.php).
|
*/

use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\ProduitController;
use App\Http\Controllers\Front\CatalogueController;
use App\Http\Controllers\Front\ProduitController as FrontProduitController;
use Illuminate\Support\Facades\Route;

Route::get('/produits', CatalogueController::class)->name('front.produits.index');
Route::get('/produits/{produit}', [FrontProduitController::class, 'show'])->name('front.produits.show');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Sans parameters(), Laravel nommerait le paramètre {category} et la liaison avec $categorie échouerait.
    Route::resource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);
    Route::resource('produits', ProduitController::class);
});
