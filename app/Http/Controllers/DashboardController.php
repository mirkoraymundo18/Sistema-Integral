<?php

namespace App\Http\Controllers;

use App\Models\KardexMovement;
use App\Models\Client;
use App\Models\LotReservation;
use App\Models\LotSale;
use App\Models\LotPayment;
use App\Models\SaleInstallment;
use App\Actions\ExpireLotReservations;
use App\Actions\BuildBusinessMetrics;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\MaterialRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ExpireLotReservations $expire, BuildBusinessMetrics $metrics)
    {
        $filters = $request->validate([
            'agrupacion' => 'nullable|in:semana,mes', 'rango' => 'nullable|in:reciente,personalizado',
            'periodos' => 'nullable|in:1,3,4,6,8,12', 'meses' => 'nullable|in:3,6,12',
            'desde' => 'nullable|required_if:rango,personalizado|date_format:Y-m-d',
            'hasta' => 'nullable|required_if:rango,personalizado|date_format:Y-m-d|after_or_equal:desde|before_or_equal:'.now('America/Lima')->toDateString(),
        ]);
        $period = (int) ($filters['periodos'] ?? $filters['meses'] ?? 3);
        $group = $filters['agrupacion'] ?? (isset($filters['meses']) ? 'mes' : 'semana');
        $custom = ($filters['rango'] ?? 'reciente') === 'personalizado';
        $today = now('America/Lima')->startOfDay();
        $from = $custom ? Carbon::parse($filters['desde'], 'America/Lima')->startOfDay()
            : ($group === 'semana' ? $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks($period - 1) : $today->copy()->startOfMonth()->subMonths($period - 1));
        $until = $custom ? Carbon::parse($filters['hasta'], 'America/Lima')->startOfDay() : $today->copy();
        if ($from->diffInDays($until) >= 366) {
            throw ValidationException::withMessages(['desde' => 'Selecciona un rango de hasta 366 días.']);
        }
        $utcFrom = $from->copy()->utc();
        $utcEnd = $until->copy()->addDay()->utc();
        $periodLabel = $from->format('d/m/Y').' al '.$until->format('d/m/Y');
        $isAdmin = $request->user()->can('administrar-almacen');

        $inventory = Product::selectRaw('COUNT(*) AS total, COALESCE(SUM(stock_actual * precio_unitario), 0) AS valor')
            ->first();
        $lowStock = Product::porReponer();
        $lowStockCount = (clone $lowStock)->count();
        $restock = $lowStock->with('unit')->orderBy('stock_actual')->orderBy('nombre')->limit(6)->get();

        $requests = MaterialRequest::whereIn('estado', ['Pendiente', 'Aprobado'])
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $request->user()->id))
            ->selectRaw('estado, COUNT(*) AS total')->groupBy('estado')->pluck('total', 'estado');

        // Aggregate at most one year by Peru calendar date, then build week/month buckets.
        $dayExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at, '-5 hours')"
            : "to_char(created_at - interval '5 hours', 'YYYY-MM-DD')";
        $daily = KardexMovement::where('created_at', '>=', $utcFrom)->where('created_at', '<', $utcEnd)
            ->selectRaw("$dayExpression AS dia, tipo, COUNT(*) AS total")->groupByRaw("$dayExpression, tipo")->get();
        $totals = [];
        foreach ($daily as $row) {
            $day = Carbon::parse($row->dia, 'America/Lima');
            $key = ($group === 'semana' ? $day->startOfWeek(Carbon::MONDAY) : $day->startOfMonth())->toDateString();
            $totals[$key][$row->tipo] = ($totals[$key][$row->tipo] ?? 0) + (int) $row->total;
        }
        $months = [];
        $cursor = $group === 'semana' ? $from->copy()->startOfWeek(Carbon::MONDAY) : $from->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($until)) {
            $bucketEnd = $group === 'semana' ? $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay() : $cursor->copy()->endOfMonth()->startOfDay();
            $bucketFrom = $cursor->greaterThan($from) ? $cursor->copy() : $from->copy();
            $bucketTo = $bucketEnd->lessThan($until) ? $bucketEnd : $until->copy();
            $counts = $totals[$cursor->toDateString()] ?? [];
            $months[] = [
                'label' => $group === 'semana' ? $bucketFrom->format('d/m').' – '.$bucketTo->format('d/m') : $cursor->copy()->locale('es')->isoFormat('MMM YY'),
                'from' => $bucketFrom->toDateString(), 'to' => $bucketTo->toDateString(),
                'entries' => (int) ($counts['ENTRADA'] ?? 0), 'exits' => (int) ($counts['SALIDA'] ?? 0),
            ];
            $group === 'semana' ? $cursor->addWeek() : $cursor->addMonth();
        }
        $expire->run();
        $commercial = [
            'clients' => Client::count(),
            'reservations' => LotReservation::where('estado', 'ACTIVA')->count(),
            'sales' => LotSale::where('created_at', '>=', $utcFrom)->where('created_at', '<', $utcEnd)->count(),
            'payments' => LotPayment::whereBetween('fecha_pago', [$from->toDateString(), $until->toDateString()])
                ->join('lot_sales', 'lot_sales.id', '=', 'lot_payments.lot_sale_id')
                ->selectRaw('lot_sales.moneda, SUM(lot_payments.monto) AS total')->groupBy('lot_sales.moneda')->get(),
            'balances' => LotSale::where('estado', 'PENDIENTE')->selectRaw('moneda, SUM(precio_total - total_pagado) AS total')->groupBy('moneda')->get(),
        ];

        $overdueQuery = SaleInstallment::whereColumn('pagado','<','monto')->where('vence_el','<',$today->toDateString());
        $overdueCount = (clone $overdueQuery)->count();
        $overdueAmounts = (clone $overdueQuery)->join('lot_sales','lot_sales.id','=','sale_installments.lot_sale_id')
            ->selectRaw('lot_sales.moneda, SUM(sale_installments.monto - sale_installments.pagado) AS total')->groupBy('lot_sales.moneda')->get();
        $upcoming = SaleInstallment::with('sale')->whereColumn('pagado','<','monto')
            ->whereBetween('vence_el',[$today->toDateString(),$today->copy()->addDays(7)->toDateString()])->orderBy('vence_el')->orderBy('id')->limit(5)->get();

        $chartMax = max(1, max(array_column($months, 'entries')), max(array_column($months, 'exits')));
        $recent = KardexMovement::with(['product.unit', 'user'])->latest('created_at')->latest('id')->limit(5)->get();
        $business = $metrics->build($from, $until, $group, $months, $isAdmin, (int)$request->user()->id);

        return view('dashboard', compact(
            'business', 'overdueCount', 'overdueAmounts', 'upcoming', 'filters', 'group', 'custom', 'periodLabel', 'commercial', 'inventory', 'lowStockCount', 'restock', 'requests', 'isAdmin', 'period', 'months', 'chartMax', 'recent',
        ));
    }
}
