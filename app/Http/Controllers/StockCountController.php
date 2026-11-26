<?php

namespace App\Http\Controllers;

use App\Actions\RecordKardexMovement;
use App\Models\Product;
use App\Models\StockCount;
use App\Support\StockQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockCountController extends Controller
{
    public function index()
    {
        return view('counts.index', [
            'products' => Product::with('unit')->orderBy('nombre')->get(),
            'counts' => StockCount::with(['product.unit', 'user'])->latest('id')->paginate(15),
        ]);
    }

    public function store(Request $request, RecordKardexMovement $record)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->withoutTrashed()],
            'cantidad' => 'required|numeric|decimal:0,2|min:0|max:2147483647',
            'saldo_esperado' => 'required|numeric|decimal:0,2|min:0|max:2147483647',
            'motivo' => 'required|string|max:255',
        ]);
        DB::transaction(function () use ($request, $record, $data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $current = StockQuantity::cents($product->stock_actual);
            $counted = StockQuantity::cents($data['cantidad']);
            if ($current !== StockQuantity::cents($data['saldo_esperado'])) {
                throw ValidationException::withMessages(['cantidad' => 'El stock cambió mientras contabas. Actualiza la pantalla y verifica el conteo.']);
            }
            if (! $product->unit?->permite_decimales && $counted % 100 !== 0) {
                throw ValidationException::withMessages(['cantidad' => 'Esta unidad requiere una cantidad entera.']);
            }
            $count = StockCount::create([
                'product_id' => $product->id, 'user_id' => $request->user()->id,
                'saldo_anterior' => $product->stock_actual,
                'cantidad_contada' => StockQuantity::format($counted), 'motivo' => $data['motivo'],
            ]);
            $difference = $counted - $current;
            if ($difference !== 0) {
                $movement = $record->record($product, [
                    'tipo' => $difference > 0 ? 'ENTRADA' : 'SALIDA',
                    'cantidad' => StockQuantity::format(abs($difference)),
                    'precio_unitario' => $product->precio_unitario,
                    'motivo_o_proyecto' => 'Ajuste por conteo #'.$count->id,
                ], $request->user()->id);
                $count->kardex_movement_id = $movement->id;
                $count->save();
            }
        }, 3);

        return to_route('counts.index')->with('success', 'Conteo guardado y stock conciliado.');
    }
}
