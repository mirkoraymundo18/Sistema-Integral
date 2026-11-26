<?php
namespace App\Support;
use InvalidArgumentException;
class Money {
    public static function cents(mixed $value): int {
        if (!preg_match('/\A[0-9]{1,12}(?:\.[0-9]{1,2})?\z/', (string) $value)) {
            throw new InvalidArgumentException('Importe inválido. Usa hasta dos decimales.');
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $value), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
    public static function format(int $cents): string {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
