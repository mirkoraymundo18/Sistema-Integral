<?php
namespace App\Support;
use InvalidArgumentException;
class InstallmentMath {
    public static function minimum(int $total, string $percent): int {
        $basisPoints = Money::cents($percent);
        return intdiv($total * $basisPoints + 9999, 10000);
    }
    public static function split(int $amount, int $count): array {
        if ($count < 1 || $count > 120 || $amount < $count) throw new InvalidArgumentException('El saldo no permite ese número de cuotas.');
        $base = intdiv($amount, $count);
        $rows = array_fill(0, $count, $base);
        $rows[$count - 1] += $amount - $base * $count;
        return $rows;
    }
}
