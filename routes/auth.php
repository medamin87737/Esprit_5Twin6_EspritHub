<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
