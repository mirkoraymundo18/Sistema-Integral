<x-app-layout>
<section class="catalog">
    <header class="kardex-heading">
        <div><p class="catalog-eyebrow">Gestión comercial / Contactos</p><h1>Clientes</h1><p>Consulta la ficha del cliente para ver sus compras, deudas, pagos y reservas.</p></div>
        <a class="catalog-button catalog-primary" href="{{ route('commercial.clients.create') }}">+ Nuevo cliente</a>
    </header>
    @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif
    <section class="catalog-panel">
        <form method="GET" class="catalog-filters" action="{{ route('commercial.clients.index') }}">
            <label>Buscar cliente<input type="search" name="search" maxlength="120" value="{{ $search }}" placeholder="DNI, nombres o apellidos"></label>
            <button class="catalog-button" type="submit">Buscar</button>
            <a class="catalog-link" href="{{ route('commercial.clients.index') }}">Ver todos</a>
        </form>
        <p class="px-5 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
        <div class="catalog-table-wrap">
            <table class="catalog-table" aria-label="Clientes registrados">
                <thead><tr><th scope="col">Cliente</th><th scope="col">DNI</th><th scope="col">Teléfono</th><th scope="col">Correo</th><th scope="col">Ficha</th></tr></thead>
                <tbody>@forelse($clients as $client)
                    <tr><td><strong>{{ $client->nombre_completo }}</strong></td><td>{{ $client->dni }}</td><td>{{ $client->telefono ?? 'Pendiente' }}</td><td>{{ $client->email ?? 'Pendiente' }}</td>
                        <td><a class="catalog-link" href="{{ route('commercial.clients.show', $client) }}">Ver cliente</a></td></tr>
                @empty <tr><td colspan="5">Los clientes registrados aparecerán aquí.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <div class="catalog-pagination">{{ $clients->links() }}</div>
    </section>
</section>
</x-app-layout>
