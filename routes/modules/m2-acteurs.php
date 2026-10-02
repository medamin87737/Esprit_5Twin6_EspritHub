<?php

/*
|--------------------------------------------------------------------------
| Module 2 — Acteurs de la chaîne (Amin)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\ActeurController;
use App\Http\Controllers\Admin\TypeActeurController;
use App\Http\Controllers\Front\AnnuaireController;
use Illuminate\Support\Facades\Route;

Route::get('/acteurs', AnnuaireController::class)->name('front.acteurs.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('type-acteurs', TypeActeurController::class);
    Route::resource('acteurs', ActeurController::class);
});
