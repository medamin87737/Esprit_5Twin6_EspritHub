<?php

/*
|--------------------------------------------------------------------------
| Espace consommateur (Front Office)
|--------------------------------------------------------------------------
|
| Signalements et historique des scans. Le profil reste sur /mon-compte,
| commun à tous les utilisateurs connectés (routes/web.php).
|
*/

use App\Http\Controllers\Consommateur\ScanController;
use App\Http\Controllers\Consommateur\SignalementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:espace-consommateur'])->prefix('mon-espace')->name('consommateur.')->group(function () {
    Route::get('/signalements', [SignalementController::class, 'index'])->name('signalements.index');
    Route::get('/signalements/{signalement}/modifier', [SignalementController::class, 'edit'])->name('signalements.edit')->can('update', 'signalement');
    Route::put('/signalements/{signalement}', [SignalementController::class, 'update'])->name('signalements.update')->can('update', 'signalement');
    Route::delete('/signalements/{signalement}', [SignalementController::class, 'destroy'])->name('signalements.destroy')->can('delete', 'signalement');

    Route::middleware('can:signaler')->group(function () {
        Route::get('/signaler/{produit}', [SignalementController::class, 'create'])->name('signalements.create');
        Route::post('/signaler/{produit}', [SignalementController::class, 'store'])->name('signalements.store');
    });

    Route::get('/scans', ScanController::class)->name('scans.index');
});
