<?php

namespace App\Http\Controllers;

use App\Actions\ExpireLotReservations;
use App\Models\LandLot;
use App\Models\RealEstateProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RealEstateProjectController extends Controller
{
    public function index(Request $request, ExpireLotReservations $expire)
    {
        $expire->run();
        $search = $request->validate(['search' => 'nullable|string|max:160'])['search'] ?? null;
        $projects = RealEstateProject::withCount([
            'lots',
            'lots as available_count' => fn ($query) => $query->where('estado', 'DISPONIBLE'),
            'lots as reserved_count' => fn ($query) => $query->where('estado', 'RESERVADO'),
            'lots as sold_count' => fn ($query) => $query->where('estado', 'VENDIDO'),
        ])->when($search !== null && $search !== '', function ($query) use ($search) {
            $text = $search;
            $query->where(function ($query) use ($text) {
                $query->whereLike('nombre', "%{$text}%")->orWhereLike('ubicacion', "%{$text}%");
            });
        })->orderBy('nombre')->orderBy('id')->paginate(12)->withQueryString();

        return view('commercial.projects.index', compact('projects', 'search'));
    }

    public function create()
    {
        return view('commercial.projects.form', ['project' => new RealEstateProject]);
    }

    public function store(Request $request)
    {
        $project = RealEstateProject::create($this->validated($request));

        return to_route('commercial.projects.show', $project)->with('success', 'Proyecto creado. Puedes registrar sus lotes.');
    }

    public function edit(RealEstateProject $project)
    {
        return view('commercial.projects.form', compact('project'));
    }

    public function update(Request $request, RealEstateProject $project)
    {
        $project->update($this->validated($request));

        return to_route('commercial.projects.show', $project)->with('success', 'Proyecto actualizado.');
    }

    public function show(Request $request, RealEstateProject $project, ExpireLotReservations $expire)
    {
        $expire->run();
        $filters = $request->validate([
            'search' => 'nullable|string|max:80',
            'estado' => ['nullable', Rule::in(array_keys(LandLot::STATUSES))],
        ]);
        $totals = $project->lots()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $lots = $project->lots()
            ->when($filters['estado'] ?? null, fn ($query, $state) => $query->where('estado', $state))
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($query) use ($search) {
                    $query->whereLike('manzana', "%{$search}%")->orWhereLike('numero', "%{$search}%");
                });
            })->orderBy('manzana')->orderBy('numero')->orderBy('id')->paginate(20)->withQueryString();

        return view('commercial.projects.show', compact('project', 'lots', 'totals', 'filters'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:160',
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:3000',
        ]);
    }
}
