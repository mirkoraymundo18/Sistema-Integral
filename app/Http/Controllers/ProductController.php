<?php

namespace App\Http\Controllers;

use App\Actions\CreateCatalogProduct;
use App\Exports\SpreadsheetExport;
use App\Models\MaterialRequest;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('products.index', [
            'products' => $this->catalogQuery($filters)->orderBy('nombre')->orderBy('id')->paginate(15)->withQueryString(),
            'families' => ProductFamily::orderBy('nombre')->get(),
            'catalogCount' => Product::count(),
            'lowStockCount' => Product::porReponer()->count(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, SpreadsheetExport $spreadsheet)
    {
        $products = $this->catalogQuery($this->filters($request))
            ->select('products.*')->selectRaw('stock_actual * precio_unitario as valor_estimado')
            ->lazyById(500);
        $rows = $products->map(fn (Product $product) => [
            $product->codigo_sku,
            $product->nombre,
            $product->family?->nombre ?? 'Sin clasificar',
            $product->unit?->simbolo ?? 'und.',
            (float) $product->stock_actual,
            (float) $product->stock_minimo,
            (float) $product->precio_unitario,
            (float) $product->valor_estimado,
        ]);

        $file = $spreadsheet->write(
            'Existencias',
            ['SKU', 'Producto', 'Familia', 'Unidad', 'Stock actual', 'Stock mínimo', 'Precio unitario (S/)', 'Valor estimado (S/)'],
            [16, 34, 25, 14, 16, 16, 24, 24],
            $rows,
        );

        return response()->download($file, 'existencias-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend();
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:255',
            'stock' => 'nullable|in:por-reponer',
            'familia' => 'nullable|integer|min:1',
        ]);
    }

    private function catalogQuery(array $filters): Builder
    {
        $search = $filters['search'] ?? null;
        $query = Product::with(['family', 'unit']);

        if ($search !== null) {
            $query->where(function ($query) use ($search) {
                $query->whereLike('nombre', "%{$search}%")
                    ->orWhereLike('codigo_sku', "%{$search}%");
            });
        }

        if (isset($filters['familia'])) {
            $query->where('product_family_id', $filters['familia']);
        }

        if (isset($filters['stock'])) {
            $query->porReponer();
        }

        return $query;
    }

    public function show(Product $product)
    {
        $product->load(['family', 'unit']);
        $movements = $product->kardexMovements()->with('user')->latest('created_at')->latest('id')->limit(5)->get();

        return view('products.show', compact('product', 'movements'));
    }

    public function create()
    {
        return $this->form(new Product);
    }

    public function store(Request $request, CreateCatalogProduct $createProduct)
    {
        $data = $this->validatedProduct($request);

        $product = $createProduct->create($data);

        return to_route('products.show', $product)
            ->with('success', 'Producto creado. Registra la entrada inicial en Kardex.');
    }

    public function edit(Product $product)
    {
        return $this->form($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validatedProduct($request);

        DB::transaction(function () use ($product, $data) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($product->product_family_id !== null && $product->product_family_id != $data['product_family_id']) {
                throw ValidationException::withMessages([
                    'product_family_id' => 'La familia asignada se conserva para mantener la identidad del SKU.',
                ]);
            }

            if ($product->measurement_unit_id !== null && $product->measurement_unit_id != $data['measurement_unit_id']
                && ($product->stock_actual != 0 || $product->kardexMovements()->exists()
                    || MaterialRequest::where('product_id', $product->id)->exists())) {
                throw ValidationException::withMessages([
                    'measurement_unit_id' => 'La unidad se conserva cuando ya existe stock o historial.',
                ]);
            }

            $product->update($data);
        }, 3);

        return to_route('products.show', $product)->with('success', 'Ficha actualizada.');
    }

    private function form(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'families' => ProductFamily::orderBy('nombre')->get(),
            'units' => MeasurementUnit::orderBy('nombre')->get(),
        ]);
    }

    private function validatedProduct(Request $request): array
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'product_family_id' => 'required|integer|exists:product_families,id',
            'measurement_unit_id' => 'required|integer|exists:measurement_units,id',
            'stock_minimo' => 'required|numeric|decimal:0,2|min:0|max:2147483647',
            'precio_unitario' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
        ], attributes: [
            'product_family_id' => 'familia',
            'measurement_unit_id' => 'unidad de medida',
            'stock_minimo' => 'stock mínimo',
            'precio_unitario' => 'precio unitario',
        ]);
        $unit = MeasurementUnit::findOrFail($data['measurement_unit_id']);
        if (! $unit->permite_decimales && \App\Support\StockQuantity::cents($data['stock_minimo']) % 100 !== 0) {
            throw ValidationException::withMessages(['stock_minimo' => 'El mínimo debe ser entero para esta unidad.']);
        }

        return $data;
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $hasOpenRequests = MaterialRequest::where('product_id', $product->id)
                ->whereIn('estado', ['Pendiente', 'Aprobado'])->exists();

            if ($product->stock_actual != 0 || $hasOpenRequests) {
                throw ValidationException::withMessages([
                    'producto' => 'Solo se pueden archivar productos con stock cero y sin solicitudes por revisar o entregar.',
                ]);
            }

            $product->delete();
        }, 3);

        return to_route('products.index')->with('success', 'Producto archivado. Su historial sigue disponible en Kardex.');
    }
}
