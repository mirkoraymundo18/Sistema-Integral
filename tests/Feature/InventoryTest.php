<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\MaterialRequest;
use App\Models\MeasurementUnit;
use App\Models\ProductFamily;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivating_one_user_preserves_other_accounts_and_returns_to_active_list(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $carlos = User::factory()->create(['name' => 'Carlos']);
        $other = User::factory()->create(['name' => 'Otra cuenta']);

        $this->actingAs($admin)->delete(route('users.destroy', $carlos))
            ->assertRedirect(route('users.index', ['status' => 'activo']))
            ->assertSessionHas('success', fn ($message) => str_contains($message, $carlos->email));

        $this->assertSoftDeleted($carlos);
        $this->assertNotSoftDeleted($other);
        $this->assertNotSoftDeleted($admin);
        $this->assertSame(2, User::count());
    }

    public function test_catalog_load_renames_old_examples_and_keeps_codes_and_balances(): void
    {
        $family = ProductFamily::create([
            'nombre' => 'Demo · Materiales de construcción', 'prefijo' => 'DMAT',
        ]);
        $unit = MeasurementUnit::create(['nombre' => 'Bolsa', 'simbolo' => 'bls']);
        $existing = Product::create([
            'codigo_sku' => 'DMAT-101',
            'nombre' => 'Cemento Portland tipo I (demo)',
            'descripcion' => 'Producto de demostración. El precio y el saldo inicial son de ejemplo.',
            'product_family_id' => $family->id,
            'measurement_unit_id' => $unit->id,
            'stock_actual' => 42,
            'precio_unitario' => 32.5,
        ]);

        $this->artisan('almacen:cargar-catalogo')->assertSuccessful();
        $this->artisan('almacen:cargar-catalogo')->assertSuccessful();

        $this->assertDatabaseCount('products', 28);
        $this->assertDatabaseCount('product_families', 6);
        $this->assertDatabaseCount('kardex_movements', 0);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id, 'codigo_sku' => 'DMAT-101', 'stock_actual' => 42,
            'nombre' => 'Cemento Portland tipo I', 'descripcion' => null,
        ]);
        $this->assertDatabaseHas('product_families', [
            'id' => $family->id, 'nombre' => 'Materiales de construcción', 'prefijo' => 'DMAT',
        ]);
        $this->assertDatabaseHas('products', [
            'codigo_sku' => 'DMAT-001', 'stock_actual' => 0,
            'nombre' => 'Mortero seco de albañilería', 'measurement_unit_id' => $unit->id,
        ]);
    }

    public function test_dashboard_limits_request_totals_to_the_operator_and_counts_movements_by_month(): void
    {
        $operator = User::factory()->create();
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $product = Product::create([
            'codigo_sku' => 'MAT-001', 'nombre' => 'Cemento',
            'stock_actual' => 4, 'stock_minimo' => 5, 'precio_unitario' => 12.5,
        ]);
        foreach ([$operator, $admin] as $user) {
            MaterialRequest::create([
                'product_id' => $product->id, 'user_id' => $user->id,
                'estado' => 'Pendiente', 'cantidad' => 1, 'motivo' => 'Trabajo de prueba',
            ]);
        }

        $product->kardexMovements()->create([
            'user_id' => $operator->id, 'tipo' => 'ENTRADA', 'cantidad' => 4,
            'precio_unitario' => 12.5, 'motivo_o_proyecto' => 'Entrada de prueba',
        ]);

        $response = $this->withoutVite()->actingAs($operator)->get(route('dashboard', ['meses' => 12]));
        $response->assertOk();
        $this->assertEquals(1, $response->viewData('requests')['Pendiente']);
        $this->assertEquals(50, $response->viewData('inventory')->valor);
        $this->assertEquals(1, $response->viewData('lowStockCount'));
        $this->assertCount(12, $response->viewData('months'));
        $this->assertSame(1, $response->viewData('months')[11]['entries']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $this->assertEquals(2, $response->viewData('requests')['Pendiente']);
    }

    public function test_operator_cannot_administer_users_or_modify_catalog(): void
    {
        $operator = User::factory()->create();
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento']);

        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
        $this->post(route('users.store'), ['role' => 'ADMINISTRADOR'])->assertForbidden();
        $this->delete(route('users.destroy', $operator))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $operator))->assertForbidden();
        $this->patch(route('users.update', $operator), ['role' => 'ADMINISTRADOR'])->assertForbidden();
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('products.settings'))->assertForbidden();
        $this->post(route('products.store'))->assertForbidden();
        $this->patch(route('products.update', $product))->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();
    }

    public function test_admin_can_edit_another_account_and_preserves_own_access(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $operator = User::factory()->create();
        $this->actingAs($admin)->patch(route('users.update', $operator), [
            'name' => 'Nuevo nombre',
            'email' => $operator->email,
            'role' => 'ADMINISTRADOR',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $operator->id,
            'name' => 'Nuevo nombre',
            'role' => 'ADMINISTRADOR',
        ]);

        $this->patch(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'OPERADOR',
        ])->assertSessionHasErrors('role');
        $this->delete(route('users.destroy', $admin))->assertSessionHasErrors('cuenta');
        $this->assertSame('ADMINISTRADOR', $admin->refresh()->role);
    }

    public function test_admin_can_assign_another_admin_and_catalog_starts_at_zero(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Administración',
            'email' => 'admin2@example.test',
            'password' => 'contraseña-segura',
            'role' => 'ADMINISTRADOR',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'admin2@example.test', 'role' => 'ADMINISTRADOR']);

        $family = ProductFamily::create(['nombre' => 'Materiales', 'prefijo' => 'MAT']);
        $unit = MeasurementUnit::create(['nombre' => 'Bolsa', 'simbolo' => 'bolsa']);
        Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Material anterior'])->delete();

        $this->post(route('products.store'), [
            'codigo_sku' => 'IGNORAR-999',
            'nombre' => 'Cemento',
            'precio_unitario' => '24.50',
            'stock_actual' => 99,
            'stock_minimo' => 7,
            'product_family_id' => $family->id,
            'measurement_unit_id' => $unit->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'codigo_sku' => 'MAT-002',
            'stock_actual' => 0,
            'stock_minimo' => 7,
            'product_family_id' => $family->id,
            'measurement_unit_id' => $unit->id,
        ]);
    }

    public function test_restock_filter_includes_products_at_their_minimum(): void
    {
        $user = User::factory()->create();
        Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento', 'stock_actual' => 8, 'stock_minimo' => 8]);
        Product::create(['codigo_sku' => 'MAT-002', 'nombre' => 'Pintura', 'stock_actual' => 8, 'stock_minimo' => 7]);

        $this->withoutVite()->actingAs($user)
            ->get(route('products.index', ['stock' => 'por-reponer']))
            ->assertOk()->assertSee('Cemento')->assertDontSee('Pintura');
    }

    public function test_kardex_records_the_authenticated_operator_and_blocks_overdraw(): void
    {
        $operator = User::factory()->create();
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento']);
        $data = [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tipo' => 'ENTRADA',
            'cantidad' => 4,
            'precio_unitario' => '24.50',
            'motivo_o_proyecto' => 'Compra de prueba',
        ];

        $this->withoutVite()->actingAs($operator)->get(route('kardex.index'))
            ->assertOk()->assertSee('name="motivo_o_proyecto"', false)->assertSee('value="ENTRADA"', false);

        $this->post(route('kardex.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame($operator->id, KardexMovement::sole()->user->id);

        $this->post(route('kardex.store'), array_replace($data, ['tipo' => 'SALIDA', 'cantidad' => 5]))
            ->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('kardex_movements', 1);
        $this->assertSame(4, (int) $product->fresh()->stock_actual);

        $this->post(route('kardex.store'), array_replace($data, ['tipo' => 'SALIDA']))
            ->assertSessionHasNoErrors();
        $this->assertSame(0, (int) $product->fresh()->stock_actual);

        $this->actingAs($admin)->delete(route('products.destroy', $product))->assertSessionHasNoErrors();
        $this->delete(route('users.destroy', $operator))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($operator);

        $this->get(route('kardex.index'))->assertOk()
            ->assertSee($operator->name)->assertSee($product->nombre);
        $this->assertDatabaseCount('kardex_movements', 2);
    }

    public function test_kardex_export_respects_product_type_and_inclusive_dates(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento']);
        $other = Product::create(['codigo_sku' => 'MAT-002', 'nombre' => 'Pintura']);
        $data = [
            'user_id' => $user->id,
            'tipo' => 'SALIDA',
            'cantidad' => 2,
            'precio_unitario' => 20,
        ];

        $product->kardexMovements()->forceCreate([...$data, 'motivo_o_proyecto' => 'Despacho elegido', 'created_at' => '2026-11-06 04:59:59']);
        $product->kardexMovements()->forceCreate([...$data, 'motivo_o_proyecto' => 'Día siguiente', 'created_at' => '2026-11-06 05:00:00']);
        $product->kardexMovements()->forceCreate([...$data, 'tipo' => 'ENTRADA', 'motivo_o_proyecto' => 'Otra operación', 'created_at' => '2026-11-05 12:00:00']);
        $other->kardexMovements()->forceCreate([...$data, 'motivo_o_proyecto' => 'Otro producto', 'created_at' => '2026-11-05 12:00:00']);
        $filters = ['product_id' => $product->id, 'tipo' => 'SALIDA', 'hasta' => '2026-11-05'];

        $this->withoutVite()->actingAs($user)->get(route('kardex.index', $filters))
            ->assertOk()->assertSee('Despacho elegido')->assertDontSee('Día siguiente');

        $response = $this->get(route('kardex.export', $filters))->assertOk();
        $file = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file));
        try {
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertIsString($sheet);
            $this->assertStringContainsString('Despacho elegido', $sheet);
            $this->assertStringNotContainsString('Día siguiente', $sheet);
            $this->assertStringNotContainsString('Otra operación', $sheet);
            $this->assertStringNotContainsString('Otro producto', $sheet);
        } finally {
            $zip->close();
            unlink($file);
        }
    }

    public function test_only_admin_can_resolve_a_pending_request_once(): void
    {
        $operator = User::factory()->create();
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $product = Product::create(['codigo_sku' => 'MAT-001', 'nombre' => 'Cemento']);

        $this->actingAs($operator)->post(route('requests.store'), [
            'product_id' => $product->id,
            'cantidad' => 2,
            'motivo' => 'Mantenimiento',
        ])->assertSessionHasNoErrors();

        $request = MaterialRequest::sole();
        $url = route('requests.updateStatus', $request);
        $this->assertSame('Pendiente', $request->estado);

        $this->patch($url, ['estado' => 'Aprobado'])->assertForbidden();
        $this->actingAs($admin)->patch($url, ['estado' => 'Aprobado'])->assertSessionHasNoErrors();
        $this->patch($url, ['estado' => 'Rechazado'])->assertSessionHasErrors('estado');
        $this->assertSame('Aprobado', $request->refresh()->estado);

        $this->assertSame($admin->id, $request->decided_by);
        $dispatch = route('requests.dispatch', $request);
        $this->post($dispatch)->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('kardex_movements', 0);
        $this->assertSame('Aprobado', $request->refresh()->estado);

        $this->post(route('kardex.store'), [
            'product_id' => $product->id,
            'tipo' => 'ENTRADA',
            'cantidad' => 5,
            'precio_unitario' => 10,
            'motivo_o_proyecto' => 'Entrada de prueba',
        ])->assertSessionHasNoErrors();

        $this->actingAs($operator)->post($dispatch)->assertForbidden();
        $this->actingAs($admin)->post($dispatch)->assertSessionHasNoErrors();
        $this->assertSame('Entregado', $request->refresh()->estado);
        $this->assertSame('SALIDA', $request->movement->tipo);
        $this->assertSame($admin->id, $request->movement->user_id);
        $this->assertSame(3, (int) $product->fresh()->stock_actual);

        $this->post($dispatch)->assertSessionHasErrors('estado');
        $this->assertDatabaseCount('kardex_movements', 2);
        $this->assertSame(3, (int) $product->fresh()->stock_actual);
    }
}
