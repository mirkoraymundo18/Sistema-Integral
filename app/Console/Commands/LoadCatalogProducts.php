<?php

namespace App\Console\Commands;

use App\Actions\CreateCatalogProduct;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LoadCatalogProducts extends Command
{
    protected $signature = 'almacen:cargar-catalogo';

    protected $description = 'Agregar productos por familia y normalizar los nombres de la carga anterior';

    private const FAMILIES = [
        'MAT' => [
            'nombre' => 'Materiales de construcción',
            'anterior' => 'DMAT',
            'productos' => [
                ['Cemento Portland tipo I', 'Bolsa', 'bolsa', 101],
                ['Mortero seco de albañilería', 'Bolsa', 'bolsa', 102],
                ['Varilla corrugada de 12 mm', 'Unidad', 'und.', 103],
                ['Ladrillo King Kong de 18 huecos', 'Unidad', 'und.'],
                ['Yeso de construcción', 'Bolsa', 'bolsa'],
            ],
        ],
        'PIN' => [
            'nombre' => 'Pinturas y acabados',
            'anterior' => 'DPIN',
            'productos' => [
                ['Pintura látex blanco mate', 'Galón', 'gal', 101],
                ['Sellador acrílico para paredes', 'Galón', 'gal', 102],
                ['Rodillo de espuma de 9 pulgadas', 'Unidad', 'und.', 103],
                ['Esmalte sintético blanco', 'Galón', 'gal'],
                ['Brocha de 3 pulgadas', 'Unidad', 'und.'],
            ],
        ],
        'EPP' => [
            'nombre' => 'Protección personal',
            'anterior' => 'DSEG',
            'productos' => [
                ['Guantes anticorte nivel 5', 'Par', 'par', 101],
                ['Casco de seguridad con ajuste', 'Unidad', 'und.', 102],
                ['Lentes de seguridad transparentes', 'Unidad', 'und.', 103],
                ['Chaleco reflectante naranja', 'Unidad', 'und.'],
                ['Botas de seguridad con punta reforzada', 'Par', 'par'],
            ],
        ],
        'ELE' => [
            'nombre' => 'Material eléctrico',
            'anterior' => 'DELE',
            'productos' => [
                ['Cable eléctrico THHN de 2,5 mm²', 'Metro', 'm', 101],
                ['Tomacorriente doble con tierra', 'Unidad', 'und.', 102],
                ['Cinta aislante negra de 18 m', 'Unidad', 'und.', 103],
                ['Interruptor termomagnético de 20 A', 'Unidad', 'und.'],
                ['Tubo conduit PVC de 20 mm', 'Unidad', 'und.'],
            ],
        ],
        'SAN' => [
            'nombre' => 'Instalaciones sanitarias',
            'productos' => [
                ['Tubo PVC de desagüe de 2 pulgadas', 'Unidad', 'und.'],
                ['Codo PVC de 90 grados de 2 pulgadas', 'Unidad', 'und.'],
                ['Llave de paso de 1/2 pulgada', 'Unidad', 'und.'],
                ['Cinta de teflón para roscas', 'Rollo', 'rollo'],
            ],
        ],
        'HER' => [
            'nombre' => 'Herramientas y fijaciones',
            'productos' => [
                ['Martillo de uña de 16 onzas', 'Unidad', 'und.'],
                ['Cinta métrica de 5 metros', 'Unidad', 'und.'],
                ['Tornillo para madera de 2 pulgadas', 'Caja', 'caja'],
                ['Disco de corte para metal de 4 1/2 pulgadas', 'Unidad', 'und.'],
            ],
        ],
    ];

    public function handle(CreateCatalogProduct $createProduct): int
    {
        [$created, $updated] = DB::transaction(function () use ($createProduct) {
            $created = 0;
            $updated = 0;

            foreach (self::FAMILIES as $prefix => $group) {
                $legacyPrefix = $group['anterior'] ?? null;
                if ($legacyPrefix) {
                    $this->normalizeFamily($group['nombre'], $legacyPrefix);
                }

                $family = ProductFamily::where('nombre', $group['nombre'])->first()
                    ?? $this->createFamily($group['nombre'], $prefix);
                $family = ProductFamily::lockForUpdate()->findOrFail($family->id);

                foreach ($group['productos'] as $item) {
                    [$name, $unitName, $symbol] = $item;
                    $legacyProduct = isset($item[3])
                        ? Product::withTrashed()->where('codigo_sku', sprintf('%s-%03d', $legacyPrefix, $item[3]))->lockForUpdate()->first()
                        : null;

                    if ($legacyProduct) {
                        $updated += (int) $this->normalizeProduct($legacyProduct, $name);
                        continue;
                    }

                    if (Product::withTrashed()->where('nombre', $name)->exists()) {
                        continue;
                    }

                    $unit = MeasurementUnit::where('simbolo', $symbol)->first()
                        ?? MeasurementUnit::where('nombre', $unitName)->first()
                        ?? MeasurementUnit::firstOrCreate(['simbolo' => $symbol], ['nombre' => $unitName, 'permite_decimales' => in_array($symbol, ['m', 'kg', 'l', 'L'], true)]);

                    $createProduct->create([
                        'nombre' => $name,
                        'product_family_id' => $family->id,
                        'measurement_unit_id' => $unit->id,
                        'stock_minimo' => 0,
                        'precio_unitario' => 0,
                        'stock_actual' => 0,
                    ]);
                    $created++;
                }
            }

            return [$created, $updated];
        }, 3);

        $this->info("Productos agregados: $created. Productos anteriores actualizados: $updated.");

        return self::SUCCESS;
    }

    private function createFamily(string $name, string $prefix): ProductFamily
    {
        $number = 1;
        $availablePrefix = $prefix;

        while (ProductFamily::where('prefijo', $availablePrefix)->exists()) {
            $availablePrefix = $prefix.++$number;
        }

        return ProductFamily::firstOrCreate(['nombre' => $name], ['prefijo' => $availablePrefix]);
    }

    private function normalizeFamily(string $name, string $legacyPrefix): void
    {
        $family = ProductFamily::where('prefijo', $legacyPrefix)->where('nombre', 'Demo · '.$name)->lockForUpdate()->first();
        if (! $family) {
            return;
        }

        $family->nombre = ProductFamily::where('nombre', $name)->exists() ? $name.' ('.$legacyPrefix.')' : $name;
        $family->save();
    }

    private function normalizeProduct(Product $product, string $name): bool
    {
        if ($product->nombre === $name.' (demo)') {
            $product->nombre = $name;
        }

        if ($product->descripcion === 'Producto de demostración. El precio y el saldo inicial son de ejemplo.') {
            $product->descripcion = null;
        }

        if (! $product->isDirty()) {
            return false;
        }

        return $product->save();
    }
}
