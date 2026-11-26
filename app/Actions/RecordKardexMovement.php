<?php

namespace App\Actions;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Support\StockQuantity;
use Illuminate\Validation\ValidationException;

class RecordKardexMovement
{
    // The caller holds the product row lock inside a transaction.
    public function record(Product $product, array $validated, int $userId): KardexMovement
    {
        $quantity = StockQuantity::cents($validated['cantidad']);
        $current = StockQuantity::cents($product->stock_actual);
        if ($quantity === 0 || (! $product->unit?->permite_decimales && $quantity % 100 !== 0)) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser positiva y respetar la unidad del producto.']);
        }
        $newStock = $current + ($validated['tipo'] === 'SALIDA' ? -$quantity : $quantity);
        if ($newStock < 0 || $newStock > 214748364700) {
            throw ValidationException::withMessages(['cantidad' => 'Stock insuficiente o saldo superior al máximo permitido.']);
        }
        $movement = $product->kardexMovements()->create([
            ...$validated,
            'cantidad' => StockQuantity::format($quantity),
            'user_id' => $userId,
        ]);
        $product->stock_actual = StockQuantity::format($newStock);
        $product->save();

        return $movement;
    }
}
