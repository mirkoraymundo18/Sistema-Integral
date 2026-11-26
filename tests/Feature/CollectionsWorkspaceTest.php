<?php

namespace Tests\Feature;

use App\Actions\ConfirmLotSale;
use App\Actions\CreateLotReservation;
use App\Actions\RecordLotPayment;
use App\Models\Client;
use App\Models\LotSale;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(User $admin, Client $client, string $currency = 'PEN', string $price = '45000.00'): LotSale
    {
        $project = RealEstateProject::create(['nombre' => 'Proyecto de prueba', 'ubicacion' => 'Huancayo']);
        $lot = $project->lots()->forceCreate(['updated_by'=>$admin->id, 'manzana' => 'A', 'numero' => '01', 'area_m2' => 120, 'precio_total' => $price, 'moneda' => $currency, 'estado' => 'DISPONIBLE']);
        $reservation = app(CreateLotReservation::class)->create([
            'token' => (string) Str::uuid(), 'land_lot_id' => $lot->id, 'client_id' => $client->id,
            'fecha_vencimiento' => now('America/Lima')->addDays(7)->toDateString(), 'precio_acordado' => $price,
        ], $admin->id);
        return app(ConfirmLotSale::class)->confirm($reservation, $admin->id, null);
    }

    private function buyer(User $admin): Client
    {
        return Client::forceCreate(['dni' => '71826349', 'nombres' => 'Comprador', 'apellidos' => 'De prueba', 'created_by' => $admin->id]);
    }

    public function test_client_account_keeps_paid_purchases_history_and_separate_currency_totals(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $client = $this->buyer($admin);
        $pen = $this->purchase($admin, $client);
        $usd = $this->purchase($admin, $client, 'USD', '1000.00');
        app(RecordLotPayment::class)->record($pen, ['token' => (string) Str::uuid(), 'monto' => '45000.00', 'fecha_pago' => now('America/Lima')->toDateString(), 'metodo' => 'EFECTIVO'], $admin->id);
        app(RecordLotPayment::class)->record($usd, ['token' => (string) Str::uuid(), 'monto' => '100.10', 'fecha_pago' => now('America/Lima')->toDateString(), 'metodo' => 'DEPOSITO', 'referencia' => 'BANCO-USD-1'], $admin->id);
        $this->withoutVite()->actingAs($admin)->get(route('commercial.clients.show', $client))
            ->assertOk()->assertSee('Deuda cancelada')->assertSee($pen->codigo)->assertSee($usd->codigo)
            ->assertSee('BANCO-USD-1')->assertSee('Cancelar toda la deuda')
            ->assertViewHas('accountSummary', fn ($summary) => $summary['PEN']['balance'] === 0 && $summary['USD']['balance'] === 89990 && $summary['USD']['paid'] === 10010)
            ->assertViewHas('clientPayments', fn ($payments) => $payments->total() === 2);
    }

    public function test_full_payment_opens_its_receipt_and_repeated_submission_does_not_duplicate_it(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $sale = $this->purchase($admin, $this->buyer($admin));
        $data = ['token' => (string) Str::uuid(), 'modo_pago' => 'SALDO', 'saldo_esperado' => '45000.00', 'monto' => '45000.00', 'fecha_pago' => now('America/Lima')->toDateString(), 'metodo' => 'TRANSFERENCIA', 'referencia' => 'OP-COBRO-1'];
        $this->withoutVite()->actingAs($admin)->get(route('commercial.sales.show', [$sale, 'pago' => 'SALDO']))->assertOk()->assertViewHas('defaultPaymentMode', 'SALDO');
        $this->post(route('commercial.sales.payment', $sale), $data)->assertSessionHasNoErrors()->assertRedirect(route('commercial.sales.show', $sale).'#payment-history')->assertSessionHas('payment_receipt_id');
        $payment = $sale->payments()->sole();
        $this->post(route('commercial.sales.payment', $sale), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_payments', 1);
        $this->assertSame('0.00', $sale->refresh()->saldo);
        $this->get(route('commercial.sales.receipt', [$sale, $payment]))->assertOk()
            ->assertSee($payment->codigo)->assertSee('OP-COBRO-1')->assertSee('Transferencia')
            ->assertSee('45,000.00')->assertSee($admin->name)->assertSee('Pago único');
    }

    public function test_receipts_must_belong_to_purchase_and_operator_can_only_consult(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $client = $this->buyer($admin);
        $first = $this->purchase($admin, $client);
        $other = $this->purchase($admin, $client);
        $payment = app(RecordLotPayment::class)->record($first, ['token' => (string) Str::uuid(), 'monto' => '100.00', 'fecha_pago' => now('America/Lima')->toDateString(), 'metodo' => 'EFECTIVO'], $admin->id);
        $operator = User::factory()->create(['role' => 'OPERADOR']);
        $this->withoutVite()->actingAs($operator)->get(route('commercial.sales.receipt', [$first, $payment]))->assertOk();
        $this->get(route('commercial.sales.receipt', [$other, $payment]))->assertNotFound();
        $this->get(route('commercial.clients.show', $client))->assertOk()->assertDontSee('Cancelar toda la deuda')->assertSee('Ver / imprimir');
        $this->post(route('commercial.sales.payment', $first), [])->assertForbidden();
        $this->get(route('commercial.sales.index', ['search' => $first->codigo]))->assertOk()->assertSee($first->codigo)->assertDontSee($other->codigo);
        $this->get(route('commercial.sales.index', ['search' => 'Comprador']))->assertOk()->assertSee($first->codigo)->assertSee($other->codigo);
    }
}
