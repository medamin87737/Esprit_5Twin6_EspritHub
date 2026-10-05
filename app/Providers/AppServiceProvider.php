<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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

        $this->definirDroitsFront();
    }

    /**
     * Droits du Front Office par rôle : utilisés par les routes (middleware « can: »)
     * et par les menus (@can). Un visiteur non connecté est toujours refusé.
     */
    private function definirDroitsFront(): void
    {
        Gate::define('espace-consommateur', fn (User $user) => $user->isConsommateur());
        Gate::define('signaler', fn (User $user) => $user->isConsommateur());

        Gate::define('espace-pro', fn (User $user) => $user->isPro());
        Gate::define('pro-produits', fn (User $user) => $user->isPro() && (bool) $user->droitPro('gere_produits'));
        Gate::define('pro-creer-lot', fn (User $user) => $user->isPro() && (bool) $user->droitPro('cree_lots'));

        Gate::define('voir-contacts-acteurs', fn (User $user) => true);
        Gate::define('voir-indicateurs', fn (User $user) => true);
        Gate::define('comparer-sans-limite', fn (User $user) => true);
    }
}
