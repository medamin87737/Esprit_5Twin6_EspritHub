<?php

/*
|--------------------------------------------------------------------------
| Module 3 — Traçabilité des lots (Ons)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\EtapeController;
use App\Http\Controllers\Admin\LotController;
use App\Http\Controllers\Front\LotController as FrontLotController;
use App\Http\Controllers\Front\TracabiliteController;
use Illuminate\Support\Facades\Route;

Route::get('/tracabilite', TracabiliteController::class)->name('front.lots.search');
Route::get('/tracabilite/{lot:numero_lot}', [FrontLotController::class, 'show'])->name('front.lots.show');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('lots/export', [LotController::class, 'export'])->name('lots.export');
    Route::post('lots/{lot}/recit-ia', [LotController::class, 'regenererRecit'])->name('lots.recit-ia');
    Route::post('lots/{lot}/audit-ia', [LotController::class, 'analyserAuditIa'])->name('lots.audit-ia');
    Route::post('lots/{lot}/conseil-ia', [LotController::class, 'genererConseilIa'])->name('lots.conseil-ia');
    Route::resource('lots', LotController::class);
    Route::post('etapes/assistant-ai', [EtapeController::class, 'assistantAi'])->name('etapes.assistant-ai');
    Route::resource('etapes', EtapeController::class);
});
