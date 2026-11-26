<?php

namespace App\Http\Controllers;

use App\Actions\RecordKardexMovement;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Support\StockQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'estado' => 'nullable|in:Pendiente,Aprobado,Rechazado,Entregado',
            'search' => 'nullable|string|max:255',
            'desde' => 'nullable|required_with:hasta|date_format:Y-m-d|before_or_equal:'.now('America/Lima')->toDateString(),
            'hasta' => 'nullable|required_with:desde|date_format:Y-m-d|after_or_equal:desde|before_or_equal:'.now('America/Lima')->toDateString(),
        ]);
        $isAdmin = $request->user()->can('administrar-almacen');
        $query = MaterialRequest::query()
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $request->user()->id));

        $query->when($filters['desde'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($date, 'America/Lima')->startOfDay()->utc()))
            ->when($filters['hasta'] ?? null, fn ($q, $date) => $q->where('created_at', '<', \Illuminate\Support\Carbon::parse($date, 'America/Lima')->startOfDay()->addDay()->utc()))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('motivo', "%{$search}%")
                        ->orWhereHas('product', fn ($p) => $p->where(fn ($p) => $p->whereLike('nombre', "%{$search}%")->orWhereLike('codigo_sku', "%{$search}%")))
                        ->orWhereHas('user', fn ($u) => $u->whereLike('name', "%{$search}%"));
                    if (preg_match('/\A(?:SOL-)?0*([1-9][0-9]{0,17})\z/i', $search, $match)) $q->orWhere('id', $match[1]);
                });
            });
        $counts = (clone $query)->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $requests = $query->with(['user', 'product.unit', 'decisionMaker', 'movement.user'])
            ->when($filters['estado'] ?? null, fn ($query, $status) => $query->where('estado', $status))
            ->orderByRaw("CASE estado WHEN 'Pendiente' THEN 0 WHEN 'Aprobado' THEN 1 WHEN 'Entregado' THEN 2 ELSE 3 END")
            ->latest('created_at')->latest('id')->paginate(12)->withQueryString();

        $products = Product::with('unit')->orderBy('nombre')->get();

        return view('requests.index', compact('requests', 'products', 'filters', 'counts', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->withoutTrashed()],
            'cantidad' => 'required|numeric|decimal:0,2|min:0.01|max:2147483647',
            'motivo' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $quantity = StockQuantity::cents($validated['cantidad']);
            if (! $product->unit?->permite_decimales && $quantity % 100 !== 0) {
                throw ValidationException::withMessages(['cantidad' => 'Esta unidad requiere una cantidad entera.']);
            }

            MaterialRequest::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'estado' => 'Pendiente',
            ]);
        }, 3);

        return to_route('requests.index', ['estado' => 'Pendiente'])
            ->with('success', 'Solicitud enviada. Quedó pendiente de revisión.');
    }

    public function updateStatus(Request $request, MaterialRequest $materialRequest)
    {
        $validated = $request->validate([
            'estado' => 'required|in:Aprobado,Rechazado',
            'decision_note' => 'nullable|string|max:255',
        ]);

        $updated = MaterialRequest::whereKey($materialRequest->id)
            ->where('estado', 'Pendiente')
            ->update([
                ...$validated,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

        if ($updated === 0) {
            throw ValidationException::withMessages(['estado' => 'Esta solicitud ya fue atendida.']);
        }

        return back()->with('success', $validated['estado'] === 'Aprobado'
            ? 'Solicitud aprobada. Puedes despacharla desde Por entregar.'
            : 'Solicitud rechazada.');
    }

    public function dispatch(Request $request, MaterialRequest $materialRequest, RecordKardexMovement $record)
    {
        DB::transaction(function () use ($request, $materialRequest, $record) {
            $product = Product::withTrashed()->lockForUpdate()->findOrFail($materialRequest->product_id);
            $order = MaterialRequest::lockForUpdate()->findOrFail($materialRequest->id);

            if ($order->estado !== 'Aprobado' || $order->kardex_movement_id !== null) {
                throw ValidationException::withMessages(['estado' => 'Solo puedes despachar una solicitud aprobada y pendiente de entrega.']);
            }

            $quantity = $order->cantidad;
            if ($product->trashed()) {
                throw ValidationException::withMessages(['cantidad' => 'La entrega requiere un producto activo.']);
            }

            $movement = $record->record($product, [
                'tipo' => 'SALIDA',
                'cantidad' => $quantity,
                'precio_unitario' => $product->precio_unitario,
                'motivo_o_proyecto' => 'Entrega de solicitud #'.$order->id,
            ], $request->user()->id);

            $order->forceFill(['estado' => 'Entregado', 'kardex_movement_id' => $movement->id])->save();
        }, 3);

        return to_route('requests.index', ['estado' => 'Entregado'])
            ->with('success', 'Material entregado. Se registró la salida en Kardex.');
    }
    public function receipt(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($request->user()->can('administrar-almacen') || $materialRequest->user_id === $request->user()->id, 403);
        abort_unless($materialRequest->estado === 'Entregado' && $materialRequest->kardex_movement_id, 404);
        $materialRequest->load(['product.unit', 'user', 'movement.user']);

        return response()->view('requests.receipt', ['order' => $materialRequest])
            ->header('Cache-Control', 'private, no-store');
    }

}
