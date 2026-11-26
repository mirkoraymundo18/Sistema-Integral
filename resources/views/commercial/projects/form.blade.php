<x-app-layout>
    <section class="catalog catalog-narrow">
        
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Gestión comercial / Proyectos</p>
                <h1>{{ $project->exists ? 'Editar proyecto' : 'Nuevo proyecto' }}</h1>
                <p>Identifica el proyecto y su ubicación para organizar sus lotes.</p>
            </div>
        </header>
        <form class="catalog-panel catalog-form" method="POST" action="{{ $project->exists ? route('commercial.projects.update', $project) : route('commercial.projects.store') }}">
            @csrf
            @if($project->exists) @method('PATCH') @endif
            <label>Nombre del proyecto
                <input name="nombre" maxlength="160" required value="{{ old('nombre', $project->nombre) }}" placeholder="Nombre del proyecto inmobiliario">
            </label>
            <label>Ubicación
                <input name="ubicacion" maxlength="255" required value="{{ old('ubicacion', $project->ubicacion) }}" placeholder="Dirección, distrito y provincia">
            </label>
            <label>Descripción <small>(opcional)</small>
                <textarea name="descripcion" rows="4" maxlength="3000">{{ old('descripcion', $project->descripcion) }}</textarea>
            </label>
            <div class="catalog-actions">
                <button class="catalog-button catalog-primary" type="submit">{{ $project->exists ? 'Guardar cambios' : 'Crear proyecto' }}</button>
                <a class="catalog-button" href="{{ route('commercial.projects.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
</x-app-layout>
