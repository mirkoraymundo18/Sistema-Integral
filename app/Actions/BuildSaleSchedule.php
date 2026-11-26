<?php
namespace App\Actions;
use App\Models\LotSale;
use App\Models\FinancingPlan;
use App\Support\Money;
use App\Support\InstallmentMath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BuildSaleSchedule {
    public function __construct(private AllocateLotPayment $allocator) {}
    public function build(LotSale $sale, array $terms): void {
        DB::transaction(function () use ($sale, $terms) {
            $current = LotSale::lockForUpdate()->findOrFail($sale->id);
            if ($current->installments()->exists()) {
                throw ValidationException::withMessages(['cronograma' => 'Esta venta ya tiene un cronograma. Se conservan sus condiciones originales.']);
            }
            $total = Money::cents($current->precio_total);
            $saleDate = $current->created_at->copy()->timezone('America/Lima')->toDateString();
            $mode = $terms['modalidad'] ?? 'CONTADO';
            if (!in_array($mode, ['CONTADO','CUOTAS'], true)) throw ValidationException::withMessages(['modalidad' => 'Selecciona contado o cuotas.']);
            $current->financing_plan_id = null;
            $snapshot = ['modalidad' => $mode, 'intereses' => '0.00', 'plan' => 'Contado'];
            if ($mode === 'CONTADO') {
                $due = $terms['fecha_contado'] ?? now('America/Lima')->toDateString();
                if ($due < $saleDate) throw ValidationException::withMessages(['fecha_contado' => 'El vencimiento no puede ser anterior a la venta.']);
                $current->installments()->create(['numero' => 1, 'vence_el' => $due, 'monto' => Money::format($total)]);
                $snapshot['vencimiento'] = $due;
            } else {
                $plan = FinancingPlan::where('activo', true)->lockForUpdate()->find($terms['financing_plan_id'] ?? null);
                if (!$plan) throw ValidationException::withMessages(['financing_plan_id' => 'Selecciona un plan activo.']);
                $initial = Money::cents($terms['inicial'] ?? '0');
                $minimum = InstallmentMath::minimum($total, $plan->inicial_minima_porcentaje);
                if ($initial < $minimum || $initial >= $total || $total - $initial < $plan->meses) {
                    throw ValidationException::withMessages(['inicial' => 'La inicial debe cubrir el mínimo del plan, ser menor al precio total y dejar saldo para todas las cuotas.']);
                }
                $first = Carbon::parse($terms['primera_cuota'], 'America/Lima')->startOfDay();
                if ($first->toDateString() <= $saleDate || $first->toDateString() <= now('America/Lima')->toDateString()) {
                    throw ValidationException::withMessages(['primera_cuota' => 'La primera cuota debe vencer después de hoy y de la fecha de venta.']);
                }
                if ($initial > 0) $current->installments()->create(['numero' => 0, 'vence_el' => $saleDate, 'monto' => Money::format($initial)]);
                foreach (InstallmentMath::split($total - $initial, $plan->meses) as $offset => $amount) {
                    $current->installments()->create(['numero' => $offset + 1, 'vence_el' => $first->copy()->addMonthsNoOverflow($offset)->toDateString(), 'monto' => Money::format($amount)]);
                }
                $current->financing_plan_id = $plan->id;
                $snapshot = [...$snapshot, 'plan' => $plan->nombre, 'meses' => $plan->meses, 'inicial_minima_porcentaje' => $plan->inicial_minima_porcentaje, 'inicial' => Money::format($initial), 'primera_cuota' => $first->toDateString()];
            }
            $current->modalidad = $mode;
            $current->condiciones_financiamiento = $snapshot; $current->save();
            $historical = $current->payments()->orderBy('fecha_pago')->orderBy('id')->get();
            if ($historical->sum(fn ($payment) => Money::cents($payment->monto)) !== Money::cents($current->total_pagado)) {
                throw ValidationException::withMessages(['cronograma' => 'Los pagos anteriores no coinciden con el total pagado de la venta.']);
            }
            foreach ($historical as $payment) $this->allocator->allocate($current, $payment);
        }, 3);
    }
}
