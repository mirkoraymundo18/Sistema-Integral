<?php

namespace App\Actions;

use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\RealEstateProject;
use Illuminate\Support\Facades\DB;

class ExpireLotReservations
{
    // Caller already holds the project and lot locks.
    public function releaseForLot(LandLot $lot): void
    {
        $reservation = LotReservation::where('active_lot_id', $lot->id)->lockForUpdate()->first();
        if ($reservation && $reservation->vence_en->lessThanOrEqualTo(now())) {
            $reservation->estado = 'VENCIDA';
            $reservation->active_lot_id = null;
            $reservation->save();

            $lot->estado = 'DISPONIBLE';
            $lot->motivo_estado = 'Vencimiento automático de '.$reservation->codigo;
            $lot->version++;
            $lot->save();
        }
    }

    public function run(): void
    {
        LotReservation::where('estado', 'ACTIVA')->where('vence_en', '<=', now())
            ->orderBy('id')->chunkById(100, function ($reservations) {
                foreach ($reservations as $reservation) {
                    DB::transaction(function () use ($reservation) {
                        $lot = LandLot::findOrFail($reservation->land_lot_id);
                        RealEstateProject::lockForUpdate()->findOrFail($lot->real_estate_project_id);
                        $lot = LandLot::lockForUpdate()->findOrFail($lot->id);
                        $this->releaseForLot($lot);
                    }, 3);
                }
            });
    }
}
