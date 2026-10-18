<?php

/*
|--------------------------------------------------------------------------
| Espace consommateur (Front Office)
|--------------------------------------------------------------------------
|
| Qualité, historique des scans. Le profil reste sur /mon-compte
| (routes/web.php).
|
*/

use App\Http\Controllers\Consommateur\ChatQualiteController;
use App\Http\Controllers\Consommateur\QualiteController;
use App\Http\Controllers\Consommateur\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:espace-consommateur'])->prefix('mon-espace')->name('consommateur.')->group(function () {
    Route::get('/scans', ScanController::class)->name('scans.index');

    Route::get('/qualite', [QualiteController::class, 'index'])->name('qualite.index');
    Route::post('/qualite/resumer-fichier', [QualiteController::class, 'resumerFichier'])->name('qualite.resumer-fichier');
    Route::post('/qualite/analyses/{analyse}/resumer', [QualiteController::class, 'resumerAnalyse'])->name('qualite.resumer');

    Route::post('/assistant-qualite', ChatQualiteController::class)->name('chat-qualite');
});
