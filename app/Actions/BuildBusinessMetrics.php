<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\LandLot;
use App\Models\LotPayment;
use App\Models\LotReservation;
use App\Models\LotSale;
use App\Models\MaterialRequest;
use App\Models\RealEstateProject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BuildBusinessMetrics
{
    public function build(Carbon $from, Carbon $until, string $group, array $buckets, bool $isAdmin, int $userId): array
    {
        $utcFrom = $from->copy()->utc();
        $utcEnd = $until->copy()->addDay()->utc();
        $rows = [];
        foreach ($buckets as $bucket) {
            $key = $this->key($bucket['from'], $group);
            $rows[$key] = [...$bucket, 'clients' => 0, 'reservations' => 0, 'sales' => 0, 'requests' => 0, 'deliveries' => 0, 'payments' => []];
        }
        $sources = [
            'clients' => Client::query(),
            'reservations' => LotReservation::query(),
            'sales' => LotSale::query(),
            'requests' => MaterialRequest::query()->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId)),
        ];
        foreach ($sources as $metric => $query) {
            $day = $this->day('created_at');
            $daily = $query->where('created_at', '>=', $utcFrom)->where('created_at', '<', $utcEnd)
                ->selectRaw("{$day} AS dia, COUNT(*) AS total")->groupByRaw($day)->get();
            foreach ($daily as $item) {
                $key = $this->key($item->dia, $group);
                if (isset($rows[$key])) $rows[$key][$metric] += (int)$item->total;
            }
        }
        $day = $this->day('delivery.created_at');
        $delivered = MaterialRequest::where('estado', 'Entregado')
            ->when(!$isAdmin, fn ($q) => $q->where('material_requests.user_id', $userId))
            ->join('kardex_movements as delivery', 'delivery.id', '=', 'material_requests.kardex_movement_id')
            ->where('delivery.created_at', '>=', $utcFrom)->where('delivery.created_at', '<', $utcEnd)
            ->selectRaw("{$day} AS dia, COUNT(*) AS total")->groupByRaw($day)->get();
        foreach ($delivered as $item) {
            $key = $this->key($item->dia, $group);
            if (isset($rows[$key])) $rows[$key]['deliveries'] += (int)$item->total;
        }

        $dailyPayments = LotPayment::join('lot_sales', 'lot_sales.id', '=', 'lot_payments.lot_sale_id')
            ->whereDate('lot_payments.fecha_pago', '>=', $from->toDateString())->whereDate('lot_payments.fecha_pago', '<=', $until->toDateString())
            ->selectRaw('DATE(lot_payments.fecha_pago) AS dia, lot_sales.moneda, SUM(lot_payments.monto) AS total')
            ->groupByRaw('DATE(lot_payments.fecha_pago), lot_sales.moneda')->get();
        $currencies = [];
        $cashTotals = [];
        foreach ($dailyPayments as $payment) {
            $key = $this->key($payment->dia, $group);
            if (!isset($rows[$key])) continue;
            $currency = $payment->moneda;
            // Display-only sums. Ledger amounts and payment allocation are not modified.
            $rows[$key]['payments'][$currency] = ($rows[$key]['payments'][$currency] ?? 0) + (float)$payment->total;
            $cashTotals[$currency] = ($cashTotals[$currency] ?? 0) + (float)$payment->total;
            $currencies[$currency] = true;
        }
        $saleValues = LotSale::where('created_at', '>=', $utcFrom)->where('created_at', '<', $utcEnd)
            ->selectRaw('moneda, SUM(precio_total) AS total')->groupBy('moneda')->get();
        $cohortConverted = LotReservation::where('created_at', '>=', $utcFrom)->where('created_at', '<', $utcEnd)->where('estado', 'CONVERTIDA')->count();
        $series = array_values($rows);
        $totals = [];
        foreach (array_keys($sources) as $metric) $totals[$metric] = array_sum(array_column($series, $metric));
        $totals['deliveries'] = array_sum(array_column($series, 'deliveries'));
        $currencyKeys = array_keys($currencies);
        sort($currencyKeys);
        $cashMax = [];
        foreach ($currencyKeys as $currency) $cashMax[$currency] = max(1, ...array_map(fn ($row) => $row['payments'][$currency] ?? 0, $series));
        $lotStates = LandLot::selectRaw('estado, COUNT(*) AS total')->groupBy('estado')->pluck('total', 'estado');
        $projects = RealEstateProject::withCount([
            'lots', 'lots as available_count' => fn ($q) => $q->where('estado', 'DISPONIBLE'),
            'lots as reserved_count' => fn ($q) => $q->where('estado', 'RESERVADO'),
            'lots as sold_count' => fn ($q) => $q->where('estado', 'VENDIDO'),
        ])->orderByDesc('sold_count')->orderBy('nombre')->limit(6)->get();
        $latestPayments = LotPayment::with(['sale', 'creator'])
            ->whereDate('fecha_pago', '>=', $from->toDateString())->whereDate('fecha_pago', '<=', $until->toDateString())
            ->orderByDesc('fecha_pago')->orderByDesc('id')->limit(5)->get();

        return compact('series', 'totals', 'cashTotals', 'currencyKeys', 'cashMax', 'saleValues', 'cohortConverted', 'lotStates', 'projects', 'latestPayments');
    }

    private function day(string $column): string
    {
        return DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m-%d', {$column}, '-5 hours')" : "to_char({$column} - interval '5 hours', 'YYYY-MM-DD')";
    }

    private function key(string $day, string $group): string
    {
        $date = Carbon::parse($day, 'America/Lima');
        return ($group === 'semana' ? $date->startOfWeek(Carbon::MONDAY) : $date->startOfMonth())->toDateString();
    }
}
