<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le Back Office (SB Admin 2) est en Bootstrap 4 ; le Front passe 'pagination::bootstrap-5' explicitement.
        Paginator::useBootstrapFour();

        Carbon::setLocale(config('app.locale'));
    }
}
