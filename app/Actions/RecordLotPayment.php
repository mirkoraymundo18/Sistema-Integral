<?php
namespace App\Actions;
use App\Models\LotPayment;
use App\Models\LotSale;
use App\Support\Money;
use App\Support\PaymentProposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RecordLotPayment {
    public function record(LotSale $sale, array $data, int $userId): LotPayment {
        return DB::transaction(function () use ($sale, $data, $userId) {
            $current = LotSale::lockForUpdate()->findOrFail($sale->id);
            $previous = LotPayment::where('token', $data['token'])->first();
            if ($previous) {
                if ((int) $previous->lot_sale_id !== (int) $current->id) {
                    throw ValidationException::withMessages(['pago' => 'Vuelve a abrir la ficha para registrar otro pago.']);
                }
                return $previous;
            }
            $amount = Money::cents($data['monto']);
            $paid = Money::cents($current->total_pagado);
            $total = Money::cents($current->precio_total);
            if (isset($data['saldo_esperado']) && Money::cents($data['saldo_esperado']) !== $total - $paid) {
                throw ValidationException::withMessages(['pago' => 'El saldo cambió mientras revisabas el pago. Recarga la venta antes de confirmar.']);
            }
            $mode = $data['modo_pago'] ?? 'LIBRE';
            if (!in_array($mode, ['LIBRE','SIGUIENTE','ADELANTO','SALDO'], true)) {
                throw ValidationException::withMessages(['modo_pago' => 'Selecciona una operación válida.']);
            }
            if ($mode !== 'LIBRE') {
                $rows = $current->installments()->whereColumn('pagado','<','monto')->orderBy('numero')->lockForUpdate()->get()
                    ->map(fn ($row) => ['numero' => $row->numero, 'saldo_centimos' => Money::cents($row->saldo)])->all();
                if ($mode === 'ADELANTO' && $current->modalidad !== 'CUOTAS') {
                    throw ValidationException::withMessages(['modo_pago' => 'Los adelantos por cuotas requieren una venta con cronograma mensual.']);
                }
                try {
                    $expected = PaymentProposal::amount($mode, $rows, $total - $paid, (int) ($data['cantidad_cuotas'] ?? 1));
                } catch (\InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['cantidad_cuotas' => $e->getMessage()]);
                }
                if (!isset($data['saldo_esperado']) || Money::cents($data['saldo_esperado']) !== $total - $paid || $amount !== $expected) {
                    throw ValidationException::withMessages(['pago' => 'El saldo o las cuotas cambiaron. Recarga la venta y revisa el importe antes de confirmar.']);
                }
            }
            if ($amount <= 0 || $amount > $total - $paid) {
                throw ValidationException::withMessages(['monto' => 'El pago debe ser positivo y no superar el saldo pendiente.']);
            }
            if (!empty($data['referencia']) && $current->payments()->where('metodo', $data['metodo'])->where('referencia', $data['referencia'])->exists()) {
                throw ValidationException::withMessages(['referencia' => 'Esta referencia ya está registrada para la venta y el método elegidos.']);
            }
            unset($data['saldo_esperado']);
            $data['modo_pago'] = $mode;
            $data['cantidad_cuotas'] = $mode === 'ADELANTO' ? (int) ($data['cantidad_cuotas'] ?? 1) : null;
            $payment = $current->payments()->create([...$data, 'created_by' => $userId]);
            app(AllocateLotPayment::class)->allocate($current, $payment);
            $current->total_pagado = Money::format($paid + $amount);
            $current->estado = $paid + $amount === $total ? 'PAGADA' : 'PENDIENTE';
            $current->save();
            return $payment;
        }, 3);
    }
}
