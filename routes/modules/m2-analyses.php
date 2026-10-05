<?php

/*
|--------------------------------------------------------------------------
| Module 2 — Analyses qualité (Amin)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AnalyseController;
use App\Http\Controllers\Admin\LaboratoireController;
use App\Http\Controllers\Front\RapportAnalyseController;
use Illuminate\Support\Facades\Route;

Route::get('/analyses/{analyse}/rapport', RapportAnalyseController::class)
    ->middleware(['auth', 'can:telechargerRapport,analyse'])
    ->name('front.analyses.rapport');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('laboratoires', LaboratoireController::class);
    Route::resource('analyses', AnalyseController::class)->parameters(['analyses' => 'analyse']);
});
