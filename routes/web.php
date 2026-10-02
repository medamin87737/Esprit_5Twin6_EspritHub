<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.front.home')->name('home');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/mon-compte', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mon-compte', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/mon-compte/mot-de-passe', [ProfileController::class, 'password'])->name('profile.password');
    Route::delete('/mon-compte', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');

    Route::resource('utilisateurs', UserController::class)
        ->parameters(['utilisateurs' => 'user'])
        ->names('users')
        ->except('show');
});

require __DIR__.'/modules/m1-produits.php';
require __DIR__.'/modules/m2-acteurs.php';
require __DIR__.'/modules/m3-lots.php';
require __DIR__.'/modules/m4-empreintes.php';
require __DIR__.'/modules/m5-certifications.php';
