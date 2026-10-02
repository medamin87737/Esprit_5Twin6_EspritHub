<?php

/*
|--------------------------------------------------------------------------
| Module 2 — Acteurs de la chaîne (Sahar)
|--------------------------------------------------------------------------
|
| Interfaces seules : les Route::view seront remplacées par
| Route::resource('type-acteurs', TypeActeurController::class) et
| Route::resource('acteurs', ActeurController::class) lors du CRUD.
|
*/

use Illuminate\Support\Facades\Route;

Route::view('/acteurs', 'pages.front.acteurs.index')->name('front.acteurs.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/type-acteurs', 'pages.admin.type-acteurs.index')->name('type-acteurs.index');
    Route::view('/type-acteurs/create', 'pages.admin.type-acteurs.create')->name('type-acteurs.create');

    Route::view('/acteurs', 'pages.admin.acteurs.index')->name('acteurs.index');
    Route::view('/acteurs/create', 'pages.admin.acteurs.create')->name('acteurs.create');
});
