<?php

namespace App\Console\Commands;

use App\Support\WarehouseQuarterLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class LoadQuarterActivity extends Command
{
    protected $signature = 'almacen:cargar-actividad {--hasta= : Fecha final YYYY-MM-DD, por defecto hoy en Perú} {--admin= : ID del administrador responsable}';
    protected $description = 'Guardar tres meses de actividad de muestra con requerimientos, entregas y Kardex vinculados';

    public function handle(): int
    {
        $admin = $this->option('admin');
        if ($admin !== null && (!ctype_digit((string)$admin) || (int)$admin < 1)) {
            $this->error('El ID del administrador debe ser positivo.');
            return self::FAILURE;
        }
        try {
            $result = (new WarehouseQuarterLoader(DB::connection()->getPdo()))->load($this->option('hasta') ?? now('America/Lima')->toDateString(), $admin === null ? null : (int)$admin);
            $this->table(['Registro', 'Cantidad'], collect($result['counts'])->map(fn ($value, $key) => [$key, $value])->values()->all());
            $this->info('Periodo: '.$result['from'].' al '.$result['through'].'. El saldo anterior de los productos se conserva.');
            $this->line('Origen: actividad generada de muestra, guardada en la base. No representa operaciones históricas verificadas.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
