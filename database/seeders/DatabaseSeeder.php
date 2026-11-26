<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->command?->info('No se crean usuarios ni contraseñas predeterminados. Usa php artisan almacen:crear-admin y, si necesitas ejemplos, php artisan sistema:cargar-ejemplos.');
    }
}
