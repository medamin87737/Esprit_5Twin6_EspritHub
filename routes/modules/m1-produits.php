<?php

/*
|--------------------------------------------------------------------------
| Module 1 — Produits & Catégories (Ghada)
|--------------------------------------------------------------------------
|
| Les produits se gèrent depuis deux espaces avec le même contrôleur :
| l'administrateur (tout le catalogue) et le fournisseur (ses produits).
|
*/

use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\ProduitController;
use App\Http\Controllers\Front\CatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('/produits', CatalogueController::class)->name('front.produits.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Sans parameters(), Laravel nommerait le paramètre {category} et la liaison avec $categorie échouerait.
    Route::resource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);
    Route::resource('produits', ProduitController::class);
});

Route::middleware(['auth', 'fournisseur'])->prefix('fournisseur')->name('fournisseur.')->group(function () {
    Route::redirect('/', '/fournisseur/produits');
    Route::resource('produits', ProduitController::class);
});
