<x-app-layout>
<section class="catalog pay-workspace">
    <header class="pay-page-heading">
        <div><p class="catalog-eyebrow">Gestión comercial / Cobranzas</p><h1>Cobros y deudas</h1><p>Consulta los saldos y acompaña cada compra hasta su último pago.</p></div>
        <div class="catalog-actions"><a class="catalog-button catalog-primary" href="{{ route('commercial.payments.index') }}">Historial de cobros</a><a class="catalog-button" href="{{ route('commercial.reservations.index',['estado'=>'ACTIVA']) }}">Continuar una reserva →</a></div>
    </header>

    <nav class="pay-tabs" aria-label="Consultar cobros">
        <a href="{{ route('commercial.sales.index') }}" @if(empty($filters['estado']) && empty($filters['vencidas'])) aria-current="page" @endif>Todas las compras</a>
        <a href="{{ route('commercial.sales.index',['estado'=>'PENDIENTE']) }}" @if(($filters['estado'] ?? '') === 'PENDIENTE' && empty($filters['vencidas'])) aria-current="page" @endif>Por pagar</a>
        <a href="{{ route('commercial.sales.index',['vencidas'=>1]) }}" @if(!empty($filters['vencidas'])) aria-current="page" @endif>Cuotas vencidas</a>
        <a href="{{ route('commercial.sales.index',['estado'=>'PAGADA']) }}" @if(($filters['estado'] ?? '') === 'PAGADA') aria-current="page" @endif>Canceladas</a>
    </nav>

    <section class="pay-list">
        <form class="pay-filterbar" method="GET" action="{{ route('commercial.sales.index') }}">
            @if(!empty($filters['vencidas']))
                <input type="hidden" name="vencidas" value="1">
            @endif
            @if(!empty($filters['dni']))
                <input type="hidden" name="dni" value="{{ $filters['dni'] }}">
            @endif
            <label>Buscar cliente o compra<input name="search" type="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre, apellido, DNI o código de compra"></label>
            <label>Estado<select name="estado"><option value="">Todos los estados</option><option value="PENDIENTE" @selected(($filters['estado'] ?? '') === 'PENDIENTE')>Con saldo pendiente</option><option value="PAGADA" @selected(($filters['estado'] ?? '') === 'PAGADA')>Deuda cancelada</option></select></label>
            <div class="catalog-actions"><button type="submit" class="catalog-button catalog-primary">Buscar</button><a class="catalog-link" href="{{ route('commercial.sales.index') }}">Limpiar</a></div>
        </form>
        <div class="pay-list-heading"><h2>{{ $sales->total() }} {{ $sales->total() === 1 ? 'compra encontrada' : 'compras encontradas' }}</h2><span>
            @if(!empty($filters['dni']))
                Cliente con DNI {{ $filters['dni'] }} ·
            @endif
            @if(!empty($filters['vencidas']))
                Con cuotas vencidas ·
            @endif
            Página {{ $sales->currentPage() }} de {{ $sales->lastPage() }}</span>
        </div>
        <p class="px-5 pb-3 text-xs text-slate-400 sm:hidden">Desliza para ver saldos y acciones.</p>
        <div class="catalog-table-wrap"><table class="catalog-table"><thead><tr><th>Cliente / Compra</th><th>Inmueble</th><th>Precio y pagos</th><th>Saldo pendiente</th><th>Gestión</th></tr></thead><tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><strong>{{ $sale->ficha_cliente['nombre'] }}</strong><small>DNI {{ $sale->ficha_cliente['dni'] }}</small><small>{{ $sale->codigo }}</small></td>
                    <td>{{ $sale->ficha_lote['proyecto'] }}<small>Mz. {{ $sale->ficha_lote['manzana'] }} · Lote {{ $sale->ficha_lote['numero'] }}</small><small>{{ $sale->modalidad === 'CUOTAS' ? 'Inicial + cuotas' : ($sale->modalidad === 'CONTADO' ? 'Contado' : 'Sin cronograma') }}</small></td>
                    <td>{{ $sale->moneda }} {{ number_format((float)$sale->precio_total,2) }}<small>Pagado: {{ $sale->moneda }} {{ number_format((float)$sale->total_pagado,2) }}</small></td>
                    <td><strong>{{ $sale->moneda }} {{ number_format((float)$sale->saldo,2) }}</strong><small><span class="catalog-badge {{ $sale->estado === 'PAGADA' ? 'catalog-good' : 'catalog-warning' }}">{{ $sale->estado === 'PAGADA' ? 'Cancelada' : 'Por pagar' }}</span></small></td>
                    <td><div class="collection-row-actions">
                        @if($sale->estado === 'PENDIENTE' && auth()->user()->can('administrar-almacen'))
                            <a class="catalog-button catalog-primary" href="{{ route('commercial.sales.show',$sale) }}#payment-section">Gestionar pago →</a>
                        @else
                            <a class="catalog-button" href="{{ route('commercial.sales.show',$sale) }}#payment-history">Ver pagos →</a>
                        @endif
                        <a class="catalog-link text-xs" href="{{ route('commercial.clients.show',$sale->client_id) }}#client-account">Ficha del cliente</a>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="pay-empty"><x-erp-icon name="requests" /><strong>No hay compras con estos filtros</strong><p>Prueba con otro nombre o limpia la búsqueda. Para una compra nueva, continúa desde una reserva activa.</p><a class="catalog-link" href="{{ route('commercial.sales.index') }}">Ver todas las compras</a></div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="catalog-pagination">{{ $sales->links() }}</div>
    </section>
    <p class="pay-footnote">Cada pago conserva su fecha, medio de pago, referencia y constancia en el detalle de la compra.</p>
</section>
</x-app-layout>
