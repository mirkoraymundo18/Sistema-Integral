<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\MaterialRequest;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\User;
use App\Support\WarehouseQuarterLoader;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseQuarterTest extends TestCase
{
    use DatabaseMigrations;

    public function test_quarter_has_linked_deliveries_preserves_balances_and_is_not_loaded_twice(): void
    {
        $this->travelTo(now('America/Lima')->setDate(2026,11,25)->setTime(12,0));
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $operator = User::factory()->create(['role' => 'OPERADOR']);
        $unit = MeasurementUnit::firstOrCreate(['simbolo' => 'und.'], ['nombre' => 'Unidad', 'permite_decimales' => false]);
        for ($familyIndex = 1; $familyIndex <= 6; $familyIndex++) {
            $family = ProductFamily::create(['nombre' => 'Familia '.$familyIndex, 'prefijo' => 'F'.$familyIndex]);
            for ($index = 1; $index <= 2; $index++) Product::create(['nombre' => 'Material '.$familyIndex.'-'.$index, 'codigo_sku' => 'F'.$familyIndex.'-00'.$index, 'product_family_id' => $family->id, 'measurement_unit_id' => $unit->id, 'stock_actual' => 0, 'stock_minimo' => 2, 'precio_unitario' => '25.00']);
        }
        $clock = new DateTimeImmutable('2026-11-25 12:00:00', new DateTimeZone('America/Lima'));
        $loader = new WarehouseQuarterLoader(DB::connection()->getPdo());
        $result = $loader->load('2026-11-25', $admin->id, $clock);
        $this->assertSame(77, $result['counts']['requests']);
        $this->assertSame(30, $result['counts']['entries']);
        $this->assertSame(60, $result['counts']['exits']);
        $this->assertSame(0, Product::where('stock_actual', '<>', 0)->count());
        $this->assertSame(60, MaterialRequest::where('estado', 'Entregado')->whereNotNull('kardex_movement_id')->count());
        $this->assertSame(77, MaterialRequest::where('user_id', $operator->id)->count());
        $repeated = $loader->load('2026-11-25', $admin->id, $clock);
        $this->assertSame(3, $repeated['counts']['months_skipped']);
        $this->assertSame(90, KardexMovement::count());
        $this->withoutVite()->actingAs($admin)->get(route('dashboard',['agrupacion'=>'mes','periodos'=>3]))->assertOk()
            ->assertViewHas('period', 3)->assertViewHas('months', fn ($months) => count($months) === 3 && array_column($months,'entries') === [8,10,12] && array_column($months,'exits') === [16,20,24]);
    }
}
