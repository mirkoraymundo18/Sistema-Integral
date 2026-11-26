<?php
namespace App\Actions;
use App\Models\LotSale;
use App\Models\LotPayment;
use App\Models\PaymentAllocation;
use App\Support\Money;
use Illuminate\Validation\ValidationException;
class AllocateLotPayment {
    // Caller holds the sale lock within the payment/schedule transaction.
    public function allocate(LotSale $sale, LotPayment $payment): void {
        if (!$sale->installments()->exists()) return;
        $remaining = Money::cents($payment->monto);
        foreach ($sale->installments()->whereColumn('pagado','<','monto')->orderBy('numero')->lockForUpdate()->get() as $installment) {
            $paid = Money::cents($installment->pagado);
            $part = min($remaining, Money::cents($installment->monto) - $paid);
            if ($part <= 0) continue;
            PaymentAllocation::create(['lot_payment_id' => $payment->id, 'sale_installment_id' => $installment->id, 'monto' => Money::format($part)]);
            $installment->pagado = Money::format($paid + $part); $installment->save();
            $remaining -= $part;
            if ($remaining === 0) break;
        }
        if ($remaining !== 0) throw ValidationException::withMessages(['pago' => 'El cronograma no coincide con el saldo. Revisa la venta antes de continuar.']);
    }
}
