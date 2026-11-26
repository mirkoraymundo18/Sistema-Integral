<?php

namespace App\Http\Controllers;

use App\Models\MeasurementUnit;
use App\Models\ProductFamily;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogSettingsController extends Controller
{
    public function index()
    {
        return view('products.settings', [
            'families' => ProductFamily::orderBy('nombre')->get(),
            'units' => MeasurementUnit::orderBy('nombre')->get(),
        ]);
    }

    public function storeFamily(Request $request)
    {
        $prefix = $request->input('prefijo');

        if (is_string($prefix)) {
            $request->merge(['prefijo' => Str::upper($prefix)]);
        }

        ProductFamily::create($request->validate([
            'nombre' => 'required|string|max:100|unique:product_families',
            'prefijo' => ['required', 'regex:/\A[A-Z][A-Z0-9]{1,7}\z/', 'unique:product_families'],
        ], [
            'prefijo.regex' => 'Usa entre 2 y 8 letras o números. El prefijo debe empezar con una letra.',
        ]));

        return back()->with('success', 'Familia creada.');
    }

    public function updateFamily(Request $request, ProductFamily $family)
    {
        $family->update($request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('product_families')->ignore($family)],
        ]));

        return back()->with('success', 'Nombre de la familia actualizado.');
    }

    public function storeUnit(Request $request)
    {
        MeasurementUnit::create($request->validate([
            'nombre' => 'required|string|max:60|unique:measurement_units',
            'simbolo' => 'required|string|max:12|unique:measurement_units',
            'permite_decimales' => 'required|boolean',
        ], attributes: ['simbolo' => 'símbolo']));

        return back()->with('success', 'Unidad de medida creada.');
    }
    public function updateUnit(Request $request, MeasurementUnit $unit)
    {
        $data = $request->validate(['permite_decimales' => 'required|boolean']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($unit, $data) {
            $unit = MeasurementUnit::lockForUpdate()->findOrFail($unit->id);
            if (! $data['permite_decimales'] && $unit->permite_decimales) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'unidad' => 'Conserva los decimales en una unidad ya habilitada. Crea otra unidad para cantidades enteras.',
                ]);
            }
            $unit->update($data);
        }, 3);

        return back()->with('success', 'Precisión de la unidad actualizada.');
    }

}
