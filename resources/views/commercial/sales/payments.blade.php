<x-app-layout>
<section class="catalog pay-workspace">
    <header class="pay-page-heading"><div><p class="catalog-eyebrow">Comercial / Registro de dinero recibido</p><h1>Historial general de cobros</h1><p>Pagos del {{ \Illuminate\Support\Carbon::parse($filters['desde'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($filters['hasta'])->format('d/m/Y') }} · Según fecha del pago.</p></div><a class="catalog-button" href="{{ route('commercial.sales.index') }}">Compras y deudas</a></header>
    <div class="biz-cash-totals">
        @forelse($totals as $total)
            <div><span>Cobrado en {{ $total->moneda }}</span><strong>{{ number_format((float)$total->total,2) }}</strong></div>
        @empty
            <p class="catalog-help">No hay pagos en esta consulta.</p>
        @endforelse
    </div>
    <section class="pay-list">
        <form method="GET" class="biz-report-filter" action="{{ route('commercial.payments.index') }}">
            <label>Desde<input type="date" name="desde" required value="{{ $filters['desde'] }}" max="{{ now('America/Lima')->toDateString() }}"></label>
            <label>Hasta<input type="date" name="hasta" required value="{{ $filters['hasta'] }}" max="{{ now('America/Lima')->toDateString() }}"></label>
            <label>Moneda<select name="moneda"><option value="">Todas</option><option value="PEN" @selected(($filters['moneda'] ?? '') === 'PEN')>Soles</option><option value="USD" @selected(($filters['moneda'] ?? '') === 'USD')>Dólares</option></select></label>
            <label>Cliente o referencia<input type="search" name="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre, DNI u operación"></label>
            <button class="catalog-button catalog-primary" type="submit">Consultar</button>
        </form>
        <div class="pay-list-heading"><h2>{{ $payments->total() }} pagos registrados</h2><a class="catalog-link" href="{{ route('commercial.payments.index') }}">Limpiar</a></div>
        <div class="catalog-table-wrap"><table class="catalog-table"><thead><tr><th>Pago / Fecha</th><th>Cliente / Compra</th><th>Importe</th><th>Medio / Operación</th><th>Registró</th><th>Constancia</th></tr></thead><tbody>
            @forelse($payments as $payment)
                <tr><td><strong>{{ $payment->codigo }}</strong><small>{{ $payment->fecha_pago->format('d/m/Y') }}</small></td><td>{{ $payment->sale->ficha_cliente['nombre'] }}<small>{{ $payment->sale->codigo }} · DNI {{ $payment->sale->ficha_cliente['dni'] }}</small></td><td><strong>{{ $payment->sale->moneda }} {{ number_format((float)$payment->monto,2) }}</strong></td><td>{{ ['EFECTIVO'=>'Efectivo','TRANSFERENCIA'=>'Transferencia','DEPOSITO'=>'Depósito'][$payment->metodo] ?? $payment->metodo }}<small>{{ $payment->referencia ?: 'Sin referencia' }}</small></td><td>{{ $payment->creator?->name }}</td><td><a class="catalog-link" href="{{ route('commercial.sales.receipt',[$payment->sale,$payment]) }}">Ver / imprimir</a></td></tr>
            @empty
                <tr><td colspan="6">No hay pagos con estas fechas y filtros.</td></tr>
            @endforelse
        </tbody></table></div><div class="catalog-pagination">{{ $payments->links() }}</div>
    </section>
</section>
</x-app-layout>
