<x-app-layout>
<section class="catalog">
    <header class="kardex-heading"><div><h1>Reservar un lote</h1><p>Completa tres pasos y revisa el resumen antes de confirmar.</p></div></header>
    @if($lots->isEmpty() || $clients->isEmpty())
        <div class="catalog-panel catalog-empty"><h2>Preparemos tu primera reserva</h2><p>Necesitas un cliente registrado y un lote disponible para continuar.</p><div class="catalog-actions"><a class="catalog-button catalog-primary" href="{{ route('commercial.clients.create') }}">Registrar cliente</a><a class="catalog-button" href="{{ route('commercial.projects.index') }}">Ver proyectos y lotes</a></div></div>
    @else
        <form id="reservation-form" method="POST" action="{{ route('commercial.reservations.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ old('token', $token) }}">
            <div class="reservation-form-layout">
                <div class="reservation-steps">
                    <fieldset class="catalog-panel catalog-form"><legend class="sr-only">Paso 1: cliente</legend><div class="reservation-step-title"><span>1</span><div><h2>¿Para quién es la reserva?</h2><p>Encuentra al cliente por su DNI o nombre.</p></div></div>
                        <label class="reservation-js-only" hidden>Buscar cliente<input type="search" data-filter-select="client_id" placeholder="Escribe un DNI o nombre" autocomplete="off" aria-controls="reservation-client"></label>
                        <label>Cliente<select id="reservation-client" name="client_id" required><option value="">Selecciona un cliente</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $selectedClient) == $client->id)>{{ $client->dni }} · {{ $client->nombre_completo }}</option>@endforeach</select></label>
                        <p class="catalog-help" data-filter-feedback="client_id" role="status"></p><a class="catalog-link" href="{{ route('commercial.clients.create') }}">+ El cliente aún no está registrado</a>
                    </fieldset>
                    <fieldset class="catalog-panel catalog-form"><legend class="sr-only">Paso 2: lote</legend><div class="reservation-step-title"><span>2</span><div><h2>Elige el lote disponible</h2><p>Selecciona el proyecto, la manzana y el número.</p></div></div>
                        <label class="reservation-js-only" hidden>Buscar proyecto o lote<input type="search" data-filter-select="land_lot_id" placeholder="Ejemplo: Los Jardines o Mz. A" aria-controls="reservation-lot"></label>
                        <label>Lote<select id="reservation-lot" name="land_lot_id" required><option value="">Selecciona un lote</option>@foreach($lots as $lot)<option value="{{ $lot->id }}" data-price="{{ $lot->precio_total }}" data-currency="{{ $lot->moneda }}" data-area="{{ $lot->area_m2 }}" @selected(old('land_lot_id', $selectedLot) == $lot->id)>{{ $lot->project->nombre }} · Mz. {{ $lot->manzana }} · Lote {{ $lot->numero }}</option>@endforeach</select></label>
                        <p class="catalog-help" data-filter-feedback="land_lot_id" role="status"></p><p class="catalog-help">Solo se muestran lotes disponibles. Se volverá a comprobar la disponibilidad al guardar.</p>
                    </fieldset>
                    <fieldset class="catalog-panel catalog-form"><legend class="sr-only">Paso 3: condiciones</legend><div class="reservation-step-title"><span>3</span><div><h2>Define el acuerdo</h2><p>Confirma el precio total y el plazo de la reserva.</p></div></div>
                        <label>Precio total acordado<input name="precio_acordado" type="number" min="0.01" step="0.01" max="999999999999.99" required value="{{ old('precio_acordado') }}"></label><p class="catalog-help" id="reservation-currency">La moneda corresponde al lote. Este importe no registra un pago.</p>
                        <label>Vigente hasta<input type="date" name="fecha_vencimiento" min="{{ now('America/Lima')->toDateString() }}" required value="{{ old('fecha_vencimiento') }}"></label>
                        <div class="catalog-actions reservation-js-only" hidden aria-label="Elegir duración de reserva"><button class="catalog-button" type="button" data-reservation-days="3">3 días</button><button class="catalog-button" type="button" data-reservation-days="7">7 días</button><button class="catalog-button" type="button" data-reservation-days="15">15 días</button></div><p class="catalog-help">Hasta las 23:59 del día elegido, hora de Perú.</p>
                        <label>Condiciones <small>(opcional)</small><textarea name="condiciones" maxlength="2000" rows="4" placeholder="Escribe los acuerdos que deban aparecer en la constancia">{{ old('condiciones') }}</textarea></label>
                    </fieldset>
                </div>
                <aside class="reservation-summary catalog-panel catalog-form" aria-label="Resumen de la reserva">
                    <p class="catalog-eyebrow">Antes de confirmar</p><h2>Resumen de tu reserva</h2><dl class="reservation-summary-data"><div><dt>Cliente</dt><dd data-summary="client">Selecciona un cliente</dd></div><div><dt>Proyecto y lote</dt><dd data-summary="lot">Selecciona un lote</dd></div><div><dt>Área</dt><dd data-summary="area">Por seleccionar</dd></div><div><dt>Precio total acordado</dt><dd data-summary="price">Por definir</dd></div><div><dt>Vigente hasta</dt><dd data-summary="date">Elige una fecha</dd></div></dl>
                    <p class="reservation-progress" role="status" id="reservation-progress">Completa los datos del formulario.</p><button class="catalog-button catalog-primary" type="submit" data-reservation-submit>Confirmar reserva</button><a class="catalog-button" href="{{ route('commercial.reservations.index') }}">Volver sin guardar</a><p class="catalog-help">Al confirmar, el lote quedará reservado y podrás obtener la constancia.</p>
                </aside>
            </div>
        </form>
        <dialog id="reservation-review" class="reservation-dialog" aria-labelledby="reservation-review-title"><div class="catalog-form"><p class="catalog-eyebrow">Último paso</p><h2 id="reservation-review-title" tabindex="-1">¿Confirmamos la reserva?</h2><p class="catalog-help">Revisa el cliente, el lote y la fecha antes de guardarla.</p><dl class="reservation-summary-data"><div><dt>Cliente</dt><dd data-review="client"></dd></div><div><dt>Lote</dt><dd data-review="lot"></dd></div><div><dt>Precio total</dt><dd data-review="price"></dd></div><div><dt>Vigente hasta</dt><dd data-review="date"></dd></div></dl><div class="catalog-actions"><button type="button" class="catalog-button" data-review-back>Seguir editando</button><button type="button" class="catalog-button catalog-primary" data-review-confirm>Sí, guardar reserva</button></div></div></dialog>
    @endif
</section>
</x-app-layout>
