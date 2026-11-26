<section id="client-account" class="catalog-panel collection-section">
    <div class="catalog-body catalog-heading"><div><p class="catalog-eyebrow">01 / Estado de cuenta</p><h2>Compras y deudas</h2><p class="catalog-help">Elige una compra para pagar la siguiente cuota, adelantar o cancelar toda la deuda.</p></div><a class="catalog-link" href="{{ route('commercial.sales.index', ['dni' => $client->dni]) }}">Abrir en Cobros y deudas →</a></div>
    @forelse($accountSummary as $currency => $summary)
        <div class="collection-balance-grid catalog-body" aria-label="Resumen en {{ $currency }}">
            <div><span>Importe de las compras · {{ $currency }}</span><strong>{{ number_format($summary['total']/100, 2) }}</strong></div>
            <div><span>Pagos registrados · {{ $currency }}</span><strong>{{ number_format($summary['paid']/100, 2) }}</strong></div>
            <div class="collection-balance-due"><span>Deuda pendiente · {{ $currency }}</span><strong>{{ number_format($summary['balance']/100, 2) }}</strong><small>{{ $summary['pending'] }} compras por cancelar</small></div>
        </div>
    @empty<div class="catalog-body"><p class="catalog-help">Todavía no hay compras confirmadas. Una reserva por sí sola no genera deuda ni registra dinero recibido.</p><a class="catalog-button" href="#client-reservations">Ver reservas para continuar</a></div>@endforelse
    @if($clientSales->total())
    <div class="catalog-table-wrap"><table class="catalog-table"><caption class="sr-only">Compras y saldos de este cliente</caption><thead><tr><th>Compra / Lote</th><th>Pagado</th><th>Deuda pendiente</th><th>Próximo pendiente</th><th>Acciones</th></tr></thead><tbody>
    @foreach($clientSales as $purchase)
        @php($next = $purchase->installments->first())
        <tr><td><strong>{{ $purchase->codigo }}</strong><small>{{ $purchase->ficha_lote['proyecto'] }} · Mz. {{ $purchase->ficha_lote['manzana'] }} / Lote {{ $purchase->ficha_lote['numero'] }}</small><small>{{ $purchase->modalidad === 'CUOTAS' ? 'Inicial + cuotas' : 'Contado / abonos' }}</small></td>
            <td>{{ $purchase->moneda }} {{ number_format((float)$purchase->total_pagado,2) }}</td>
            <td><strong>{{ $purchase->moneda }} {{ number_format((float)$purchase->saldo,2) }}</strong><small>{{ $purchase->estado === 'PAGADA' ? 'Deuda cancelada' : 'Saldo por pagar' }}</small></td>
            <td>@if($next){{ $next->numero === 0 ? 'Inicial' : ($purchase->modalidad === 'CONTADO' ? 'Pago único' : 'Cuota '.$next->numero) }}<small>{{ $next->vence_el->format('d/m/Y') }} · {{ $purchase->moneda }} {{ number_format((float)$next->saldo,2) }}</small><span class="catalog-badge {{ $next->estado === 'Vencida' ? 'catalog-danger':'catalog-warning' }}">{{ $next->estado }}</span>@else<span class="catalog-help">{{ $purchase->estado === 'PAGADA' ? 'Todo pagado' : 'Sin cronograma' }}</span>@endif</td>
            <td><div class="collection-row-actions">@can('administrar-almacen')@if($purchase->estado === 'PENDIENTE')<a class="catalog-button catalog-primary" href="{{ route('commercial.sales.show',$purchase) }}#payment-section">Pagar o adelantar</a><a class="catalog-link" href="{{ route('commercial.sales.show',[$purchase,'pago'=>'SALDO']) }}#payment-section">Cancelar toda la deuda</a>@endif
@endcan<a class="catalog-link" href="{{ route('commercial.sales.show',$purchase) }}#payment-history">Ver detalle e historial</a></div></td>
        </tr>
    @endforeach</tbody></table></div><div class="catalog-pagination">{{ $clientSales->fragment('client-account')->links() }}</div>
    @endif
    @cannot('administrar-almacen')<p class="catalog-body catalog-help">Puedes consultar deudas y constancias. Administración registra los pagos recibidos.</p>@endcannot
</section>

<section id="client-payments" class="catalog-panel collection-section">
    <div class="catalog-body"><p class="catalog-eyebrow">02 / Movimientos de dinero</p><h2>Historial de pagos del cliente</h2><p class="catalog-help">{{ $clientPayments->total() }} pagos registrados, desde el registro más reciente. Abre una constancia para ver las cuotas cubiertas.</p></div>
    <div class="catalog-table-wrap"><table class="catalog-table"><thead><tr><th>Pago / Compra</th><th>Fecha del pago</th><th>Importe</th><th>Cómo se pagó</th><th>Registró</th><th>Constancia</th></tr></thead><tbody>
    @forelse($clientPayments as $payment)<tr><td><strong>{{ $payment->codigo }}</strong><small>{{ $payment->sale->codigo }}</small></td><td>{{ $payment->fecha_pago->format('d/m/Y') }}</td><td>{{ $payment->sale->moneda }} {{ number_format((float)$payment->monto,2) }}</td><td>{{ ['EFECTIVO'=>'Efectivo','TRANSFERENCIA'=>'Transferencia bancaria','DEPOSITO'=>'Depósito bancario'][$payment->metodo] ?? $payment->metodo }}<small>{{ $payment->referencia ?: 'Sin referencia registrada' }}</small></td><td>{{ $payment->creator?->name ?? 'Usuario no disponible' }}</td><td><a class="catalog-link" href="{{ route('commercial.sales.receipt',[$payment->sale,$payment]) }}">Ver / imprimir</a></td></tr>
    @empty<tr><td colspan="6">Todavía no se ha registrado dinero recibido de este cliente.</td></tr>@endforelse
    </tbody></table></div><div class="catalog-pagination">{{ $clientPayments->fragment('client-payments')->links() }}</div>
</section>
