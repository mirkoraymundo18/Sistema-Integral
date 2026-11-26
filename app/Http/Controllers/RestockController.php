<?php

namespace App\Http\Controllers;

use App\Exports\SpreadsheetExport;
use App\Models\Product;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    public function index(Request $request, SpreadsheetExport $export)
    {
        $request->validate(['exportar' => 'nullable|in:1']);
        $query = Product::porReponer()->with(['unit', 'family'])
            ->select('products.*')->selectRaw('stock_minimo - stock_actual as faltante');

        if ($request->query('exportar') === '1') {
            $rows = $query->lazyById(500)->map(fn (Product $product) => [
                $product->codigo_sku, $product->nombre, $product->family?->nombre ?? 'Sin clasificar',
                $product->unit?->simbolo ?? 'und.', (float) $product->stock_actual,
                (float) $product->stock_minimo, max(0, (float) $product->faltante),
            ]);
            $file = $export->write('Reposición', ['SKU', 'Producto', 'Familia', 'Unidad', 'Disponible', 'Mínimo', 'Faltante al mínimo'], [16, 35, 25, 12, 16, 16, 24], $rows);

            return response()->download($file, 'reposicion-'.now()->format('Y-m-d').'.xlsx', [
                'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend();
        }

        return view('products.restock', ['products' => $query->orderByDesc('faltante')->orderBy('id')->paginate(20)]);
    }
}
