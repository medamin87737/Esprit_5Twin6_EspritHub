<?php

/*
|--------------------------------------------------------------------------
| Module 3 — Traçabilité des lots (Ghada)
|--------------------------------------------------------------------------
|
| Interfaces seules : les Route::view seront remplacées par
| Route::resource('lots', LotController::class) et
| Route::resource('etapes', EtapeController::class) lors du CRUD.
|
*/

use Illuminate\Support\Facades\Route;

Route::view('/tracabilite', 'pages.front.lots.search')->name('front.lots.search');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/lots', 'pages.admin.lots.index')->name('lots.index');
    Route::view('/lots/create', 'pages.admin.lots.create')->name('lots.create');

    Route::view('/etapes', 'pages.admin.etapes.index')->name('etapes.index');
    Route::view('/etapes/create', 'pages.admin.etapes.create')->name('etapes.create');
});
