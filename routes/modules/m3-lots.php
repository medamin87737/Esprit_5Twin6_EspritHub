<?php

/*
|--------------------------------------------------------------------------
| Module 3 — Traçabilité des lots (Ons)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\EtapeController;
use App\Http\Controllers\Admin\LotController;
use App\Http\Controllers\Front\TracabiliteController;
use Illuminate\Support\Facades\Route;

Route::get('/tracabilite', TracabiliteController::class)->name('front.lots.search');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('lots', LotController::class);
    Route::resource('etapes', EtapeController::class);
});
