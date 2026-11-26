<?php

namespace App\Providers;

use App\Models\User;
use App\Models\LotReservation;
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
        Gate::define('administrar-almacen', fn (User $user): bool => $user->role === 'ADMINISTRADOR');
        Gate::define('gestionar-comercial', fn (User $user): bool => in_array($user->role, ['ADMINISTRADOR', 'OPERADOR'], true));
        Gate::define('cancelar-reserva', fn (User $user, LotReservation $reservation): bool =>
            $user->role === 'ADMINISTRADOR'
            || ($user->role === 'OPERADOR' && (int) $reservation->created_by === (int) $user->id)
        );
    }
}
