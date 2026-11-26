<?php
namespace App\Http\Controllers;
use App\Models\FinancingPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class FinancingPlanController extends Controller {
    public function index(Request $request) {
        $plans = FinancingPlan::query()->when(!$request->user()->can('administrar-almacen'), fn ($q) => $q->where('activo',true))->orderBy('meses')->orderBy('id')->get();
        return view('commercial.plans.index', compact('plans'));
    }
    private function data(Request $request, ?FinancingPlan $plan = null): array {
        $data = $request->validate(['nombre' => ['required','string','max:80',Rule::unique('financing_plans')->ignore($plan?->id)], 'meses' => 'required|integer|min:1|max:120', 'inicial_minima_porcentaje' => 'required|numeric|decimal:0,2|min:0|max:99.99', 'activo' => 'nullable|boolean']);
        $data['activo'] = $request->boolean('activo'); return $data;
    }
    public function store(Request $request) {
        FinancingPlan::create($this->data($request)); return to_route('commercial.plans.index')->with('success','Plan creado. Se aplicará a nuevas ventas.');
    }
    public function update(Request $request, FinancingPlan $plan) {
        $plan->update($this->data($request,$plan)); return to_route('commercial.plans.index')->with('success','Plan actualizado. Los cronogramas anteriores conservan sus condiciones.');
    }
}
