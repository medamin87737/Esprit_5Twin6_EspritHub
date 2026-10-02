<?php

/*
|--------------------------------------------------------------------------
| Module 5 — Certifications (Marwa)
|--------------------------------------------------------------------------
|
| Interfaces seules : les Route::view seront remplacées par
| Route::resource('organismes', OrganismeController::class) et
| Route::resource('certifications', CertificationController::class) lors du CRUD.
|
*/

use Illuminate\Support\Facades\Route;

Route::view('/certifications', 'pages.front.certifications.index')->name('front.certifications.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/organismes', 'pages.admin.organismes.index')->name('organismes.index');
    Route::view('/organismes/create', 'pages.admin.organismes.create')->name('organismes.create');

    Route::view('/certifications', 'pages.admin.certifications.index')->name('certifications.index');
    Route::view('/certifications/create', 'pages.admin.certifications.create')->name('certifications.create');
});
