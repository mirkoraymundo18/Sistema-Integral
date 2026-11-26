<?php
namespace App\Actions;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\LotSale;
use App\Models\RealEstateProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ConfirmLotSale {
    public function confirm(LotReservation $reservation, int $userId, ?string $notes, array $terms = []): LotSale {
        return DB::transaction(function () use ($reservation, $userId, $notes, $terms) {
            $lot = LandLot::findOrFail($reservation->land_lot_id);
            RealEstateProject::lockForUpdate()->findOrFail($lot->real_estate_project_id);
            $lot = LandLot::lockForUpdate()->findOrFail($lot->id);
            $current = LotReservation::lockForUpdate()->findOrFail($reservation->id);
            $previous = LotSale::where('lot_reservation_id', $current->id)->first();
            if ($previous) return $previous;
            if ($current->estado !== 'ACTIVA' || $current->vence_en->lessThanOrEqualTo(now())
                || (int) $current->active_lot_id !== (int) $lot->id || $lot->estado !== 'RESERVADO'
                || LotSale::where('land_lot_id', $lot->id)->exists()) {
                throw ValidationException::withMessages(['venta' => 'La reserva debe estar activa y vigente, con su lote reservado, para continuar con la venta.']);
            }
            $sale = LotSale::create([
                'lot_reservation_id' => $current->id, 'land_lot_id' => $lot->id,
                'client_id' => $current->client_id, 'precio_total' => $current->precio_acordado,
                'moneda' => $current->moneda, 'estado' => 'PENDIENTE', 'total_pagado' => 0,
                'ficha_cliente' => $current->ficha_cliente, 'ficha_lote' => $current->ficha_lote,
                'observaciones' => $notes, 'created_by' => $userId,
            ]);
            app(BuildSaleSchedule::class)->build($sale, $terms);
            $current->estado = 'CONVERTIDA';
            $current->active_lot_id = null;
            $current->save();
            $lot->estado = 'VENDIDO';
            $lot->motivo_estado = 'Venta comercial '.$sale->codigo.' desde '.$current->codigo;
            $lot->updated_by = $userId;
            $lot->version++;
            $lot->save();
            return $sale;
        }, 3);
    }
}
