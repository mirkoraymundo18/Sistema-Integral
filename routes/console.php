<?php

use App\Actions\ExpireLotReservations;
use App\Models\User;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('almacen:crear-admin', function () {
    $validator = Validator::make([
        'name' => $this->ask('Nombre del administrador'),
        'email' => $this->ask('Correo electrónico'),
        'password' => $this->secret('Contraseña (mínimo 12 caracteres)'),
    ], [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users',
        'password' => 'required|string|min:12',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return 1;
    }

    $user = new User($validator->validated());
    $user->role = 'ADMINISTRADOR';
    $user->save();

    $this->info('Cuenta de administrador creada.');

    return 0;
})->purpose('Crear una cuenta de administración del almacén');

Artisan::command('comercial:vencer-reservas', function (ExpireLotReservations $expire) {
    $expire->run();
    $this->info('Vencimientos de reservas procesados.');
})->purpose('Liberar los lotes cuyas reservas ya vencieron');

Schedule::command('comercial:vencer-reservas')->everyMinute()->withoutOverlapping();
