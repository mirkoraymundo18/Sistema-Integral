<?php
namespace App\Actions;
use App\Models\Product;
use App\Models\LotSale;
use App\Models\LotPayment;
use App\Models\LotReservation;
use App\Support\Money;
use App\Support\StockQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class CheckSystemIntegrity {
    public function check(): array {
        $report=['checked_at'=>now('America/Lima')->format('d/m/Y H:i'),'counts'=>['productos'=>0,'reservas'=>0,'ventas'=>0,'pagos'=>0],'errors'=>0,'warnings'=>0,'issues'=>[]];
        $issue=function(string $level,string $code,string $detail) use (&$report):void {
            $report[$level==='error'?'errors':'warnings']++;
            if(count($report['issues'])<100) $report['issues'][]=compact('level','code','detail');
        };
        foreach(['products','kardex_movements','lot_reservations','lot_sales','lot_payments','sale_installments','payment_allocations'] as $table) {
            if(!Schema::hasTable($table)) $issue('error','tablas','Falta la tabla '.$table.'. Ejecuta la actualización.');
        }
        if($report['errors']) return $report;
        return DB::transaction(function() use (&$report,$issue) {
            if(DB::getDriverName()==='pgsql' && DB::transactionLevel()===1) DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
            Product::withTrashed()->withCount('kardexMovements')
                ->withSum(['kardexMovements as entradas'=>fn($q)=>$q->where('tipo','ENTRADA')],'cantidad')
                ->withSum(['kardexMovements as salidas'=>fn($q)=>$q->where('tipo','SALIDA')],'cantidad')
                ->chunkById(100,function($products) use (&$report,$issue) {
                    foreach($products as $product) {
                        $report['counts']['productos']++;
                        try {
                            $stock=StockQuantity::cents($product->stock_actual);
                            if($product->kardex_movements_count && $stock!==Money::cents($product->entradas??0)-Money::cents($product->salidas??0)) {
                                $issue('warning','stock.conciliar','Producto '.$product->codigo_sku.': el saldo difiere del Kardex. Revisa si existe saldo inicial anterior al sistema.');
                            } elseif(!$product->kardex_movements_count && $stock>0) {
                                $issue('warning','stock.inicial','Producto '.$product->codigo_sku.': tiene saldo sin movimientos registrados.');
                            }
                        } catch(\Throwable) {$issue('error','stock.formato','Producto '.$product->codigo_sku.': cantidad inválida.');}
                    }
                });
            LotReservation::with(['lot','sale'])->chunkById(100,function($reservations) use (&$report,$issue) {
                foreach($reservations as $reservation) {
                    $report['counts']['reservas']++;
                    if($reservation->estado==='ACTIVA') {
                        if((int)$reservation->active_lot_id!==(int)$reservation->land_lot_id || $reservation->lot?->estado!=='RESERVADO' || $reservation->sale) $issue('error','reserva.lote',$reservation->codigo.': no coincide la reserva activa con el estado del lote.');
                        if($reservation->vence_en->lessThanOrEqualTo(now())) $issue('warning','reserva.vencimiento',$reservation->codigo.': venció y está pendiente de procesar; revisa el programador.');
                    } elseif($reservation->active_lot_id!==null) $issue('error','reserva.bloqueo',$reservation->codigo.': conserva un bloqueo de lote sin estar activa.');
                    if($reservation->estado==='CONVERTIDA' && !$reservation->sale) $issue('error','reserva.venta',$reservation->codigo.': está convertida, pero no tiene venta.');
                }
            });
            LotSale::with(['lot','reservation'])->withCount('installments')->withSum('payments','monto')
                ->withSum('installments','monto')->withSum('installments','pagado')->chunkById(100,function($sales) use (&$report,$issue) {
                    foreach($sales as $sale) {
                        $report['counts']['ventas']++;
                        try {
                            $total=Money::cents($sale->precio_total);$paid=Money::cents($sale->total_pagado);
                            if($paid>$total || Money::cents($sale->payments_sum_monto??0)!==$paid) $issue('error','venta.pagos',$sale->codigo.': total pagado no coincide con el historial o supera el precio.');
                            if(($paid===$total)!==($sale->estado==='PAGADA')) $issue('error','venta.estado',$sale->codigo.': estado de pago inconsistente.');
                            if($sale->installments_count && (Money::cents($sale->installments_sum_monto??0)!==$total || Money::cents($sale->installments_sum_pagado??0)!==$paid)) $issue('error','venta.cronograma',$sale->codigo.': el cronograma no coincide con el precio o con los abonos.');
                        } catch(\Throwable) {$issue('error','venta.importe',$sale->codigo.': importe inválido.');}
                        if($sale->lot?->estado!=='VENDIDO' || $sale->reservation?->estado!=='CONVERTIDA') $issue('error','venta.lote',$sale->codigo.': lote o reserva no reflejan la venta.');
                        if(!$sale->installments_count && $sale->estado==='PENDIENTE') $issue('warning','venta.sin_cronograma',$sale->codigo.': venta anterior pendiente de definir contado o cuotas.');
                    }
                });
            LotPayment::with(['sale'=>fn($q)=>$q->withCount('installments'),'allocations.installment'])->chunkById(100,function($payments) use (&$report,$issue) {
                foreach($payments as $payment) {
                    $report['counts']['pagos']++;
                    try {
                        $applied=0;
                        foreach($payment->allocations as $part) {
                            $applied+=Money::cents($part->monto);
                            if((int)$part->installment?->lot_sale_id!==(int)$payment->lot_sale_id) $issue('error','pago.venta',$payment->codigo.': una aplicación pertenece a otra compra.');
                        }
                        if($payment->sale?->installments_count && $applied!==Money::cents($payment->monto)) $issue('error','pago.aplicacion',$payment->codigo.': el importe no coincide con lo aplicado al cronograma.');
                    } catch(\Throwable) {$issue('error','pago.importe',$payment->codigo.': importe inválido.');}
                }
            });
            return $report;
        });
    }
}
