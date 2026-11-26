<x-app-layout>
    <section class="catalog" aria-labelledby="projects-title">
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Gestión comercial / Portafolio</p>
                <h1 id="projects-title">Proyectos y lotes</h1>
                <p>Consulta los proyectos inmobiliarios y la disponibilidad registrada de sus lotes.</p>
            </div>
            @can('administrar-almacen')
                <a class="catalog-button catalog-primary" href="{{ route('commercial.projects.create') }}">+ Nuevo proyecto</a>
            @endcan
        </header>
        @if(session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif
        <form class="catalog-panel catalog-filters" method="GET" action="{{ route('commercial.projects.index') }}">
            <label>Buscar proyecto
                <input name="search" type="search" maxlength="160" value="{{ $search }}" placeholder="Nombre o ubicación">
            </label>
            <button class="catalog-button" type="submit">Buscar</button>
            @if($search !== null && $search !== '')<a class="catalog-link" href="{{ route('commercial.projects.index') }}">Limpiar</a>@endif
        </form>

        @if($projects->isEmpty())
            <div class="catalog-panel catalog-empty">
                <h2>{{ $search !== null && $search !== '' ? 'Sin coincidencias' : 'Empieza por tu primer proyecto' }}</h2>
                <p>{{ $search !== null && $search !== '' ? 'Prueba otro nombre o ubicación.' : 'Registra el nombre y la ubicación. Después podrás incorporar sus lotes.' }}</p>
                @can('administrar-almacen')
                    @if($search === null || $search === '')<a class="catalog-button catalog-primary" href="{{ route('commercial.projects.create') }}">Crear proyecto</a>@endif
                @endcan
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($projects as $project)
                    <article class="catalog-panel catalog-form">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><x-erp-icon name="building" /></span>
                            <span class="catalog-badge bg-slate-100 text-slate-700">{{ $project->lots_count }} lotes</span>
                        </div>
                        <div>
                            <h2><a class="hover:text-blue-700" href="{{ route('commercial.projects.show', $project) }}">{{ $project->nombre }}</a></h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $project->ubicacion }}</p>
                        </div>
                        <dl class="grid grid-cols-3 gap-2 border-y border-slate-100 py-4 text-xs text-slate-600">
                            <div><dt>Disponibles</dt><dd class="mt-1 text-lg font-semibold text-emerald-800">{{ $project->available_count }}</dd></div>
                            <div><dt>Reservados</dt><dd class="mt-1 text-lg font-semibold text-amber-800">{{ $project->reserved_count }}</dd></div>
                            <div><dt>Vendidos</dt><dd class="mt-1 text-lg font-semibold text-slate-900">{{ $project->sold_count }}</dd></div>
                        </dl>
                        <a class="catalog-link" href="{{ route('commercial.projects.show', $project) }}">Ver proyecto y lotes →</a>
                    </article>
                @endforeach
            </div>
            <div>{{ $projects->links() }}</div>
        @endif
    </section>
</x-app-layout>
