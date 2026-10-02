<?php

/*
|--------------------------------------------------------------------------
| Module 1 — Produits & Catégories (Amin)
|--------------------------------------------------------------------------
|
| Interfaces seules : les Route::view seront remplacées par
| Route::resource('categories', CategorieController::class) et
| Route::resource('produits', ProduitController::class) lors du CRUD.
|
*/

use Illuminate\Support\Facades\Route;

Route::view('/produits', 'pages.front.produits.index')->name('front.produits.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/categories', 'pages.admin.categories.index')->name('categories.index');
    Route::view('/categories/create', 'pages.admin.categories.create')->name('categories.create');

    Route::view('/produits', 'pages.admin.produits.index')->name('produits.index');
    Route::view('/produits/create', 'pages.admin.produits.create')->name('produits.create');
});
