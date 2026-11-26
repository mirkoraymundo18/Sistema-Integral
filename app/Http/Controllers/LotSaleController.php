<?php
namespace App\Http\Controllers;
use App\Actions\ConfirmLotSale;
use App\Actions\BuildSaleSchedule;
use App\Models\FinancingPlan;
use App\Support\Money;
use App\Actions\ExpireLotReservations;
use App\Actions\RecordLotPayment;
use App\Models\LotReservation;
use App\Models\LotSale;
use App\Models\LotPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class LotSaleController extends Controller {
    public function payments(Request $request) {
        $filters = $request->validate([
            'desde' => 'nullable|required_with:hasta|date_format:Y-m-d|before_or_equal:'.now('America/Lima')->toDateString(),
            'hasta' => 'nullable|required_with:desde|date_format:Y-m-d|after_or_equal:desde|before_or_equal:'.now('America/Lima')->toDateString(),
            'moneda' => 'nullable|in:PEN,USD', 'search' => 'nullable|string|max:120',
        ]);
        $filters['desde'] = $filters['desde'] ?? now('America/Lima')->startOfMonth()->toDateString();
        $filters['hasta'] = $filters['hasta'] ?? now('America/Lima')->toDateString();
        if (\Illuminate\Support\Carbon::parse($filters['desde'])->diffInDays(\Illuminate\Support\Carbon::parse($filters['hasta'])) >= 366) throw \Illuminate\Validation\ValidationException::withMessages(['desde'=>'Selecciona hasta 366 días.']);
        $query = LotPayment::query()->whereDate('lot_payments.fecha_pago','>=',$filters['desde'])->whereDate('lot_payments.fecha_pago','<=',$filters['hasta'])
            ->when($filters['moneda'] ?? null, fn ($q,$currency) => $q->whereHas('sale',fn($sale)=>$sale->where('moneda',$currency)))
            ->when($filters['search'] ?? null, fn ($q,$search) => $q->where(fn($q)=>$q->whereLike('referencia',"%{$search}%")->orWhereHas('sale.client',fn($c)=>$c->where(fn($c)=>$c->where('dni',$search)->orWhereLike('nombres',"%{$search}%")->orWhereLike('apellidos',"%{$search}%")))));
        $totals = (clone $query)->join('lot_sales','lot_sales.id','=','lot_payments.lot_sale_id')->selectRaw('lot_sales.moneda,SUM(lot_payments.monto) AS total')->groupBy('lot_sales.moneda')->toBase()->get();
        $payments = $query->with(['sale','creator'])->orderByDesc('fecha_pago')->orderByDesc('id')->paginate(15)->withQueryString();
        return response()->view('commercial.sales.payments',compact('filters','totals','payments'))->header('Cache-Control','private, no-store');
    }
    public function index(Request $request) {
        $filters = $request->validate(['estado' => 'nullable|in:PENDIENTE,PAGADA', 'dni' => ['nullable', 'regex:/\A[0-9]{8}\z/'], 'search' => 'nullable|string|max:120', 'vencidas' => 'nullable|boolean']);
        $sales = LotSale::query()->when($filters['vencidas'] ?? false, fn ($q) => $q->whereHas('installments', fn ($q) => $q->whereColumn('pagado','<','monto')->where('vence_el','<',now('America/Lima')->toDateString())))->when($filters['estado'] ?? null, fn ($q, $state) => $q->where('estado', $state))
            ->when($filters['dni'] ?? null, fn ($q, $dni) => $q->whereHas('client', fn ($q) => $q->where('dni', $dni)))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('client', fn ($client) => $client->where(fn ($names) => $names->whereLike('nombres', "%{$search}%")->orWhereLike('apellidos', "%{$search}%")->orWhere('dni', $search)));
                    if (preg_match('/\AVEN-0*([1-9][0-9]{0,17})\z/i', $search, $match)) $query->orWhere('id', $match[1]);
                });
            })
            ->latest('id')->paginate(15)->withQueryString();
        return view('commercial.sales.index', compact('sales', 'filters'));
    }
    public function create(LotReservation $reservation, ExpireLotReservations $expire) {
        $expire->run(); $reservation->refresh()->load('sale');
        if ($reservation->sale) return to_route('commercial.sales.show', $reservation->sale);
        $plans = FinancingPlan::where('activo',true)->orderBy('meses')->get();
        return view('commercial.sales.create', compact('reservation','plans'));
    }
    public function store(Request $request, LotReservation $reservation, ConfirmLotSale $confirm, ExpireLotReservations $expire) {
        $data = $request->validate(['observaciones' => 'nullable|string|max:1000', 'confirmacion' => 'accepted']);
        $expire->run();
        $sale = $confirm->confirm($reservation, $request->user()->id, $data['observaciones'] ?? null, $this->terms($request));
        return to_route('commercial.sales.show', $sale)->with('success', 'Venta confirmada. Registra los pagos efectivamente recibidos.');
    }
    public function show(LotSale $sale) {
        $sale->load(['creator', 'reservation']);
        $payments = $sale->payments()->with(['creator','allocations.installment'])->orderByDesc('fecha_pago')->orderByDesc('id')->paginate(15)->withQueryString();
        $installments = $sale->installments()->orderBy('numero')->paginate(24, ['*'], 'cuotas_pagina')->withQueryString();
        $paymentSchedule = $sale->installments()->whereColumn('pagado','<','monto')->orderBy('numero')->get()
            ->map(fn ($row) => [
                'numero' => $row->numero,
                'concepto' => $row->numero === 0 ? 'Inicial' : ($sale->modalidad === 'CONTADO' ? 'Pago único' : 'Cuota '.$row->numero),
                'vence_el' => $row->vence_el->toDateString(),
                'saldo_centimos' => (string) Money::cents($row->saldo),
            ]);
        $suggestedAmount = $paymentSchedule->isNotEmpty() ? Money::format((int)$paymentSchedule->first()['saldo_centimos']) : '';
        $token = (string) Str::uuid();
        $defaultPaymentMode = in_array(request('pago'), ['SIGUIENTE', 'SALDO', 'LIBRE'], true) ? request('pago') : ($paymentSchedule->isEmpty() ? 'LIBRE' : 'SIGUIENTE');
        if ($defaultPaymentMode === 'SIGUIENTE' && $paymentSchedule->isEmpty()) $defaultPaymentMode = 'LIBRE';
        if ($defaultPaymentMode === 'SALDO') $suggestedAmount = $sale->saldo;
        return response()->view('commercial.sales.show', compact('sale', 'payments', 'installments', 'paymentSchedule', 'suggestedAmount', 'token', 'defaultPaymentMode'))->header('Cache-Control', 'private, no-store');
    }
    public function receipt(LotSale $sale, LotPayment $payment) {
        abort_unless((int) $payment->lot_sale_id === (int) $sale->id, 404);
        $payment->load(['creator', 'allocations.installment']);
        return response()->view('commercial.sales.receipt', compact('sale', 'payment'))->header('Cache-Control', 'private, no-store');
    }
    public function payment(Request $request, LotSale $sale, RecordLotPayment $record) {
        $data = $request->validate([
            'modo_pago' => 'nullable|in:LIBRE,SIGUIENTE,ADELANTO,SALDO',
            'cantidad_cuotas' => 'nullable|required_if:modo_pago,ADELANTO|integer|min:1|max:120',
            'saldo_esperado' => ['nullable', Rule::requiredIf(in_array($request->input('modo_pago'), ['SIGUIENTE','ADELANTO','SALDO'],true)), 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99', 'regex:/\A[0-9]{1,12}(?:\.[0-9]{1,2})?\z/'],
            'token' => 'required|uuid', 'monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99', 'regex:/\A[0-9]{1,12}(?:\.[0-9]{1,2})?\z/'],
            'fecha_pago' => 'required|date_format:Y-m-d|after_or_equal:'.$sale->created_at->copy()->timezone('America/Lima')->toDateString().'|before_or_equal:'.now('America/Lima')->toDateString(),
            'metodo' => 'required|in:EFECTIVO,TRANSFERENCIA,DEPOSITO',
            'referencia' => ['nullable', Rule::requiredIf(in_array($request->input('metodo'), ['TRANSFERENCIA', 'DEPOSITO'], true)), 'string', 'max:100'],
            'observaciones' => 'nullable|string|max:500',
        ]);
        $payment = $record->record($sale, $data, $request->user()->id);
        return redirect()->to(route('commercial.sales.show', $sale).'#payment-history')->with('success', 'Pago registrado y saldo actualizado.')->with('payment_receipt_id', $payment->id);
    }
    private function terms(Request $request): array {
        $data = $request->validate([
            'modalidad' => 'nullable|in:CONTADO,CUOTAS',
            'financing_plan_id' => 'exclude_unless:modalidad,CUOTAS|required|integer|exists:financing_plans,id',
            'inicial' => ['exclude_unless:modalidad,CUOTAS','required','numeric','decimal:0,2','min:0','max:999999999999.99','regex:/\A[0-9]{1,12}(?:\.[0-9]{1,2})?\z/'],
            'primera_cuota' => 'exclude_unless:modalidad,CUOTAS|required|date_format:Y-m-d|after:'.now('America/Lima')->toDateString(),
            'fecha_contado' => 'exclude_unless:modalidad,CONTADO|nullable|date_format:Y-m-d',
        ]);
        $data['modalidad'] = $data['modalidad'] ?? 'CONTADO';
        return $data;
    }
    public function configure(LotSale $sale) {
        if ($sale->installments()->exists()) return to_route('commercial.sales.show', $sale);
        $plans = FinancingPlan::where('activo',true)->orderBy('meses')->get();
        return view('commercial.sales.schedule', compact('sale','plans'));
    }
    public function schedule(Request $request, LotSale $sale, BuildSaleSchedule $build) {
        $request->validate(['confirmacion' => 'accepted']);
        $build->build($sale, $this->terms($request));
        return to_route('commercial.sales.show', $sale)->with('success','Cronograma registrado. Los pagos anteriores quedaron aplicados sin volver a cobrarlos.');
    }
}
