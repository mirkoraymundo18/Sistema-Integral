<?php

namespace App\Http\Controllers;

use App\Actions\ExpireLotReservations;
use App\Actions\CreateLotReservation;
use App\Models\Client;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\RealEstateProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LotReservationController extends Controller
{
    public function index(Request $request, ExpireLotReservations $expire)
    {
        $expire->run();
        $filters = $request->validate([
            'estado' => 'nullable|in:ACTIVA,CANCELADA,VENCIDA,CONVERTIDA',
            'dni' => ['nullable', 'string', 'regex:/\A[0-9]{8}\z/'],
        ]);
        $reservations = LotReservation::with('client')
            ->when($filters['estado'] ?? null, fn ($query, $state) => $query->where('estado', $state))
            ->when($filters['dni'] ?? null, fn ($query, $dni) => $query->whereHas('client', fn ($query) => $query->where('dni', $dni)))
            ->latest('id')->paginate(15)->withQueryString();

        return view('commercial.reservations.index', compact('reservations', 'filters'));
    }

    public function create(Request $request, ExpireLotReservations $expire)
    {
        $expire->run();
        $selection = $request->validate(['lote' => 'nullable|integer|min:1', 'cliente' => 'nullable|integer|min:1']);
        $selectedLot = $selection['lote'] ?? null;

        return view('commercial.reservations.form', [
            'lots' => LandLot::where('estado', 'DISPONIBLE')->with('project')->orderBy('real_estate_project_id')->orderBy('manzana')->orderBy('numero')->get(),
            'clients' => Client::orderBy('apellidos')->orderBy('nombres')->get(),
            'selectedLot' => $selectedLot,
            'selectedClient' => $selection['cliente'] ?? null,
            'token' => Str::uuid()->toString(),
        ]);
    }

    public function store(Request $request, CreateLotReservation $create)
    {
        $data = $request->validate([
            'token' => 'required|uuid',
            'land_lot_id' => 'required|integer|exists:land_lots,id',
            'client_id' => 'required|integer|exists:clients,id',
            'fecha_vencimiento' => 'required|date_format:Y-m-d|after_or_equal:'.now('America/Lima')->toDateString(),
            'precio_acordado' => 'required|numeric|decimal:0,2|min:0.01|max:999999999999.99',
            'condiciones' => 'nullable|string|max:2000',
        ]);
        $reservation = $create->create($data, $request->user()->id);

        return to_route('commercial.reservations.show', $reservation)->with('success', 'Reserva registrada.');
    }

    public function show(LotReservation $reservation, ExpireLotReservations $expire)
    {
        $expire->run();
        $reservation->refresh()->load(['creator', 'sale']);

        return response()->view('commercial.reservations.show', compact('reservation'))->header('Cache-Control', 'private, no-store');
    }

    public function cancel(Request $request, LotReservation $reservation, ExpireLotReservations $expire)
    {
        $data = $request->validate(['motivo_cancelacion' => 'required|string|max:255']);
        DB::transaction(function () use ($request, $reservation, $data, $expire) {
            $lot = LandLot::findOrFail($reservation->land_lot_id);
            RealEstateProject::lockForUpdate()->findOrFail($lot->real_estate_project_id);
            $lot = LandLot::lockForUpdate()->findOrFail($lot->id);
            $expire->releaseForLot($lot);
            $current = LotReservation::lockForUpdate()->findOrFail($reservation->id);
            if ($current->estado !== 'ACTIVA' || $current->active_lot_id !== $lot->id) {
                throw ValidationException::withMessages(['reserva' => 'La reserva ya no está activa. Actualiza su ficha.']);
            }

            $current->estado = 'CANCELADA';
            $current->active_lot_id = null;
            $current->cancelled_by = $request->user()->id;
            $current->cancelled_at = now();
            $current->motivo_cancelacion = $data['motivo_cancelacion'];
            $current->save();
            $lot->estado = 'DISPONIBLE';
            $lot->motivo_estado = 'Cancelación de '.$current->codigo;
            $lot->updated_by = $request->user()->id;
            $lot->version++;
            $lot->save();
        }, 3);

        return to_route('commercial.reservations.show', $reservation)->with('success', 'Reserva cancelada y lote liberado.');
    }
}
