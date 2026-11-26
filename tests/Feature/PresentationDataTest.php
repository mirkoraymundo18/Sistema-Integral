<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\KardexMovement;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\Product;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_populates_linked_records_and_repeating_does_not_duplicate_stock(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $this->assertDatabaseCount('clients', 16);
        $this->assertDatabaseCount('real_estate_projects', 3);
        $this->assertDatabaseCount('land_lots', 36);
        $this->assertDatabaseCount('lot_reservations', 6);
        $this->assertDatabaseCount('products', 48);
        $this->assertDatabaseCount('product_families', 8);
        $this->assertDatabaseCount('measurement_units', 12);
        $this->assertDatabaseCount('kardex_movements', 48);
        foreach (Client::all() as $client) {
            $this->assertMatchesRegularExpression('/\A7[0-9]{7}\z/', $client->dni);
            $this->assertSame($admin->id, $client->created_by);
            $this->assertNull($client->telefono);
        }
        foreach (Product::with(['family', 'unit', 'kardexMovements'])->get() as $product) {
            $this->assertNotNull($product->family);
            $this->assertNotNull($product->unit);
            $this->assertCount(1, $product->kardexMovements);
            $entry = $product->kardexMovements->sole();
            $this->assertSame('ENTRADA', $entry->tipo);
            $this->assertSame((string) $product->stock_actual, (string) $entry->cantidad);
            $this->assertSame($admin->id, $entry->user_id);
        }
        foreach (LotReservation::with(['client', 'lot'])->get() as $reservation) {
            $this->assertSame('ACTIVA', $reservation->estado);
            $this->assertSame($reservation->lot->id, $reservation->active_lot_id);
            $this->assertSame('RESERVADO', $reservation->lot->estado);
            $this->assertSame($reservation->client->dni, $reservation->ficha_cliente['dni']);
            $this->assertTrue($reservation->vence_en->isFuture());
        }
        $balances = Product::orderBy('id')->pluck('stock_actual', 'id')->all();
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $this->assertDatabaseCount('clients', 16);
        $this->assertDatabaseCount('land_lots', 36);
        $this->assertDatabaseCount('lot_reservations', 6);
        $this->assertDatabaseCount('products', 48);
        $this->assertDatabaseCount('kardex_movements', 48);
        $this->assertSame($balances, Product::orderBy('id')->pluck('stock_actual', 'id')->all());
    }

    public function test_existing_clients_lots_and_products_keep_their_data_and_balances(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $data = json_decode(file_get_contents(database_path('data/huancayo.json')), true);
        $client = new Client([...$data['clientes'][0], 'nombres' => 'Nombre conservado']);
        $client->created_by = $admin->id;
        $client->save();
        $project = RealEstateProject::create([
            'nombre' => $data['proyectos'][0]['nombre'], 'ubicacion' => $data['proyectos'][0]['ubicacion'],
        ]);
        $lot = $project->lots()->forceCreate(['updated_by'=>$admin->id, ...$data['proyectos'][0]['lotes'][0], 'estado' => 'VENDIDO', 'precio_total' => 88000]);
        $product = Product::create([
            'codigo_sku' => 'EXIST-001', 'nombre' => $data['productos'][0]['nombre'],
            'stock_actual' => 777, 'precio_unitario' => 99, 'stock_minimo' => 12,
        ]);
        $archived = Product::create(['codigo_sku' => 'EXIST-002', 'nombre' => $data['productos'][1]['nombre']]);
        $archived->delete();
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $this->assertSame('Nombre conservado', $client->refresh()->nombres);
        $this->assertSame('VENDIDO', $lot->refresh()->estado);
        $this->assertSame('88000.00', $lot->precio_total);
        $this->assertEquals(777, $product->refresh()->stock_actual);
        $this->assertEquals(99, $product->precio_unitario);
        $this->assertEquals(12, $product->stock_minimo);
        $this->assertTrue($archived->refresh()->trashed());
        $this->assertSame(0, KardexMovement::whereIn('product_id', [$product->id, $archived->id])->count());
        $this->assertSame(48, Product::withTrashed()->count());
    }


    public function test_old_example_dni_is_updated_in_place_and_existing_reservation_keeps_its_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $data = json_decode(file_get_contents(database_path('data/huancayo.json')), true);
        $item = $data['clientes'][0];
        $client = Client::where('dni', $item['dni'])->firstOrFail();
        $reservation = $client->reservations()->firstOrFail();
        $snapshot = $reservation->ficha_cliente;
        $client->update(['dni' => $item['dni_anterior']]);
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $this->assertSame($item['dni'], $client->refresh()->dni);
        $this->assertDatabaseCount('clients', 16);
        $this->assertDatabaseCount('lot_reservations', 6);
        $this->assertSame($snapshot, $reservation->refresh()->ficha_cliente);
    }

    public function test_no_data_is_created_without_an_active_administrator(): void
    {
        User::factory()->create(['role' => 'OPERADOR']);
        $this->artisan('sistema:cargar-ejemplos')->assertFailed();
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('land_lots', 0);
    }
}
