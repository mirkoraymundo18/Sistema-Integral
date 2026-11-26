<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\RealEstateProject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLotReservation
{
    public function __construct(private ExpireLotReservations $expire) {}

    public function create(array $data, int $userId): LotReservation
    {
        return DB::transaction(function () use ($data, $userId) {
            $lot = LandLot::findOrFail($data['land_lot_id']);
            $project = RealEstateProject::lockForUpdate()->findOrFail($lot->real_estate_project_id);
            $lot = LandLot::lockForUpdate()->findOrFail($lot->id);
            $previous = LotReservation::where('token', $data['token'])->first();
            if ($previous) {
                if ($previous->land_lot_id !== $lot->id || $previous->client_id != $data['client_id']) {
                    throw ValidationException::withMessages(['reserva' => 'Vuelve a abrir el formulario para registrar otra reserva.']);
                }
                return $previous;
            }
            $this->expire->releaseForLot($lot);
            if ($lot->estado !== 'DISPONIBLE' || $lot->sale()->exists() || LotReservation::where('active_lot_id', $lot->id)->exists()) {
                throw ValidationException::withMessages(['lote' => 'Este lote ya no está disponible. Selecciona otro.']);
            }
            $client = Client::lockForUpdate()->findOrFail($data['client_id']);
            $reservation = LotReservation::create([
                'token' => $data['token'], 'land_lot_id' => $lot->id, 'active_lot_id' => $lot->id,
                'client_id' => $client->id, 'created_by' => $userId,
                'estado' => 'ACTIVA',
                'vence_en' => Carbon::createFromFormat('!Y-m-d', $data['fecha_vencimiento'], 'America/Lima')->addDay()->utc(),
                'precio_acordado' => $data['precio_acordado'], 'moneda' => $lot->moneda,
                'condiciones' => $data['condiciones'] ?? null,
                'ficha_lote' => [
                    'proyecto' => $project->nombre, 'ubicacion' => $project->ubicacion,
                    'manzana' => $lot->manzana, 'numero' => $lot->numero, 'area_m2' => $lot->area_m2,
                ],
                'ficha_cliente' => ['dni' => $client->dni, 'nombre' => $client->nombre_completo],
            ]);
            $lot->estado = 'RESERVADO';
            $lot->motivo_estado = 'Creación de '.$reservation->codigo;
            $lot->updated_by = $userId;
            $lot->version++;
            $lot->save();

            return $reservation;
        }, 3);
    }
}
