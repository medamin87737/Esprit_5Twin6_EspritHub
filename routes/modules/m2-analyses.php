<?php

/*
|--------------------------------------------------------------------------
| Module 2 — Analyses qualité (Amin)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AnalyseController;
use App\Http\Controllers\Admin\AnalyseExportController;
use App\Http\Controllers\Admin\AnalyseIaController;
use App\Http\Controllers\Admin\LaboratoireController;
use App\Http\Controllers\Front\RapportAnalyseController;
use Illuminate\Support\Facades\Route;

Route::get('/analyses/{analyse}/rapport', RapportAnalyseController::class)
    ->middleware(['auth', 'can:telechargerRapport,analyse'])
    ->name('front.analyses.rapport');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('laboratoires', LaboratoireController::class);

    Route::get('analyses/export', AnalyseExportController::class)->name('analyses.export');
    Route::post('analyses/ia/prefill', [AnalyseIaController::class, 'prefill'])->name('analyses.ia.prefill');
    Route::post('analyses/{analyse}/ia/extraire', [AnalyseIaController::class, 'extraire'])->name('analyses.ia.extraire');
    Route::post('analyses/{analyse}/ia/resumer', [AnalyseIaController::class, 'resumer'])->name('analyses.ia.resumer');
    Route::post('lots/{lot}/ia/risque', [AnalyseIaController::class, 'risque'])->name('lots.ia.risque');

    Route::resource('analyses', AnalyseController::class)->parameters(['analyses' => 'analyse']);
});
