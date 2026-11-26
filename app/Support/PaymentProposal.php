<?php
namespace App\Support;
use InvalidArgumentException;
class PaymentProposal {
    // Rows are the unpaid installments of one sale, ordered by number. Values are cents.
    public static function amount(string $mode, array $rows, int $balance, int $count = 1): int {
        if ($mode === 'SALDO') return $balance;
        if ($mode === 'SIGUIENTE') {
            if (!$rows) throw new InvalidArgumentException('Esta venta no tiene vencimientos pendientes.');
            return (int) $rows[0]['saldo_centimos'];
        }
        if ($mode !== 'ADELANTO') throw new InvalidArgumentException('Selecciona una operación válida.');
        $monthly = array_values(array_filter($rows, fn ($row) => (int) $row['numero'] > 0));
        if ($count < 1 || $count > 120 || $count > count($monthly)) {
            throw new InvalidArgumentException('Selecciona una cantidad válida de cuotas pendientes.');
        }
        $amount = 0;
        foreach ($rows as $row) {
            if ((int) $row['numero'] === 0) $amount += (int) $row['saldo_centimos'];
        }
        foreach (array_slice($monthly,0,$count) as $row) $amount += (int) $row['saldo_centimos'];
        return $amount;
    }
}
