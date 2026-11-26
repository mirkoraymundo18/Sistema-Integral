<?php
namespace Tests\Feature;
use App\Actions\BuildSaleSchedule;
use App\Actions\ConfirmLotSale;
use App\Actions\CreateLotReservation;
use App\Actions\RecordLotPayment;
use App\Models\Client;
use App\Models\FinancingPlan;
use App\Models\LotPayment;
use App\Models\LotSale;
use App\Models\PaymentAllocation;
use App\Models\RealEstateProject;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
class FinancingScheduleTest extends TestCase {
    use RefreshDatabase;
    private function setupReservation(): array {
        $this->travelTo(Carbon::parse('2027-01-01 12:00:00', 'America/Lima'));
        $admin = User::factory()->create(['role'=>'ADMINISTRADOR']);
        $client = new Client(['dni'=>'72648391','nombres'=>'Cliente','apellidos'=>'De prueba']); $client->created_by=$admin->id; $client->save();
        $project=RealEstateProject::create(['nombre'=>'Proyecto','ubicacion'=>'Huancayo']);
        $lot=$project->lots()->forceCreate(['updated_by'=>$admin->id, 'manzana'=>'A','numero'=>'01','area_m2'=>100,'precio_total'=>'100.01','moneda'=>'PEN','estado'=>'DISPONIBLE']);
        $reservation=app(CreateLotReservation::class)->create(['token'=>(string)Str::uuid(),'client_id'=>$client->id,'land_lot_id'=>$lot->id,'fecha_vencimiento'=>'2027-01-10','precio_acordado'=>'100.01'],$admin->id);
        $plan=FinancingPlan::create(['nombre'=>'Plan siete meses','meses'=>7,'inicial_minima_porcentaje'=>10,'activo'=>true]);
        $terms=['modalidad'=>'CUOTAS','financing_plan_id'=>$plan->id,'inicial'=>'10.01','primera_cuota'=>'2027-01-31'];
        $this->withoutVite()->actingAs($admin);
        return [$admin,$reservation,$plan,$terms];
    }
    public function test_exact_schedule_month_end_dates_and_partial_payment_allocation(): void {
        [$admin,$reservation,$plan,$terms]=$this->setupReservation();
        $sale=app(ConfirmLotSale::class)->confirm($reservation,$admin->id,null,$terms);
        $rows=$sale->installments()->orderBy('numero')->get();
        $this->assertCount(8,$rows);
        $this->assertSame(10001,$rows->sum(fn($row)=>Money::cents($row->monto)));
        $this->assertSame('2027-02-28',$rows[2]->vence_el->toDateString());
        $this->assertSame('2027-03-31',$rows[3]->vence_el->toDateString());
        $this->assertSame('12.90',$rows->last()->monto);
        $data=['token'=>(string)Str::uuid(),'monto'=>'10.02','fecha_pago'=>'2027-01-01','metodo'=>'EFECTIVO'];
        $payment=app(RecordLotPayment::class)->record($sale,$data,$admin->id);
        app(RecordLotPayment::class)->record($sale,$data,$admin->id);
        $this->assertDatabaseCount('lot_payments',1);
        $this->assertSame(2,PaymentAllocation::where('lot_payment_id',$payment->id)->count());
        $this->assertSame('10.01',$rows[0]->refresh()->pagado);
        $this->assertSame('0.01',$rows[1]->refresh()->pagado);
        $this->assertSame('89.99',$sale->refresh()->saldo);
        $plan->update(['meses'=>12,'inicial_minima_porcentaje'=>50]);
        $this->assertSame(7,$sale->refresh()->condiciones_financiamiento['meses']);
        $this->get(route('commercial.sales.show',$sale))->assertOk()->assertSee('Cronograma de pago');
        $this->travelTo(Carbon::parse('2027-02-01 12:00:00','America/Lima'));
        $this->assertSame('Vencida',$rows[1]->refresh()->estado);
        $this->assertSame('Pagada',$rows[0]->refresh()->estado);
        $this->travelBack();
    }
    public function test_invalid_initial_or_inactive_plan_rolls_back_sale_confirmation(): void {
        [, $reservation,$plan,$terms]=$this->setupReservation();
        $this->post(route('commercial.sales.store',$reservation),[...$terms,'confirmacion'=>1,'inicial'=>'1.00'])->assertSessionHasErrors('inicial');
        $this->assertDatabaseCount('lot_sales',0);
        $this->assertDatabaseCount('sale_installments',0);
        $this->assertSame('ACTIVA',$reservation->refresh()->estado);
        $plan->update(['activo'=>false]);
        $this->post(route('commercial.sales.store',$reservation),[...$terms,'confirmacion'=>1])->assertSessionHasErrors('financing_plan_id');
        $this->assertDatabaseCount('lot_sales',0);
        $this->travelBack();
    }
    public function test_legacy_payments_are_distributed_without_creating_more_payments(): void {
        [$admin,$reservation,,$terms]=$this->setupReservation();
        $sale=LotSale::create(['lot_reservation_id'=>$reservation->id,'land_lot_id'=>$reservation->land_lot_id,'client_id'=>$reservation->client_id,'precio_total'=>'100.01','total_pagado'=>'25.00','moneda'=>'PEN','estado'=>'PENDIENTE','ficha_cliente'=>$reservation->ficha_cliente,'ficha_lote'=>$reservation->ficha_lote,'created_by'=>$admin->id]);
        LotPayment::create(['lot_sale_id'=>$sale->id,'token'=>(string)Str::uuid(),'monto'=>'25.00','fecha_pago'=>'2027-01-01','metodo'=>'EFECTIVO','created_by'=>$admin->id]);
        $this->post(route('commercial.sales.schedule',$sale),[...$terms,'confirmacion'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_payments',1);
        $this->assertSame('25.00',$sale->refresh()->total_pagado);
        $this->assertSame(2500,PaymentAllocation::all()->sum(fn($part)=>Money::cents($part->monto)));
        $this->post(route('commercial.sales.schedule',$sale),[...$terms,'confirmacion'=>1])->assertSessionHasErrors('cronograma');
        $this->assertDatabaseCount('sale_installments',8);
        $this->travelBack();
    }
    public function test_operator_reads_plans_but_cannot_change_them_or_rewrite_a_schedule(): void {
        [$admin,$reservation,$plan,$terms]=$this->setupReservation();
        $operator=User::factory()->create(['role'=>'OPERADOR']);
        $this->actingAs($operator)->get(route('commercial.sales.create',$reservation))->assertOk()->assertSee('Esta es una simulación')->assertDontSee('Confirmar venta');
        $this->post(route('commercial.sales.store',$reservation),[...$terms,'confirmacion'=>1])->assertForbidden();
        $this->assertDatabaseCount('lot_sales',0);
        $sale=app(ConfirmLotSale::class)->confirm($reservation,$admin->id,null,$terms);
        $this->actingAs($operator);
        $this->get(route('commercial.plans.index'))->assertOk();
        $this->get(route('commercial.sales.show',$sale))->assertOk();
        $this->patch(route('commercial.plans.update',$plan),[])->assertForbidden();
        $this->post(route('commercial.plans.store'),[])->assertForbidden();
        $this->post(route('commercial.sales.schedule',$sale),[...$terms,'confirmacion'=>1])->assertForbidden();
        $this->travelBack();
    }
}
