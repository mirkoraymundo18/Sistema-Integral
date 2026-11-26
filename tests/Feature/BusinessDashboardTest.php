<?php

namespace Tests\Feature;

use App\Actions\ConfirmLotSale;
use App\Actions\CreateLotReservation;
use App\Actions\RecordKardexMovement;
use App\Actions\RecordLotPayment;
use App\Models\Client;
use App\Models\MaterialRequest;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(User $admin, string $dni, string $currency)
    {
        $client=Client::forceCreate(['dni'=>$dni,'nombres'=>'Comprador','apellidos'=>$currency,'created_by'=>$admin->id]);
        $project=RealEstateProject::create(['nombre'=>'Proyecto '.$currency,'ubicacion'=>'Huancayo']);
        $lot=$project->lots()->forceCreate(['updated_by'=>$admin->id, 'manzana'=>'A','numero'=>'01','area_m2'=>120,'precio_total'=>'1000.00','moneda'=>$currency,'estado'=>'DISPONIBLE']);
        $reservation=app(CreateLotReservation::class)->create(['token'=>(string)Str::uuid(),'land_lot_id'=>$lot->id,'client_id'=>$client->id,'fecha_vencimiento'=>'2026-11-30','precio_acordado'=>'1000.00'],$admin->id);
        return app(ConfirmLotSale::class)->confirm($reservation,$admin->id,null);
    }

    public function test_weekly_business_metrics_keep_dates_currencies_and_request_ownership_consistent(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00','America/Lima'));
        $admin=User::factory()->create(['role'=>'ADMINISTRADOR']);
        $operator=User::factory()->create(['role'=>'OPERADOR']);
        $pen=$this->purchase($admin,'71826349','PEN');
        $usd=$this->purchase($admin,'72648391','USD');
        $this->travelTo(Carbon::parse('2026-11-25 12:00:00','America/Lima'));
        foreach([[$pen,'100.00','2026-11-12'],[$pen,'250.00','2026-11-23'],[$usd,'50.00','2026-11-16']] as [$sale,$amount,$date]) {
            app(RecordLotPayment::class)->record($sale,['token'=>(string)Str::uuid(),'monto'=>$amount,'fecha_pago'=>$date,'metodo'=>'EFECTIVO'],$admin->id);
        }
        $unit=MeasurementUnit::create(['nombre'=>'Unidad','simbolo'=>'und.','permite_decimales'=>false]);
        $product=Product::create(['codigo_sku'=>'MAT-001','nombre'=>'Material','measurement_unit_id'=>$unit->id,'stock_actual'=>0,'precio_unitario'=>10]);
        DB::transaction(fn()=>app(RecordKardexMovement::class)->record($product,['tipo'=>'ENTRADA','cantidad'=>20,'precio_unitario'=>10,'motivo_o_proyecto'=>'Abastecimiento'],$admin->id));
        foreach([$admin,$operator] as $actor) MaterialRequest::forceCreate(['user_id'=>$actor->id,'product_id'=>$product->id,'cantidad'=>2,'motivo'=>'Trabajo de obra','estado'=>'Pendiente','created_at'=>'2026-11-12 16:00:00']);
        $old=MaterialRequest::forceCreate(['user_id'=>$operator->id,'product_id'=>$product->id,'cantidad'=>1,'motivo'=>'Solicitud anterior','estado'=>'Aprobado','created_at'=>'2026-11-01 16:00:00','decided_by'=>$admin->id,'decided_at'=>'2026-11-02 16:00:00']);
        $movement=DB::transaction(fn()=>app(RecordKardexMovement::class)->record($product->refresh(),['tipo'=>'SALIDA','cantidad'=>1,'precio_unitario'=>10,'motivo_o_proyecto'=>'Entrega de solicitud #'.$old->id],$admin->id));
        $movement->forceFill(['created_at'=>'2026-11-20 16:00:00'])->save();
        $old->forceFill(['estado'=>'Entregado','kardex_movement_id'=>$movement->id])->save();

        $response=$this->withoutVite()->actingAs($admin)->get(route('dashboard'))->assertOk();
        $response->assertViewHas('group','semana')->assertViewHas('period',3);
        $business=$response->viewData('business');
        $this->assertCount(3,$business['series']);
        $this->assertSame([2,0,0],array_column($business['series'],'sales'));
        $this->assertSame([100.0,0.0,250.0],array_map(fn($r)=>(float)($r['payments']['PEN']??0),$business['series']));
        $this->assertSame([0.0,50.0,0.0],array_map(fn($r)=>(float)($r['payments']['USD']??0),$business['series']));
        $this->assertSame(2,$business['totals']['requests']);
        $this->assertSame(1,$business['totals']['deliveries']);
        $this->get(route('commercial.payments.index',['desde'=>'2026-11-09','hasta'=>'2026-11-15','moneda'=>'PEN']))->assertOk()
            ->assertViewHas('payments',fn($rows)=>$rows->total()===1)->assertViewHas('totals',fn($totals)=>(float)$totals->first()->total===100.0);
        $operatorResponse=$this->actingAs($operator)->get(route('dashboard'))->assertOk();
        $this->assertSame(1,$operatorResponse->viewData('business')['totals']['requests']);
        $this->assertSame(1,$operatorResponse->viewData('business')['totals']['deliveries']);
        $this->get(route('requests.index',['desde'=>'2026-11-09','hasta'=>'2026-11-15','search'=>$operator->name]))->assertOk()->assertViewHas('requests',fn($rows)=>$rows->total()===1);
        $this->get(route('requests.index',['search'=>'SOL-'.str_pad((string)$old->id,6,'0',STR_PAD_LEFT)]))->assertOk()->assertViewHas('requests',fn($rows)=>$rows->total()===1);
    }
}
