<?php

namespace App\Http\Controllers;

use App\Actions\ExpireLotReservations;
use App\Models\Client;
use App\Models\LotSale;
use App\Models\LotPayment;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:120'])['search'] ?? null;
        $clients = Client::query()->when($search !== null && $search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('dni', $search)->orWhereLike('nombres', "%{$search}%")->orWhereLike('apellidos', "%{$search}%");
            });
        })->orderBy('apellidos')->orderBy('nombres')->orderBy('id')->paginate(15)->withQueryString();

        return view('commercial.clients.index', compact('clients', 'search'));
    }

    public function create()
    {
        return view('commercial.clients.form', ['client' => new Client]);
    }

    public function store(Request $request)
    {
        $client = new Client($this->validated($request, new Client));
        $client->created_by = $request->user()->id;
        $client->save();

        return to_route('commercial.clients.show', $client)->with('success', 'Cliente registrado.');
    }

    public function show(Client $client, ExpireLotReservations $expire)
    {
        $expire->run();
        $reservations = $client->reservations()->latest('id')->paginate(10, ['*'], 'reservas_pagina')->withQueryString();
        $salesQuery = LotSale::where('client_id', $client->id);
        $accountSummary = [];
        foreach ((clone $salesQuery)->select('id', 'moneda', 'precio_total', 'total_pagado', 'estado')->cursor() as $sale) {
            $currency = $sale->moneda;
            $accountSummary[$currency] ??= ['total' => 0, 'paid' => 0, 'balance' => 0, 'pending' => 0];
            $accountSummary[$currency]['total'] += Money::cents($sale->precio_total);
            $accountSummary[$currency]['paid'] += Money::cents($sale->total_pagado);
            $accountSummary[$currency]['balance'] += Money::cents($sale->saldo);
            $accountSummary[$currency]['pending'] += $sale->estado === 'PENDIENTE' ? 1 : 0;
        }
        $clientSales = $salesQuery->with(['installments' => fn ($query) => $query->whereColumn('pagado', '<', 'monto')->orderBy('numero')])
            ->orderByDesc('estado')->latest('id')->paginate(8, ['*'], 'compras_pagina')->withQueryString();
        $clientPayments = LotPayment::whereHas('sale', fn ($query) => $query->where('client_id', $client->id))
            ->with(['sale', 'creator'])->latest('id')->paginate(10, ['*'], 'pagos_pagina')->withQueryString();
        return response()->view('commercial.clients.show', compact('client', 'reservations', 'clientSales', 'clientPayments', 'accountSummary'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function edit(Client $client)
    {
        return view('commercial.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request, $client));

        return to_route('commercial.clients.show', $client)->with('success', 'Datos del cliente actualizados.');
    }

    private function validated(Request $request, Client $client): array
    {
        return $request->validate([
            'dni' => ['required', 'string', 'regex:/\A[0-9]{8}\z/', Rule::unique('clients')->ignore($client->id)],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:120',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
        ], ['dni.regex' => 'El DNI debe tener exactamente 8 dígitos.']);
    }
}
