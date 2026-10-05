<?php

/*
|--------------------------------------------------------------------------
| Espace professionnel (Front Office)
|--------------------------------------------------------------------------
|
| Producteurs, transformateurs et distributeurs. Les droits de chaque rôle
| sont définis dans config/nutritrace.php (roles_pro) et vérifiés par les
| Gates de AppServiceProvider et les policies (Lot, Etape, Produit).
|
*/

use App\Http\Controllers\Pro\AnalyseController;
use App\Http\Controllers\Pro\CertificationController;
use App\Http\Controllers\Pro\DashboardController;
use App\Http\Controllers\Pro\EtapeController;
use App\Http\Controllers\Pro\IndicateurController;
use App\Http\Controllers\Pro\LotController;
use App\Http\Controllers\Pro\ProduitController;
use App\Http\Controllers\Pro\ProfilController;
use App\Http\Controllers\Pro\SignalementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:espace-pro'])->prefix('pro')->name('pro.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::middleware('can:pro-produits')->group(function () {
        Route::resource('produits', ProduitController::class)->except('show');

        Route::get('/certifications', [CertificationController::class, 'index'])->name('certifications.index');
        Route::get('/certifications/create', [CertificationController::class, 'create'])->name('certifications.create');
        Route::post('/certifications', [CertificationController::class, 'store'])->name('certifications.store');

        Route::get('/signalements', SignalementController::class)->name('signalements.index');
    });

    Route::middleware('profil-societe')->group(function () {
        Route::get('/lots', [LotController::class, 'index'])->name('lots.index');
        Route::middleware('can:pro-creer-lot')->group(function () {
            Route::get('/lots/create', [LotController::class, 'create'])->name('lots.create');
            Route::post('/lots', [LotController::class, 'store'])->name('lots.store');
        });
        Route::get('/lots/{lot}', [LotController::class, 'show'])->name('lots.show')->can('view', 'lot');
        Route::post('/lots/{lot}/transfert', [LotController::class, 'transferer'])->name('lots.transfert')->can('transferer', 'lot');

        Route::get('/lots/{lot}/etapes/create', [EtapeController::class, 'create'])->name('etapes.create')->can('ajouterEtape', 'lot');
        Route::post('/lots/{lot}/etapes', [EtapeController::class, 'store'])->name('etapes.store')->can('ajouterEtape', 'lot');
        Route::get('/etapes/{etape}/edit', [EtapeController::class, 'edit'])->name('etapes.edit')->can('update', 'etape');
        Route::put('/etapes/{etape}', [EtapeController::class, 'update'])->name('etapes.update')->can('update', 'etape');
        Route::delete('/etapes/{etape}', [EtapeController::class, 'destroy'])->name('etapes.destroy')->can('delete', 'etape');

        Route::get('/etapes/{etape}/indicateurs/create', [IndicateurController::class, 'create'])->name('indicateurs.create')->can('ajouterIndicateur', 'etape');
        Route::post('/etapes/{etape}/indicateurs', [IndicateurController::class, 'store'])->name('indicateurs.store')->can('ajouterIndicateur', 'etape');

        Route::get('/lots/{lot}/analyses/create', [AnalyseController::class, 'create'])->name('analyses.create')->can('ajouterAnalyse', 'lot');
        Route::post('/lots/{lot}/analyses', [AnalyseController::class, 'store'])->name('analyses.store')->can('ajouterAnalyse', 'lot');
        Route::get('/analyses/{analyse}/edit', [AnalyseController::class, 'edit'])->name('analyses.edit')->can('update', 'analyse');
        Route::put('/analyses/{analyse}', [AnalyseController::class, 'update'])->name('analyses.update')->can('update', 'analyse');
        Route::delete('/analyses/{analyse}', [AnalyseController::class, 'destroy'])->name('analyses.destroy')->can('delete', 'analyse');
    });
});
