<?php

/*
|--------------------------------------------------------------------------
| Module 5 — Certifications (Marwa)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\CertificationExportController;
use App\Http\Controllers\Admin\CertificationIaController;
use App\Http\Controllers\Admin\CertificationTraductionController;
use App\Http\Controllers\Admin\OrganismeController;
use App\Http\Controllers\Front\ChatCertificationController;
use App\Http\Controllers\Front\LabelController;
use Illuminate\Support\Facades\Route;

Route::get('/certifications', LabelController::class)->name('front.certifications.index');
Route::get('/certifications/assistant', ChatCertificationController::class)->name('front.certifications.chat');
Route::post('/certifications/assistant', [ChatCertificationController::class, 'repondre'])->name('front.certifications.chat.repondre')->middleware('throttle:30,1');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('organismes', OrganismeController::class);
    Route::resource('certifications', CertificationController::class);
    Route::patch('certifications/{certification}/valider', [CertificationController::class, 'valider'])->name('certifications.valider');
    Route::patch('certifications/{certification}/suspendre', [CertificationController::class, 'suspendre'])->name('certifications.suspendre');

    // Export
    Route::get('certifications/{certification}/pdf', [CertificationExportController::class, 'pdf'])->name('certifications.pdf');
    Route::get('certifications/{certification}/qr', [CertificationExportController::class, 'qr'])->name('certifications.qr');

    // Traduction
    Route::post('certifications/{certification}/traduire', CertificationTraductionController::class)->name('certifications.traduire');

    // IA
    Route::post('certifications/{certification}/ia/upload', [CertificationIaController::class, 'upload'])->name('certifications.ia.upload');
    Route::post('certifications/{certification}/ia/verifier', [CertificationIaController::class, 'verifier'])->name('certifications.ia.verifier');
    Route::post('certifications/{certification}/ia/incoherences', [CertificationIaController::class, 'incoherences'])->name('certifications.ia.incoherences');
});
