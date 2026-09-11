<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
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
     *
     * DISI-15: definir gates globales que controlan el acceso segun el rol.
     * - admin: gestiona todo el sistema
     * - anotador: solo ve informacion y anota donde esta asignado
     */
    public function boot(): void
    {
        // Gate admin-only: solo admin puede gestionar catalogos (ligas, torneos,
        // equipos, categorias, atletas, anotadores, referees)
        $adminCatalogs = ['leagues', 'tournaments', 'teams', 'categories', 'athletes', 'scorekeepers', 'referees'];
        foreach ($adminCatalogs as $resource) {
            Gate::define("manage {$resource}", function (User $user) use ($resource) {
                return $user->hasRole('admin');
            });
        }

        // Gate admin-only: gestionar CRUD de juegos (create/edit/delete)
        Gate::define('manage games crud', function (User $user) {
            return $user->hasRole('admin');
        });

        // Gate: ver juegos (admin + anotador asignado + owner)
        Gate::define('view games', function (User $user) {
            return $user->hasRole('admin') || $user->hasRole('anotador');
        });

        // Gate: anotar jugadas (admin + anotador asignado al juego)
        // El control fino por juego se hace en GamePolicy::score()
        Gate::define('score any game', function (User $user) {
            return $user->hasRole('admin');
        });
    }
}
