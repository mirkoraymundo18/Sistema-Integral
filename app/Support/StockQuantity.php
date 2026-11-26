<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class StockQuantity
{
    public static function cents(mixed $value): int
    {
        if (! preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/', (string) $value)) {
            throw ValidationException::withMessages(['cantidad' => 'Usa una cantidad positiva con hasta dos decimales.']);
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $value), 2, '');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($cents > 214748364700) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad supera el máximo permitido.']);
        }
        return $cents;
    }

    public static function format(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
