<x-app-layout>
<section class="catalog reservation-record">
    <header class="kardex-heading reservation-no-print">
        <div><h1>Constancia de reserva</h1><p>Revisa los datos y obtén una copia para el cliente.</p></div>
        <button class="catalog-button catalog-primary" type="button" data-print-reservation><x-erp-icon name="requests" /> Imprimir / Guardar PDF</button>
    </header>
    @if(session('success'))<p class="catalog-notice reservation-no-print" role="status">{{ session('success') }}</p>@endif
    <div class="reservation-document-layout">
        <article class="reservation-paper" aria-labelledby="document-title">
            <div class="reservation-letterhead">
                <div class="reservation-brand"><span class="reservation-brand-mark"><x-erp-icon name="building" /></span><div><strong>Leidy Inmobiliaria</strong><span>Gestión comercial · Proyectos y lotes</span></div></div>
                <div class="reservation-document-code"><span>CONSTANCIA</span><strong>{{ $reservation->codigo }}</strong></div>
            </div>
            <div class="reservation-document-heading">
                <div><p class="reservation-caption">Registro comercial</p><h2 id="document-title">Reserva de lote</h2><p>Emitida el {{ $reservation->created_at->copy()->timezone('America/Lima')->format('d/m/Y') }} · {{ $reservation->created_at->copy()->timezone('America/Lima')->format('H:i') }}, Perú</p></div>
                <div>@include('commercial.reservations.status')</div>
            </div>
            <section class="reservation-document-section"><h3><span>01</span> Datos del cliente</h3>
                <dl class="reservation-data-grid"><div class="reservation-wide"><dt>Nombres y apellidos</dt><dd>{{ $reservation->ficha_cliente['nombre'] }}</dd></div><div><dt>Documento de identidad</dt><dd>DNI {{ $reservation->ficha_cliente['dni'] }}</dd></div></dl>
            </section>
            <section class="reservation-document-section"><h3><span>02</span> Identificación del inmueble</h3>
                <dl class="reservation-data-grid"><div class="reservation-wide"><dt>Proyecto inmobiliario</dt><dd>{{ $reservation->ficha_lote['proyecto'] }}</dd></div><div class="reservation-wide"><dt>Ubicación</dt><dd>{{ $reservation->ficha_lote['ubicacion'] }}</dd></div><div><dt>Manzana / Lote</dt><dd>{{ $reservation->ficha_lote['manzana'] }} / {{ $reservation->ficha_lote['numero'] }}</dd></div><div><dt>Área del terreno</dt><dd>{{ number_format((float) $reservation->ficha_lote['area_m2'], 2) }} m²</dd></div></dl>
            </section>
            <div class="reservation-agreement"><div><span>Precio total acordado</span><strong>{{ $reservation->moneda === 'PEN' ? 'S/' : 'US$' }} {{ number_format((float) $reservation->precio_acordado, 2) }}</strong><small>{{ $reservation->moneda === 'PEN' ? 'Soles peruanos' : 'Dólares estadounidenses' }}</small></div><div><span>Fecha límite de reserva</span><strong>{{ $reservation->vence_en->copy()->timezone('America/Lima')->subSecond()->format('d/m/Y') }}</strong><small>Hasta las 23:59 · Hora de Perú</small></div></div>
            <section class="reservation-document-section"><h3><span>03</span> Condiciones registradas</h3><p class="reservation-conditions">{{ $reservation->condiciones ?? 'Sin condiciones adicionales registradas.' }}</p></section>
            @if($reservation->estado === 'CANCELADA')
                <section class="reservation-cancelled-note"><strong>Reserva cancelada</strong><p>{{ $reservation->motivo_cancelacion }}</p><small>{{ $reservation->cancelled_at?->copy()->timezone('America/Lima')->format('d/m/Y H:i') }} · Hora de Perú</small></section>
            @elseif($reservation->estado === 'VENCIDA')
                <p class="reservation-cancelled-note">El plazo de esta reserva ha vencido. Consulta la disponibilidad actual del lote antes de realizar otra gestión.</p>
            @endif
            <footer class="reservation-document-footer"><div><span>Registrado por</span><strong>{{ $reservation->creator?->name ?? 'Personal autorizado' }}</strong></div><p>Constancia interna de reserva. El precio indicado corresponde al total acordado y no acredita pagos ni propiedad del inmueble.</p><span>Leidy Inmobiliaria · {{ $reservation->codigo }}</span></footer>
        </article>
        <aside class="reservation-side-help reservation-no-print" aria-label="Acciones de la reserva">
            <div class="catalog-panel catalog-form"><p class="catalog-eyebrow">¿Qué deseas hacer?</p><h2>Tu reserva, a mano</h2><p class="catalog-help">El documento conserva los datos registrados al crear la reserva.</p><button type="button" class="catalog-button catalog-primary" data-print-reservation>Obtener mi constancia</button><p class="catalog-help">En la ventana de impresión elige «Guardar como PDF» o tu impresora.</p><a class="catalog-button" href="{{ route('commercial.clients.show', $reservation->client_id) }}">Ver ficha del cliente</a><a class="catalog-button" href="{{ route('commercial.lots.show', $reservation->land_lot_id) }}">Ver ficha del lote</a></div>
            @if($reservation->sale)
                <a class="catalog-button catalog-primary" href="{{ route('commercial.sales.show', $reservation->sale) }}">Ver venta y pagos →</a>
            @elseif($reservation->estado === 'ACTIVA')
                <div class="catalog-panel catalog-form"><h2>Siguiente paso: la compra</h2><p class="catalog-help">Cuando el cliente decida comprar, confirma la venta y registra los pagos recibidos.</p>@can('administrar-almacen')<a class="catalog-button catalog-primary" href="{{ route('commercial.sales.create', $reservation) }}">Continuar con la venta →</a>@else<a class="catalog-button catalog-primary" href="{{ route('commercial.sales.create',$reservation) }}">Simular contado o cuotas →</a><p class="catalog-help">El administrador confirma la venta. Puedes simular las opciones para atender al cliente.</p>@endcan</div>
            @endif
            @if($reservation->estado === 'ACTIVA')
                @can('cancelar-reserva', $reservation)
                <details class="catalog-panel"><summary class="reservation-cancel-summary">Cancelar esta reserva</summary><form class="catalog-form" method="POST" action="{{ route('commercial.reservations.cancel', $reservation) }}" data-cancel-reservation>
                    @csrf
                    <p class="catalog-help">El lote quedará disponible para otra persona. El historial de esta reserva se conservará.</p><label>¿Por qué se cancela?<textarea name="motivo_cancelacion" required maxlength="255" rows="3" placeholder="Describe el motivo de la cancelación">{{ old('motivo_cancelacion') }}</textarea></label><button class="catalog-button catalog-danger" type="submit">Cancelar y liberar lote</button>
                </form></details>
                @else
                    <p class="catalog-panel catalog-form catalog-help">Para cancelar una reserva registrada por otra persona, solicita la gestión al administrador.</p>
                @endcan
            @endif
        </aside>
    </div>
</section>
</x-app-layout>
