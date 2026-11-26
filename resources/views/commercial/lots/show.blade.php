<x-app-layout>
    <section class="catalog" aria-labelledby="lot-title">
        
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Gestión comercial / Ficha del lote</p>
                <h1 id="lot-title">Manzana {{ $lot->manzana }} · Lote {{ $lot->numero }}</h1>
                <p>{{ $lot->project->nombre }} · {{ $lot->project->ubicacion }}</p>
            </div>
            @can('gestionar-comercial')
                <div class="catalog-actions">
                    @if ($lot->sale)
                        <a class="catalog-button catalog-primary" href="{{ route('commercial.sales.show', $lot->sale) }}">Ver venta y pagos</a>
                    @elseif ($lot->activeReservation)
                        <a class="catalog-button catalog-primary" href="{{ route('commercial.reservations.show', $lot->activeReservation) }}">Ver reserva activa</a>
                    @else
                        @can('administrar-almacen')
                            <a class="catalog-button" href="{{ route('commercial.lots.edit', $lot) }}">Editar ficha</a>
                        @endcan
                        @if ($lot->estado === 'DISPONIBLE')
                            <a class="catalog-button catalog-primary" href="{{ route('commercial.reservations.create', ['lote' => $lot->id]) }}">Reservar este lote</a>
                        @endif
                    @endif
                </div>
            @endcan
        </header>
        @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif
        <dl class="catalog-stats lg:grid-cols-3">
            <div class="catalog-stat"><dt>Área del lote</dt><dd>{{ number_format((float) $lot->area_m2, 2) }} m²</dd></div>
            <div class="catalog-stat"><dt>Precio total</dt><dd>@if($lot->precio_total > 0){{ $lot->moneda === 'PEN' ? 'S/' : 'US$' }} {{ number_format((float) $lot->precio_total, 2) }}@else Por definir @endif</dd></div>
            <div class="catalog-stat"><dt>Estado comercial</dt><dd>@include('commercial.lots.status')</dd></div>
        </dl>
        <div class="grid items-start gap-5 lg:grid-cols-2">
            <section class="catalog-panel catalog-form">
                <h2>Datos del lote</h2>
                <dl class="grid gap-4 text-sm">
                    <div><dt class="text-slate-500">Proyecto</dt><dd class="mt-1 font-medium">{{ $lot->project->nombre }}</dd></div>
                    <div><dt class="text-slate-500">Partida registral</dt><dd class="mt-1">{{ $lot->partida_registral ?? 'Pendiente de registrar' }}</dd></div>
                    <div><dt class="text-slate-500">Observaciones</dt><dd class="mt-1 whitespace-pre-wrap">{{ $lot->observaciones ?? 'Sin observaciones registradas.' }}</dd></div>
                </dl>
            </section>
            <section class="catalog-panel catalog-form">
                <h2>Seguimiento de la ficha</h2>
                <dl class="grid gap-4 text-sm">
                    <div><dt class="text-slate-500">Última actualización</dt><dd class="mt-1">{{ $lot->updated_at->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-500">Responsable</dt><dd class="mt-1">{{ $lot->editor?->name ?? 'Sin referencia' }}</dd></div>
                    <div><dt class="text-slate-500">Motivo del último cambio de estado</dt><dd class="mt-1">{{ $lot->motivo_estado ?? 'Registro inicial como disponible.' }}</dd></div>
                </dl>
            </section>
        </div>
    </section>
</x-app-layout>
