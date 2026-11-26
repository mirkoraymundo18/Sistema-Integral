<x-app-layout>
<section class="catalog">
    <header class="kardex-heading"><div><p class="catalog-eyebrow">Gestión comercial / Seguimiento</p><h1>Reservas de lotes</h1><p>Vincula cada lote con su cliente y consulta sus plazos de reserva.</p></div>
        <a class="catalog-button catalog-primary" href="{{ route('commercial.reservations.create') }}">+ Nueva reserva</a>
    </header>
    @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif
    <nav class="catalog-actions" aria-label="Consultar por estado">
        <a class="catalog-button {{ empty($filters['estado']) ? 'catalog-primary' : '' }}" href="{{ route('commercial.reservations.index') }}" @if(empty($filters['estado'])) aria-current="page" @endif>Todas</a>
        @foreach(['ACTIVA' => 'Activas', 'VENCIDA' => 'Vencidas', 'CONVERTIDA' => 'Con venta', 'CANCELADA' => 'Canceladas'] as $state => $text)
            <a class="catalog-button {{ ($filters['estado'] ?? '') === $state ? 'catalog-primary' : '' }}" href="{{ route('commercial.reservations.index', ['estado' => $state]) }}" @if(($filters['estado'] ?? '') === $state) aria-current="page" @endif>{{ $text }}</a>
        @endforeach
    </nav>
    <section class="catalog-panel">
        <form class="catalog-filters" method="GET" action="{{ route('commercial.reservations.index') }}">
            <label>DNI del cliente<input name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" value="{{ $filters['dni'] ?? '' }}" placeholder="DNI completo"></label>
            <label>Estado<select name="estado">
                <option value="">Todos los estados</option>
                @foreach(['ACTIVA' => 'Activas', 'CANCELADA' => 'Canceladas', 'VENCIDA' => 'Vencidas', 'CONVERTIDA' => 'Con venta'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['estado'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            <button class="catalog-button" type="submit">Filtrar</button>
            <a class="catalog-link" href="{{ route('commercial.reservations.index') }}">Limpiar</a>
        </form>
        <p class="px-5 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
        <div class="catalog-table-wrap"><table class="catalog-table" aria-label="Reservas registradas">
            <thead><tr><th scope="col">Reserva</th><th scope="col">Cliente</th><th scope="col">Lote</th><th scope="col">Vigente hasta</th><th scope="col">Estado</th><th scope="col">Ficha</th></tr></thead>
            <tbody>@forelse($reservations as $reservation)
                <tr><td>{{ $reservation->codigo }}</td><td>{{ $reservation->ficha_cliente['nombre'] }}<small>{{ $reservation->ficha_cliente['dni'] }}</small></td>
                    <td>{{ $reservation->ficha_lote['proyecto'] }}<small>Mz. {{ $reservation->ficha_lote['manzana'] }} · Lote {{ $reservation->ficha_lote['numero'] }}</small></td>
                    <td>{{ $reservation->vence_en->copy()->timezone('America/Lima')->subSecond()->format('d/m/Y') }}</td>
                    <td>@include('commercial.reservations.status')</td><td><a class="catalog-link" href="{{ route('commercial.reservations.show', $reservation) }}">Ver constancia</a></td></tr>
            @empty <tr><td colspan="6">Las reservas que registres aparecerán aquí.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="catalog-pagination">{{ $reservations->links() }}</div>
    </section>
</section>
</x-app-layout>
