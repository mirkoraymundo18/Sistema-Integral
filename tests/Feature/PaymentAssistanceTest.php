<?php
namespace Tests\Feature;
use App\Actions\ConfirmLotSale;
use App\Actions\CreateLotReservation;
use App\Models\Client;
use App\Models\FinancingPlan;
use App\Models\LotSale;
use App\Models\User;
use App\Models\RealEstateProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
class PaymentAssistanceTest extends TestCase {
    use RefreshDatabase;
    private function sale(int $number = 1): LotSale {
        $this->travelTo(Carbon::parse('2027-01-10 12:00:00','America/Lima'));
        $admin=User::where('role','ADMINISTRADOR')->first() ?? User::factory()->create(['role'=>'ADMINISTRADOR']);
        $client=new Client(['dni'=>(string)(71200000+$number),'nombres'=>'Cliente '.$number,'apellidos'=>'De prueba']);$client->created_by=$admin->id;$client->save();
        $project=RealEstateProject::create(['nombre'=>'Proyecto '.$number,'ubicacion'=>'Huancayo']);
        $lot=$project->lots()->forceCreate(['updated_by'=>$admin->id, 'manzana'=>'A','numero'=>'01','area_m2'=>100,'precio_total'=>10000,'moneda'=>'PEN','estado'=>'DISPONIBLE']);
        $reservation=app(CreateLotReservation::class)->create(['token'=>(string)Str::uuid(),'client_id'=>$client->id,'land_lot_id'=>$lot->id,'fecha_vencimiento'=>'2027-01-15','precio_acordado'=>10000],$admin->id);
        $plan=FinancingPlan::firstOrCreate(['nombre'=>'Plan de prueba tres meses'],['meses'=>3,'inicial_minima_porcentaje'=>10,'activo'=>true]);
        $sale=app(ConfirmLotSale::class)->confirm($reservation,$admin->id,null,['modalidad'=>'CUOTAS','financing_plan_id'=>$plan->id,'inicial'=>'1000.00','primera_cuota'=>'2027-02-10']);
        $this->withoutVite()->actingAs($admin);return $sale;
    }
    private function payment(array $data=[]): array {
        return [...['token'=>(string)Str::uuid(),'monto'=>'1000.00','fecha_pago'=>'2027-01-10','metodo'=>'EFECTIVO'],...$data];
    }
    public function test_suggests_initial_then_next_installment_and_advances_without_changing_due_dates(): void {
        $sale=$this->sale();$other=$this->sale(2);
        $dates=$sale->installments()->orderBy('numero')->get()->map(fn($row)=>$row->vence_el->toDateString())->all();
        $this->get(route('commercial.sales.show',$sale))->assertOk()->assertViewHas('suggestedAmount','1000.00')->assertSee('2027-01-10');
        $initial=$this->payment(['modo_pago'=>'SIGUIENTE','saldo_esperado'=>'10000.00']);
        $this->post(route('commercial.sales.payment',$sale),$initial)->assertSessionHasNoErrors();
        $this->post(route('commercial.sales.payment',$sale),$initial)->assertSessionHasNoErrors();
        $this->assertSame(1,$sale->payments()->count());
        $this->get(route('commercial.sales.show',$sale))->assertOk()->assertViewHas('suggestedAmount','3000.00');
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'ADELANTO','cantidad_cuotas'=>2,'saldo_esperado'=>'9000.00','monto'=>'6000.00']))->assertSessionHasNoErrors();
        $this->assertSame('3000.00',$sale->refresh()->saldo);
        $rows=$sale->installments()->orderBy('numero')->get();
        $this->assertSame('Pagada',$rows[1]->estado);$this->assertSame('Pagada',$rows[2]->estado);
        $this->assertSame('Pendiente',$rows[3]->estado);
        $this->assertSame($dates,$rows->map(fn($row)=>$row->vence_el->toDateString())->all());
        $this->assertSame('0.00',$other->refresh()->total_pagado);
        $this->post(route('commercial.sales.payment',$other),$initial)->assertSessionHasErrors('pago');
        $this->assertSame(0,$other->payments()->count());$this->travelBack();
    }
    public function test_advance_includes_pending_initial_and_rejects_changed_amounts_and_stale_balances(): void {
        $sale=$this->sale();
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'ADELANTO','cantidad_cuotas'=>2,'saldo_esperado'=>'10000.00','monto'=>'6000.00']))->assertSessionHasErrors('pago');
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'ADELANTO','cantidad_cuotas'=>4,'saldo_esperado'=>'10000.00','monto'=>'10000.00']))->assertSessionHasErrors('cantidad_cuotas');
        $this->assertSame(0,$sale->payments()->count());
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'LIBRE','monto'=>'100.00','saldo_esperado'=>'10000.00']))->assertSessionHasNoErrors();
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'SIGUIENTE','saldo_esperado'=>'10000.00']))->assertSessionHasErrors('pago');
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'LIBRE','monto'=>'50.00','saldo_esperado'=>'10000.00']))->assertSessionHasErrors('pago');
        $this->assertSame(1,$sale->payments()->count());
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'ADELANTO','cantidad_cuotas'=>2,'saldo_esperado'=>'9900.00','monto'=>'6900.00']))->assertSessionHasNoErrors();
        $this->assertSame('3000.00',$sale->refresh()->saldo);
        $this->assertSame('ADELANTO',$sale->payments()->latest('id')->first()->modo_pago);
        $this->post(route('commercial.sales.payment',$sale),$this->payment(['modo_pago'=>'SALDO','monto'=>'3000.00','saldo_esperado'=>'3000.00']))->assertSessionHasNoErrors();
        $this->assertSame('PAGADA',$sale->refresh()->estado);$this->assertSame('0.00',$sale->saldo);$this->travelBack();
    }
}
