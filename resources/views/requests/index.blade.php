<x-app-layout>
<section class="catalog req-workspace" aria-labelledby="requests-title">
    <header class="pay-page-heading"><div><p class="catalog-eyebrow">Operaciones / Control de materiales</p><h1 id="requests-title">{{ $isAdmin ? 'Requerimientos' : 'Mis requerimientos' }}</h1><p>Solicitud → revisión → entrega → comprobante.</p></div>
        @if($products->isNotEmpty())
            <a class="catalog-button catalog-primary" href="#nueva-solicitud" data-open-request>+ Nueva solicitud</a>
        @endif
    </header>
    @if(session('success'))
        <p class="catalog-notice" role="status">{{ session('success') }}</p>
    @endif

    <nav class="req-status-strip" aria-label="Estados de los requerimientos">
        @foreach(['Pendiente'=>'Por revisar','Aprobado'=>'Por entregar','Entregado'=>'Entregados','Rechazado'=>'Rechazados'] as $state=>$label)
            <a href="{{ route('requests.index',[...$filters,'estado'=>$state]) }}" @if(($filters['estado'] ?? '') === $state) aria-current="page" @endif><span>{{ $label }}</span><strong>{{ $counts[$state] ?? 0 }}</strong></a>
        @endforeach
    </nav>

    @if($products->isNotEmpty())
        @include('requests.form')
    @else
        <p class="catalog-help">Registra un producto activo para empezar a solicitar materiales.</p>
    @endif

    <section class="req-inbox" aria-labelledby="request-list-title">
        <div class="req-inbox-heading"><div><h2 id="request-list-title">{{ $isAdmin ? 'Bandeja de solicitudes' : 'Mi bandeja' }}</h2><p>{{ $requests->total() }} resultados · Pendientes y aprobados primero</p></div><a class="catalog-link" href="{{ route('requests.index') }}">Ver todas</a></div>
        <form method="GET" class="req-filterbar" action="{{ route('requests.index') }}">
            <label>Buscar<input type="search" name="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" placeholder="Material, SKU, solicitante, motivo o SOL-000078"></label>
            <label>Estado<select name="estado"><option value="">Todos</option>
                @foreach(['Pendiente','Aprobado','Entregado','Rechazado'] as $state)
                    <option value="{{ $state }}" @selected(($filters['estado'] ?? '') === $state)>{{ $state }}</option>
                @endforeach
            </select></label>
            <div class="catalog-actions"><button type="submit" class="catalog-button">Filtrar</button><a class="catalog-link" href="{{ route('requests.index') }}">Limpiar</a></div>
            <details class="req-date-filter" @if(!empty($filters['desde'])) open @endif><summary>Filtrar por fecha de solicitud</summary><div class="catalog-fields"><label>Desde<input type="date" name="desde" value="{{ $filters['desde'] ?? '' }}" max="{{ now('America/Lima')->toDateString() }}"></label><label>Hasta<input type="date" name="hasta" value="{{ $filters['hasta'] ?? '' }}" max="{{ now('America/Lima')->toDateString() }}"></label></div></details>
        </form>
        <div class="req-columns" aria-hidden="true"><span>Solicitud / Material</span><span>Cantidad</span><span>Solicitante / Fecha</span><span>Estado</span><span>Acción</span></div>
        <div>
            @forelse($requests as $order)
                @include('requests.item')
            @empty
                <div class="pay-empty"><x-erp-icon name="requests" /><strong>No hay solicitudes con estos filtros</strong><p>Prueba otro criterio o crea una nueva solicitud de materiales.</p></div>
            @endforelse
        </div>
        <div class="catalog-pagination">{{ $requests->links() }}</div>
    </section>
    <p class="pay-footnote">Las fechas se muestran en hora de Perú. Aprobar autoriza la solicitud; confirmar la entrega descuenta stock y genera su salida de Kardex.</p>
</section>
</x-app-layout>
