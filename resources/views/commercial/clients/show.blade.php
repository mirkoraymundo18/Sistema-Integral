<x-app-layout>
<section class="catalog">

    <header class="kardex-heading"><div><p class="catalog-eyebrow">Gestión comercial / Cliente</p><h1>{{ $client->nombre_completo }}</h1><p>DNI {{ $client->dni }}</p></div>
        <div class="catalog-actions"><a class="catalog-button" href="{{ route('commercial.sales.index', ['dni' => $client->dni]) }}">Cobros y deudas</a><a class="catalog-button catalog-primary" href="{{ route('commercial.reservations.create', ['cliente' => $client->id]) }}">+ Reservar un lote</a><a class="catalog-button" href="{{ route('commercial.clients.edit', $client) }}">Editar datos</a></div>
    </header>
    <nav class="collection-navigation" aria-label="Apartados del cliente"><a href="#client-account">01 · Compras y deudas</a><a href="#client-payments">02 · Historial de pagos</a><a href="#client-reservations">03 · Reservas</a><a href="#client-details">04 · Datos personales</a></nav>
    @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif

    @include('commercial.clients.account')
    <section id="client-reservations" class="catalog-panel collection-section">
        <div class="catalog-body"><p class="catalog-eyebrow">03 / Antes de la compra</p><h2>Reservas del cliente</h2><p class="catalog-help">Reserva activa → confirmar compra → elegir contado o cuotas → registrar pagos.</p></div>
        <div class="catalog-table-wrap"><table class="catalog-table">
            <thead><tr><th scope="col">Código</th><th scope="col">Proyecto y lote</th><th scope="col">Estado</th><th scope="col">Ficha</th></tr></thead>
            <tbody>@forelse($reservations as $reservation)
                <tr><td>{{ $reservation->codigo }}</td><td>{{ $reservation->ficha_lote['proyecto'] }}<small>Mz. {{ $reservation->ficha_lote['manzana'] }} · Lote {{ $reservation->ficha_lote['numero'] }}</small></td>
                    <td>@include('commercial.reservations.status')</td><td><a class="catalog-link" href="{{ route('commercial.reservations.show', $reservation) }}">Abrir reserva</a></td></tr>
            @empty <tr><td colspan="4">Este cliente todavía no tiene reservas.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="catalog-pagination">{{ $reservations->fragment('client-reservations')->links() }}</div>
    </section>
    <details id="client-details" class="catalog-panel collection-section"><summary class="reservation-cancel-summary">04 · Datos personales y contacto</summary>
    <dl class="catalog-form">
        <div class="catalog-fields">
            <div><dt class="text-sm text-slate-500">Teléfono</dt><dd>{{ $client->telefono ?? 'Pendiente' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Correo</dt><dd>{{ $client->email ?? 'Pendiente' }}</dd></div>
        </div>
        <div><dt class="text-sm text-slate-500">Dirección</dt><dd>{{ $client->direccion ?? 'Pendiente' }}</dd></div>
        <div><dt class="text-sm text-slate-500">Origen</dt><dd>Registro manual del personal</dd></div>
    </dl>
    </details>
</section>
</x-app-layout>
