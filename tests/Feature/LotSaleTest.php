<?php
namespace Tests\Feature;
use App\Actions\CreateLotReservation;
use App\Models\Client;
use App\Models\LotSale;
use App\Models\LotPayment;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class LotSaleTest extends TestCase {
    use RefreshDatabase;
    private function reservation(): array {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $client = new Client(['dni' => '71826349', 'nombres' => 'Comprador', 'apellidos' => 'De prueba']);
        $client->created_by = $admin->id; $client->save();
        $project = RealEstateProject::create(['nombre' => 'Proyecto', 'ubicacion' => 'Huancayo']);
        $lot = $project->lots()->forceCreate(['updated_by'=>$admin->id, 'manzana' => 'A', 'numero' => '01', 'area_m2' => 120, 'precio_total' => 45000, 'moneda' => 'PEN', 'estado' => 'DISPONIBLE']);
        $reservation = app(CreateLotReservation::class)->create([
            'token' => (string) Str::uuid(), 'land_lot_id' => $lot->id, 'client_id' => $client->id,
            'fecha_vencimiento' => now('America/Lima')->addDays(7)->toDateString(), 'precio_acordado' => '44000.30',
        ], $admin->id);
        $this->withoutVite()->actingAs($admin);
        return [$admin, $lot, $reservation];
    }
    public function test_purchase_preserves_agreement_converts_once_and_blocks_lot_edits(): void {
        [, $lot, $reservation] = $this->reservation();
        $this->get(route('commercial.sales.create', $reservation))->assertOk();
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1])->assertSessionHasNoErrors();
        $sale = LotSale::sole();
        $this->assertSame('44000.30', $sale->precio_total);
        $this->assertSame('44000.30', $sale->saldo);
        $this->assertSame('0.00', $sale->total_pagado);
        $this->assertSame('CONVERTIDA', $reservation->refresh()->estado);
        $this->assertNull($reservation->active_lot_id);
        $this->assertSame('VENDIDO', $lot->refresh()->estado);
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_sales', 1);
        $this->post(route('commercial.reservations.cancel', $reservation), ['motivo_cancelacion' => 'Intento posterior'])->assertSessionHasErrors('reserva');
        $this->patch(route('commercial.lots.update', $lot), ['version' => $lot->version])->assertSessionHasErrors('venta');
        $this->get(route('commercial.sales.show', $sale))->assertOk();
        $this->get(route('commercial.reservations.show', $reservation))->assertOk()->assertSee('Convertida en venta');
    }
    public function test_partial_payments_are_exact_idempotent_and_cannot_exceed_balance(): void {
        [, , $reservation] = $this->reservation();
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1]);
        $sale = LotSale::sole();
        $payment = ['token' => (string) Str::uuid(), 'monto' => '10000.10', 'fecha_pago' => now('America/Lima')->toDateString(), 'metodo' => 'TRANSFERENCIA', 'referencia' => 'OP-001'];
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'referencia' => null])->assertSessionHasErrors('referencia');
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'monto' => '+10.00'])->assertSessionHasErrors('monto');
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'fecha_pago' => now('America/Lima')->addDay()->toDateString()])->assertSessionHasErrors('fecha_pago');
        $this->post(route('commercial.sales.payment', $sale), $payment)->assertSessionHasNoErrors();
        $this->post(route('commercial.sales.payment', $sale), $payment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_payments', 1);
        $this->assertSame('34000.20', $sale->refresh()->saldo);
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'token' => (string) Str::uuid()])->assertSessionHasErrors('referencia');
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'token' => (string) Str::uuid(), 'referencia' => 'OP-002', 'monto' => '34000.21'])->assertSessionHasErrors('monto');
        $this->post(route('commercial.sales.payment', $sale), [...$payment, 'token' => (string) Str::uuid(), 'referencia' => 'OP-002', 'monto' => '34000.20'])->assertSessionHasNoErrors();
        $this->assertSame('PAGADA', $sale->refresh()->estado);
        $this->assertSame('0.00', $sale->saldo);
        $this->assertSame('44000.30', $sale->total_pagado);
        $this->assertDatabaseCount('lot_payments', 2);
    }
    public function test_operator_can_consult_purchase_but_only_administrator_confirms_and_records_payments(): void {
        [, , $reservation] = $this->reservation();
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1]);
        $sale = LotSale::sole();
        $this->actingAs(User::factory()->create(['role' => 'OPERADOR']));
        $this->get(route('commercial.sales.index'))->assertOk();
        $this->get(route('commercial.sales.show', $sale))->assertOk()->assertDontSee('data-sale-payment',false);
        $this->get(route('commercial.sales.create', $reservation))->assertRedirect(route('commercial.sales.show', $sale));
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1])->assertForbidden();
        $this->post(route('commercial.sales.payment', $sale), [])->assertForbidden();
        $this->assertDatabaseCount('lot_payments', 0);
    }
    public function test_expired_reservation_cannot_be_converted(): void {
        [, , $reservation] = $this->reservation();
        $reservation->vence_en = now()->subMinute(); $reservation->save();
        $this->post(route('commercial.sales.store', $reservation), ['confirmacion' => 1])->assertSessionHasErrors('venta');
        $this->assertDatabaseCount('lot_sales', 0);
        $this->assertSame('VENCIDA', $reservation->refresh()->estado);
    }
}
