<?php

namespace App\Http\Controllers;

use App\Actions\RecordKardexMovement;
use App\Exports\KardexExport;
use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $operation = $request->query('operacion') === 'SALIDA' ? 'SALIDA' : 'ENTRADA';
        $products = Product::withTrashed()->with('unit')->orderBy('nombre')->get();
        $activeProducts = $products->reject(fn (Product $product) => $product->trashed());
        $movements = $this->movements($filters)->paginate(25)->withQueryString();

        return view('kardex.index', compact('products', 'activeProducts', 'movements', 'filters', 'operation'));
    }

    public function export(Request $request, KardexExport $export)
    {
        $movements = $this->movements($this->filters($request))->lazyByIdDesc(500);

        return response()->download($export->write($movements), 'kardex-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend();
    }

    private function filters(Request $request): array
    {
        return array_filter($request->validate([
            'product_id' => 'nullable|integer|min:1|max:9223372036854775807',
            'movimiento' => 'nullable|integer|min:1|max:9223372036854775807',
            'tipo' => 'nullable|in:ENTRADA,SALIDA',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
        ]));
    }

    private function movements(array $filters): Builder
    {
        return KardexMovement::with(['product.unit', 'user'])
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->where('product_id', $id))
            ->when($filters['movimiento'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filters['tipo'] ?? null, fn ($query, $type) => $query->where('tipo', $type))
            ->when($filters['desde'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date, 'America/Lima')->startOfDay()->utc()))
            ->when($filters['hasta'] ?? null, fn ($query, $date) => $query->where('created_at', '<', Carbon::parse($date, 'America/Lima')->addDay()->startOfDay()->utc()))
            ->latest('created_at')->latest('id');
    }

    public function store(Request $request, RecordKardexMovement $record)
    {
        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->withoutTrashed()],
            'tipo' => 'required|in:ENTRADA,SALIDA',
            'cantidad' => 'required|numeric|decimal:0,2|min:0.01|max:2147483647',
            'precio_unitario' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'motivo_o_proyecto' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated, $record) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $record->record($product, $validated, $request->user()->id);
        }, 3);

        return back()->with('success', 'Movimiento registrado en Kardex correctamente.')
            ->with('kardex_operation', $validated['tipo']);
    }
}
