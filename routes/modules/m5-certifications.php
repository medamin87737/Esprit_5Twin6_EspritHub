<?php

/*
|--------------------------------------------------------------------------
| Module 5 — Certifications (Marwa)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\OrganismeController;
use App\Http\Controllers\Front\LabelController;
use Illuminate\Support\Facades\Route;

Route::get('/certifications', LabelController::class)->name('front.certifications.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('organismes', OrganismeController::class);
    Route::resource('certifications', CertificationController::class);
});
