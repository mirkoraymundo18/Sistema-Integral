<?php

namespace Tests\Feature;

use App\Models\MaterialRequest;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_fractional_movements_preserve_exact_stock_and_whole_units_reject_fractions(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $unit = MeasurementUnit::create(['nombre' => 'Metro', 'simbolo' => 'm', 'permite_decimales' => true]);
        $product = Product::create(['codigo_sku' => 'ELE-001', 'nombre' => 'Cable', 'measurement_unit_id' => $unit->id]);
        $movement = ['product_id' => $product->id, 'tipo' => 'ENTRADA', 'precio_unitario' => 2, 'motivo_o_proyecto' => 'Prueba de precisión'];

        $this->actingAs($admin)->post(route('kardex.store'), [...$movement, 'cantidad' => '0.10'])->assertSessionHasNoErrors();
        $this->post(route('kardex.store'), [...$movement, 'cantidad' => '0.20'])->assertSessionHasNoErrors();
        $this->assertEquals('0.30', $product->fresh()->stock_actual);
        $this->post(route('kardex.store'), [...$movement, 'tipo' => 'SALIDA', 'cantidad' => '0.31'])->assertSessionHasErrors('cantidad');
        $this->assertEquals('0.30', $product->fresh()->stock_actual);

        $whole = Product::create(['codigo_sku' => 'EPP-001', 'nombre' => 'Casco']);
        $this->post(route('kardex.store'), [...$movement, 'product_id' => $whole->id, 'cantidad' => '0.50'])->assertSessionHasErrors('cantidad');
    }

    public function test_count_records_adjustment_and_rejects_stale_balance_or_operator_access(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $operator = User::factory()->create();
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento', 'stock_actual' => 10]);
        $data = ['product_id' => $product->id, 'saldo_esperado' => 10, 'cantidad' => 8, 'motivo' => 'Conteo semanal'];

        $this->actingAs($operator)->post(route('counts.store'), $data)->assertForbidden();
        $this->actingAs($admin)->post(route('counts.store'), $data)->assertSessionHasNoErrors();
        $this->assertEquals(8, $product->fresh()->stock_actual);
        $this->assertDatabaseHas('kardex_movements', ['product_id' => $product->id, 'tipo' => 'SALIDA', 'cantidad' => 2]);
        $this->post(route('counts.store'), $data)->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('stock_counts', 1);
        $this->assertDatabaseCount('kardex_movements', 1);
    }

    public function test_fractional_delivery_has_a_receipt_visible_only_to_owner_and_admin(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $unit = MeasurementUnit::create(['nombre' => 'Litro', 'simbolo' => 'l', 'permite_decimales' => true]);
        $product = Product::create([
            'codigo_sku' => 'PIN-001', 'nombre' => 'Pintura',
            'measurement_unit_id' => $unit->id, 'stock_actual' => '5.50', 'precio_unitario' => 10,
        ]);

        $this->actingAs($owner)->post(route('requests.store'), [
            'product_id' => $product->id, 'cantidad' => '1.25', 'motivo' => 'Mantenimiento',
        ])->assertSessionHasNoErrors();
        $order = MaterialRequest::sole();
        $this->actingAs($admin)->patch(route('requests.updateStatus', $order), ['estado' => 'Aprobado'])->assertSessionHasNoErrors();
        $this->post(route('requests.dispatch', $order))->assertSessionHasNoErrors();
        $this->assertEquals('4.25', $product->fresh()->stock_actual);
        $this->actingAs($owner)->get(route('requests.receipt', $order))->assertOk()->assertSee('1.25');
        $this->actingAs($other)->get(route('requests.receipt', $order))->assertForbidden();
    }

    public function test_session_expires_after_thirty_minutes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['almacen_last_activity' => now()->subMinutes(31)->timestamp])
            ->get(route('products.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
