<?php

/*
|--------------------------------------------------------------------------
| Module 4 — Empreinte environnementale (Sahar)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\EmpreinteCarboneController;
use App\Http\Controllers\Admin\FacteurClimatiqController;
use App\Http\Controllers\Admin\IndicateurController;
use App\Http\Controllers\Front\ComparaisonController;
use App\Http\Controllers\Front\EcoScoreController;
use Illuminate\Support\Facades\Route;

Route::get('/empreintes', EcoScoreController::class)->name('front.empreintes.index');
Route::get('/comparer', ComparaisonController::class)->name('front.comparaison');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('empreintes', EmpreinteCarboneController::class);
    Route::resource('indicateurs', IndicateurController::class);

    Route::get('facteurs-climatiq', [FacteurClimatiqController::class, 'index'])->name('facteurs-climatiq.index');
    Route::post('facteurs-climatiq/synchroniser', [FacteurClimatiqController::class, 'synchroniser'])->name('facteurs-climatiq.synchroniser');
});
