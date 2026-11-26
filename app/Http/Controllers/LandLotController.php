<?php

namespace App\Http\Controllers;

use App\Actions\ExpireLotReservations;
use App\Models\LandLot;
use App\Models\RealEstateProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LandLotController extends Controller
{
    public function create(RealEstateProject $project)
    {
        return view('commercial.lots.form', ['project' => $project, 'lot' => new LandLot]);
    }

    public function store(Request $request, RealEstateProject $project)
    {
        $lot = DB::transaction(function () use ($request, $project) {
            $project = RealEstateProject::lockForUpdate()->findOrFail($project->id);
            $data = $this->validated($request, $project, new LandLot);
            $lot = new LandLot($data);
            $lot->estado = 'DISPONIBLE';
            $lot->updated_by = $request->user()->id;
            $project->lots()->save($lot);

            return $lot;
        }, 3);

        return to_route('commercial.lots.show', $lot)->with('success', 'Lote registrado como disponible.');
    }

    public function show(LandLot $lot, ExpireLotReservations $expire)
    {
        $expire->run();
        $lot->refresh()->load(['project', 'editor', 'activeReservation', 'sale']);

        return view('commercial.lots.show', compact('lot'));
    }

    public function edit(LandLot $lot, ExpireLotReservations $expire)
    {
        $expire->run();
        $lot->refresh()->load(['project', 'activeReservation', 'sale']);

        return view('commercial.lots.form', ['lot' => $lot, 'project' => $lot->project]);
    }

    public function update(Request $request, LandLot $lot, ExpireLotReservations $expire)
    {
        $expire->run();
        $version = $request->validate(['version' => 'required|integer|min:1|max:2147483647'])['version'];

        DB::transaction(function () use ($request, $lot, $version) {
            $project = RealEstateProject::lockForUpdate()->findOrFail($lot->real_estate_project_id);
            $current = LandLot::lockForUpdate()->findOrFail($lot->id);
            if ($current->sale()->exists()) {
                throw ValidationException::withMessages(['venta' => 'Este lote tiene una venta registrada. Consulta su ficha en Ventas y pagos.']);
            }
            if ($current->activeReservation()->exists()) {
                throw ValidationException::withMessages(['reserva' => 'Este lote tiene una reserva activa. Cancélala desde Reservas antes de modificar la ficha.']);
            }
            $data = $this->validated($request, $project, $current);
            if ((int) $current->version !== (int) $version) {
                throw ValidationException::withMessages(['lote' => 'Otra persona actualizó este lote. Vuelve a abrir su ficha antes de guardar.']);
            }
            if ($current->estado !== $data['estado'] && empty($data['motivo_estado'])) {
                throw ValidationException::withMessages(['motivo_estado' => 'Explica el motivo del cambio de estado.']);
            }
            if ($current->estado === $data['estado']) {
                $data['motivo_estado'] = $current->motivo_estado;
            }
            $current->fill($data);
            $current->updated_by = $request->user()->id;
            $current->version++;
            $current->save();
        }, 3);

        return to_route('commercial.lots.show', $lot)->with('success', 'Ficha del lote actualizada.');
    }

    private function validated(Request $request, RealEstateProject $project, LandLot $lot): array
    {
        foreach (['manzana', 'numero'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $request->merge([$field => Str::upper(trim($value))]);
            }
        }

        return $request->validate([
            'manzana' => 'required|string|max:24',
            'numero' => [
                'required', 'string', 'max:24',
                Rule::unique('land_lots', 'numero')->where('real_estate_project_id', $project->id)
                    ->where('manzana', $request->input('manzana'))->ignore($lot->id),
            ],
            'area_m2' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'precio_total' => 'required|numeric|decimal:0,2|min:0|max:999999999999.99',
            'moneda' => 'required|in:PEN,USD',
            'estado' => $lot->exists ? ['required', Rule::in(array_keys(LandLot::STATUSES))] : ['nullable', Rule::in(['DISPONIBLE'])],
            'partida_registral' => 'nullable|string|max:80',
            'observaciones' => 'nullable|string|max:3000',
            'motivo_estado' => 'nullable|string|max:255',
        ], attributes: ['numero' => 'número de lote', 'area_m2' => 'área', 'precio_total' => 'precio total']);
    }
}
