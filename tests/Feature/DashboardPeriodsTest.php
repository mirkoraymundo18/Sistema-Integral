<?php
namespace Tests\Feature;
use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
class DashboardPeriodsTest extends TestCase {
    use RefreshDatabase;
    public function test_weekly_range_uses_peru_dates_and_drilldown_matches_the_chart(): void {
        $this->travelTo(Carbon::parse('2026-11-23 12:00:00', 'America/Lima'));
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Material']);
        foreach ([['ENTRADA', '2026-11-16 04:30:00'], ['SALIDA', '2026-11-16 06:00:00']] as [$type, $utc]) {
            KardexMovement::forceCreate(['product_id' => $product->id, 'user_id' => $admin->id, 'tipo' => $type, 'cantidad' => 1, 'precio_unitario' => 1, 'motivo_o_proyecto' => 'Prueba de límite semanal', 'created_at' => $utc, 'updated_at' => $utc]);
        }
        $response = $this->withoutVite()->actingAs($admin)->get(route('dashboard', ['agrupacion' => 'semana', 'rango' => 'personalizado', 'desde' => '2026-11-09', 'hasta' => '2026-11-22']));
        $response->assertOk();
        $buckets = $response->viewData('months');
        $this->assertCount(2, $buckets);
        $this->assertSame(1, $buckets[0]['entries']);
        $this->assertSame(0, $buckets[0]['exits']);
        $this->assertSame(1, $buckets[1]['exits']);
        $this->get(route('kardex.index', ['desde' => $buckets[0]['from'], 'hasta' => $buckets[0]['to']]))
            ->assertOk()->assertViewHas('movements', fn ($rows) => $rows->total() === 1);
        $this->get(route('dashboard', ['agrupacion' => 'semana', 'periodos' => 4]))->assertOk()->assertViewHas('months', fn ($buckets) => count($buckets) === 4);
        $this->get(route('dashboard', ['meses' => 12]))->assertOk()->assertViewHas('months', fn ($buckets) => count($buckets) === 12);
        $this->get(route('dashboard', ['rango' => 'personalizado', 'desde' => '2025-01-01', 'hasta' => '2026-11-22']))->assertSessionHasErrors('desde');
        $this->get(route('dashboard', ['rango' => 'personalizado', 'desde' => '2026-11-22', 'hasta' => '2026-11-09']))->assertSessionHasErrors('hasta');
        $this->travelBack();
    }
}
