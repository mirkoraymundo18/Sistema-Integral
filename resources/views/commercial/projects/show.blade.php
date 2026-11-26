<x-app-layout>
    <section class="catalog" aria-labelledby="project-title">
        
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Gestión comercial / Proyecto</p>
                <h1 id="project-title">{{ $project->nombre }}</h1>
                <p>{{ $project->ubicacion }}</p>
            </div>
            @can('administrar-almacen')
                <div class="catalog-actions">
                    <a class="catalog-button" href="{{ route('commercial.projects.edit', $project) }}">Editar proyecto</a>
                    <a class="catalog-button catalog-primary" href="{{ route('commercial.lots.create', $project) }}">+ Registrar lote</a>
                </div>
            @endcan
        </header>
        @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif
        <nav class="catalog-stats lg:grid-cols-3" aria-label="Disponibilidad de lotes">
            @foreach(\App\Models\LandLot::STATUSES as $state => $label)
                <a class="catalog-stat {{ ($filters['estado'] ?? null) === $state ? 'ring-2 ring-blue-600' : '' }}" href="{{ route('commercial.projects.show', [$project, 'estado' => $state]) }}">
                    <span>{{ $label }}</span><strong>{{ $totals[$state] ?? 0 }}</strong>
                </a>
            @endforeach
        </nav>
        @if($project->descripcion)
            <details class="catalog-panel">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-800">Acerca del proyecto</summary>
                <p class="whitespace-pre-wrap px-5 pb-5 text-sm text-slate-600">{{ $project->descripcion }}</p>
            </details>
        @endif
        <section class="catalog-panel">
            <div class="catalog-body"><h2>Lotes del proyecto</h2><p class="catalog-help">{{ $lots->total() }} en la consulta</p></div>
            <form class="catalog-filters" method="GET" action="{{ route('commercial.projects.show', $project) }}">
                <label>Buscar manzana o lote
                    <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="80" placeholder="Ej. A o 01">
                </label>
                <label>Estado comercial
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        @foreach(\App\Models\LandLot::STATUSES as $state => $label)
                            <option value="{{ $state }}" @selected(($filters['estado'] ?? null) === $state)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="catalog-button">Filtrar</button>
                @if(array_filter($filters))<a class="catalog-link" href="{{ route('commercial.projects.show', $project) }}">Limpiar</a>@endif
            </form>
            @if($lots->isEmpty())
                <div class="catalog-empty">
                    <h2>{{ array_filter($filters) ? 'Sin coincidencias' : 'Este proyecto todavía no tiene lotes' }}</h2>
                    <p>{{ array_filter($filters) ? 'Cambia los filtros para ver otros lotes.' : 'Registra cada lote con su manzana, número, área y precio.' }}</p>
                </div>
            @else
                <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
                <div class="catalog-table-wrap">
                    <table class="catalog-table" aria-label="Lotes del proyecto">
                        <thead><tr><th scope="col">Manzana / lote</th><th scope="col">Área</th><th scope="col">Precio total</th><th scope="col">Estado</th><th scope="col">Ficha</th></tr></thead>
                        <tbody>
                            @foreach($lots as $lot)
                                <tr>
                                    <td><strong>Mz. {{ $lot->manzana }} · Lote {{ $lot->numero }}</strong></td>
                                    <td class="catalog-price">{{ number_format((float) $lot->area_m2, 2) }} m²</td>
                                    <td class="catalog-price">@if($lot->precio_total > 0){{ $lot->moneda === 'PEN' ? 'S/' : 'US$' }} {{ number_format((float) $lot->precio_total, 2) }}@else Por definir @endif</td>
                                    <td>@include('commercial.lots.status')</td>
                                    <td><a class="catalog-link" href="{{ route('commercial.lots.show', $lot) }}">Ver ficha</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="catalog-pagination">{{ $lots->links() }}</div>
            @endif
        </section>
    </section>
</x-app-layout>
