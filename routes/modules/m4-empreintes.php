<?php

/*
|--------------------------------------------------------------------------
| Module 4 — Empreinte environnementale (Ons)
|--------------------------------------------------------------------------
|
| Interfaces seules : les Route::view seront remplacées par
| Route::resource('empreintes', EmpreinteCarboneController::class) et
| Route::resource('indicateurs', IndicateurController::class) lors du CRUD.
|
*/

use Illuminate\Support\Facades\Route;

Route::view('/empreintes', 'pages.front.empreintes.index')->name('front.empreintes.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/empreintes', 'pages.admin.empreintes.index')->name('empreintes.index');
    Route::view('/empreintes/create', 'pages.admin.empreintes.create')->name('empreintes.create');

    Route::view('/indicateurs', 'pages.admin.indicateurs.index')->name('indicateurs.index');
    Route::view('/indicateurs/create', 'pages.admin.indicateurs.create')->name('indicateurs.create');
});
