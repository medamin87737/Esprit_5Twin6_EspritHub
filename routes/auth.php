<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

    Route::get('/double-authentification', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/double-authentification', [TwoFactorController::class, 'authenticate'])->middleware('throttle:6,1')->name('two-factor.authenticate');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/email/verifier', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verifier/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/securite/2fa', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/securite/2fa', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/securite/2fa/confirmer', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/securite/2fa', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/securite/2fa/codes', [TwoFactorController::class, 'regenerate'])->name('two-factor.recovery');
});
