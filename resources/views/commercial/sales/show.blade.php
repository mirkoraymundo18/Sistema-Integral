<x-app-layout>
<section class="catalog pay-workspace reservation-record" data-payment-workspace data-payment-has-errors="{{ $errors->any() ? 'true' : 'false' }}" data-payment-default-panel="{{ $sale->estado === 'PAGADA' ? '#payment-history' : '#payment-section' }}">
    @php
        $priceCents = \App\Support\Money::cents($sale->precio_total);
        $paidPercent = $priceCents > 0 ? min(100, round(\App\Support\Money::cents($sale->total_pagado) * 100 / $priceCents, 1)) : 0;
        $canCollect = $sale->estado === 'PENDIENTE' && auth()->user()->can('administrar-almacen');
    @endphp
    <header class="pay-page-heading">
        <div><p class="catalog-eyebrow">Cobros y deudas / {{ $sale->codigo }}</p><h1>Pagos de la compra</h1><p>{{ $sale->ficha_cliente['nombre'] }} <span>· DNI {{ $sale->ficha_cliente['dni'] }}</span></p></div>
        <div class="catalog-actions reservation-no-print"><a class="catalog-button" href="{{ route('commercial.clients.show',$sale->client_id) }}#client-account">Ficha del cliente</a><button type="button" class="catalog-button" data-print-reservation>Imprimir resumen</button></div>
    </header>

    @if(session('success'))
        <div class="pay-success reservation-no-print" role="status"><div><strong>{{ session('success') }}</strong><p>El historial conserva el detalle del cobro.</p></div>
            @if(session('payment_receipt_id'))
                <a class="catalog-button" href="{{ route('commercial.sales.receipt',[$sale, session('payment_receipt_id')]) }}">Ver constancia →</a>
            @endif
        </div>
    @endif

    <div class="pay-overview">
        <section class="pay-balance-card" aria-label="Estado de la deuda">
            <div class="pay-balance-heading"><span>Saldo pendiente · {{ $sale->moneda }}</span><span class="catalog-badge {{ $sale->estado === 'PAGADA' ? 'catalog-good' : 'catalog-warning' }}">{{ $sale->estado === 'PAGADA' ? 'Deuda cancelada' : 'Por pagar' }}</span></div>
            <strong class="pay-balance-amount">{{ $sale->moneda === 'PEN' ? 'S/' : 'US$' }} {{ number_format((float)$sale->saldo,2) }}</strong>
            <div class="pay-progress-label"><span>Avance de pago</span><strong>{{ number_format($paidPercent,1) }}%</strong></div>
            <div class="pay-progress" role="progressbar" aria-label="Porcentaje del precio pagado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $paidPercent }}"><span style="width: {{ $paidPercent }}%"></span></div>
            <dl class="pay-totals"><div><dt>Precio de la compra</dt><dd>{{ $sale->moneda }} {{ number_format((float)$sale->precio_total,2) }}</dd></div><div><dt>Pagos registrados</dt><dd>{{ $sale->moneda }} {{ number_format((float)$sale->total_pagado,2) }}</dd></div></dl>
        </section>
        <aside class="pay-property-card" aria-label="Compra seleccionada">
            <p class="catalog-eyebrow">Inmueble de esta compra</p><h2>{{ $sale->ficha_lote['proyecto'] }}</h2><p>{{ $sale->ficha_lote['ubicacion'] }}</p>
            <dl class="pay-property-data"><div><dt>Manzana / Lote</dt><dd>{{ $sale->ficha_lote['manzana'] }} / {{ $sale->ficha_lote['numero'] }}</dd></div><div><dt>Área</dt><dd>{{ $sale->ficha_lote['area_m2'] }} m²</dd></div><div><dt>Modalidad</dt><dd>{{ $sale->modalidad === 'CUOTAS' ? 'Inicial + cuotas' : ($sale->modalidad === 'CONTADO' ? 'Contado' : 'Abonos') }}</dd></div><div><dt>Plan</dt><dd>{{ $sale->condiciones_financiamiento['plan'] ?? 'Sin cronograma' }}</dd></div></dl>
            <a class="catalog-link reservation-no-print" href="{{ route('commercial.reservations.show',$sale->reservation) }}">Consultar reserva original →</a>
        </aside>
    </div>

    <nav class="pay-tabs reservation-no-print" aria-label="Gestión de pagos" data-payment-tabs>
        @if($canCollect)
            <a id="collect-tab" href="#payment-section" data-payment-tab><x-erp-icon name="requests" /> Registrar pago</a>
        @endif
        <a id="schedule-tab" href="#sale-schedule" data-payment-tab><x-erp-icon name="counts" /> Cronograma</a>
        <a id="history-tab" href="#payment-history" data-payment-tab><x-erp-icon name="kardex" /> Historial <span class="pay-tab-count">{{ $payments->total() }}</span></a>
    </nav>

    @if($canCollect)
        @include('commercial.sales.payment-form')
    @endif
    @include('commercial.sales.installments')
    @include('commercial.sales.history')

    <details class="pay-record-details reservation-no-print"><summary>Información de la compra</summary><p>{{ $sale->observaciones ?: 'Sin observaciones adicionales.' }}</p><p>Registrada por {{ $sale->creator?->name }} el {{ $sale->created_at->copy()->timezone('America/Lima')->format('d/m/Y H:i') }} · Perú.</p></details>
    @cannot('administrar-almacen')
        <p class="pay-footnote reservation-no-print">Administración registra los pagos recibidos. Puedes consultar el cronograma y las constancias.</p>
    @endcannot
    <p class="pay-footnote">Control interno de cobros. No sustituye un comprobante tributario ni acredita transferencia de propiedad.</p>
</section>
</x-app-layout>
