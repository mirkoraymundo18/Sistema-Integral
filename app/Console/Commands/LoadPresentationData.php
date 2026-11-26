<?php

namespace App\Console\Commands;

use App\Actions\CreateCatalogProduct;
use App\Actions\CreateLotReservation;
use App\Actions\RecordKardexMovement;
use App\Models\Client;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoadPresentationData extends Command
{
    protected $signature = 'sistema:cargar-ejemplos {--admin= : ID del administrador responsable}';

    protected $description = 'Agregar clientes, lotes referenciales de Huancayo y materiales con entradas iniciales en Kardex';

    public function handle(CreateCatalogProduct $createProduct, RecordKardexMovement $record, CreateLotReservation $createReservation): int
    {
        $adminQuery = User::where('role', 'ADMINISTRADOR');
        if ($this->option('admin') !== null) {
            if (!ctype_digit((string) $this->option('admin'))) {
                $this->error('El ID del administrador debe ser un número entero.');
                return self::FAILURE;
            }
            $adminQuery->whereKey($this->option('admin'));
        }
        $admin = $adminQuery->orderBy('id')->first();
        if (!$admin) {
            $this->error('Se necesita una cuenta administradora activa. No se modificó ningún registro.');
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents(database_path('data/huancayo.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->line($data['nota']);

        $counts = DB::transaction(function () use ($admin, $data, $createProduct, $record, $createReservation) {
            // Serialize repeated/concurrent imports and preserve existing operations.
            if (DB::getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(20261120, 7)');
            }
            $actor = User::where('role', 'ADMINISTRADOR')->lockForUpdate()->findOrFail($admin->id);
            $counts = ['Clientes' => 0, 'DNI de ejemplo actualizados' => 0, 'Proyectos' => 0, 'Lotes' => 0, 'Reservas' => 0, 'Familias' => 0, 'Unidades' => 0, 'Productos' => 0, 'Entradas Kardex' => 0];
            $clients = [];

            foreach ($data['clientes'] as $item) {
                if (!preg_match('/\A7[0-9]{7}\z/', $item['dni'])) {
                    throw new RuntimeException('El archivo contiene un DNI de ejemplo con formato incorrecto.');
                }
                $client = Client::where('dni', $item['dni'])->first();
                if (!$client && isset($item['dni_anterior'])) {
                    $client = Client::where('dni', $item['dni_anterior'])
                        ->where('nombres', $item['nombres'])->where('apellidos', $item['apellidos'])
                        ->lockForUpdate()->first();
                    if ($client) {
                        $client->dni = $item['dni'];
                        $client->save();
                        $counts['DNI de ejemplo actualizados']++;
                    }
                }
                if (!$client) {
                    $client = new Client(collect($item)->except(['clave', 'dni_anterior'])->all());
                    $client->created_by = $actor->id;
                    $client->save();
                    $counts['Clientes']++;
                }
                if ($client->nombres === $item['nombres'] && $client->apellidos === $item['apellidos']) {
                    $clients[$item['clave']] = $client;
                }
            }

            foreach ($data['proyectos'] as $item) {
                $project = RealEstateProject::where('nombre', $item['nombre'])->where('ubicacion', $item['ubicacion'])->first();
                if (!$project) {
                    $project = RealEstateProject::create([
                        'nombre' => $item['nombre'], 'ubicacion' => $item['ubicacion'], 'descripcion' => $item['descripcion'],
                    ]);
                    $counts['Proyectos']++;
                }
                $project = RealEstateProject::lockForUpdate()->findOrFail($project->id);
                foreach ($item['lotes'] as $lotData) {
                    if ($project->lots()->where('manzana', $lotData['manzana'])->where('numero', $lotData['numero'])->exists()) {
                        continue;
                    }
                    $lot = new LandLot($lotData);
                    $lot->updated_by = $actor->id;
                    $project->lots()->save($lot);
                    $counts['Lotes']++;
                }
            }

            foreach ($data['reservas'] ?? [] as $item) {
                if (LotReservation::where('token', $item['token'])->exists() || !isset($clients[$item['cliente']])) {
                    continue;
                }
                $project = RealEstateProject::where('nombre', $item['proyecto'])->where('ubicacion', $item['ubicacion'])->firstOrFail();
                $project = RealEstateProject::lockForUpdate()->findOrFail($project->id);
                $lot = $project->lots()->where('manzana', $item['manzana'])->where('numero', $item['numero'])->lockForUpdate()->firstOrFail();
                if ($lot->estado !== 'DISPONIBLE' || $lot->activeReservation()->exists() || $lot->precio_total <= 0) {
                    continue;
                }
                $createReservation->create([
                    'token' => $item['token'], 'land_lot_id' => $lot->id,
                    'client_id' => $clients[$item['cliente']]->id,
                    'fecha_vencimiento' => now('America/Lima')->addDays($item['dias_vigencia'])->toDateString(),
                    'precio_acordado' => $lot->precio_total,
                    'condiciones' => $item['condiciones'],
                ], $actor->id);
                $counts['Reservas']++;
            }

            $units = [];
            foreach ($data['unidades'] as $unitData) {
                $unit = MeasurementUnit::where('nombre', $unitData['nombre'])->first()
                    ?? MeasurementUnit::where('simbolo', $unitData['simbolo'])->first();
                if (!$unit) {
                    $unit = MeasurementUnit::create($unitData);
                    $counts['Unidades']++;
                }
                $units[$unitData['nombre']] = $unit;
            }

            foreach ($data['productos'] as $item) {
                // Include archived rows so a retired material is never restored or loaded twice.
                if (Product::withTrashed()->where('nombre', $item['nombre'])->exists()) {
                    continue;
                }
                $family = ProductFamily::where('nombre', $item['familia'])->first();
                if (!$family) {
                    $prefix = $item['prefijo'];
                    $suffix = 1;
                    while (ProductFamily::where('prefijo', $prefix)->exists()) {
                        $prefix = $item['prefijo'].++$suffix;
                    }
                    $family = ProductFamily::create(['nombre' => $item['familia'], 'prefijo' => $prefix]);
                    $counts['Familias']++;
                }
                $product = $createProduct->create([
                    'nombre' => $item['nombre'],
                    'descripcion' => null,
                    'product_family_id' => $family->id,
                    'measurement_unit_id' => $units[$item['unidad']]->id,
                    'precio_unitario' => $item['precio_unitario'],
                    'stock_minimo' => $item['stock_minimo'],
                    'stock_actual' => 0,
                ]);
                $product = Product::with('unit')->lockForUpdate()->findOrFail($product->id);
                $record->record($product, [
                    'tipo' => 'ENTRADA',
                    'cantidad' => $item['stock_inicial'],
                    'precio_unitario' => $item['precio_unitario'],
                    'motivo_o_proyecto' => 'Registro de existencias iniciales',
                ], $actor->id);
                $counts['Productos']++;
                $counts['Entradas Kardex']++;
            }

            return $counts;
        }, 3);

        $this->table(['Registro', 'Nuevos'], collect($counts)->map(fn ($count, $name) => [$name, $count])->values()->all());
        $this->info('Carga terminada. Se conservaron los registros, saldos y reservas anteriores.');
        $this->line('Los datos de ejemplo no están verificados en RENIEC ni SUNARP. Puedes repetir el comando sin recargar estos saldos.');
        return self::SUCCESS;
    }
}
