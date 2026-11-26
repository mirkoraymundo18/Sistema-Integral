<x-app-layout>
    <section class="catalog catalog-narrow">
        
        <header class="kardex-heading">
            <div><p class="catalog-eyebrow">Gestión comercial / {{ $project->nombre }}</p><h1>{{ $lot->exists ? 'Editar lote' : 'Registrar lote' }}</h1><p>Completa la identificación y las condiciones comerciales del lote.</p></div>
        </header>
        @if ($lot->exists && $lot->sale)
            <div class="catalog-panel catalog-form"><h2>Este lote tiene una venta registrada</h2><a class="catalog-button" href="{{ route('commercial.sales.show', $lot->sale) }}">Ver venta y pagos</a></div>
        @elseif ($lot->exists && $lot->activeReservation)
            <p class="catalog-notice">Este lote tiene una reserva activa. Gestiona la reserva antes de modificar la ficha.</p>
            <a class="catalog-button" href="{{ route('commercial.reservations.show', $lot->activeReservation) }}">Abrir reserva</a>
        @else
        <form class="catalog-panel catalog-form" method="POST" action="{{ $lot->exists ? route('commercial.lots.update', $lot) : route('commercial.lots.store', $project) }}">
            @csrf
            @if($lot->exists)
                @method('PATCH')
                <input type="hidden" name="version" value="{{ old('version', $lot->version) }}">
            @endif
            <div class="catalog-fields">
                <label>Manzana<input name="manzana" required maxlength="24" value="{{ old('manzana', $lot->manzana) }}" placeholder="Ej. A"></label>
                <label>Número de lote<input name="numero" required maxlength="24" value="{{ old('numero', $lot->numero) }}" placeholder="Ej. 01"></label>
                <label>Área (m²)<input name="area_m2" type="number" min="0.01" step="0.01" max="99999999.99" required value="{{ old('area_m2', $lot->area_m2) }}"></label>
                <label>Moneda
                    <select name="moneda" required>
                        <option value="PEN" @selected(old('moneda', $lot->moneda ?? 'PEN') === 'PEN')>Soles (S/)</option>
                        <option value="USD" @selected(old('moneda', $lot->moneda) === 'USD')>Dólares (US$)</option>
                    </select>
                </label>
            </div>
            <label>Precio total del lote
                <input name="precio_total" type="number" min="0" step="0.01" max="999999999999.99" required value="{{ old('precio_total', $lot->precio_total ?? 0) }}">
            </label>
            <p class="catalog-help">Puedes dejar el precio en cero mientras está por definir.</p>
            <label>Partida registral <small>(opcional)</small>
                <input name="partida_registral" maxlength="80" value="{{ old('partida_registral', $lot->partida_registral) }}">
            </label>
            @if($lot->exists)
                <label>Estado comercial
                    <select name="estado" required>
                        @foreach(\App\Models\LandLot::STATUSES as $state => $label)
                            <option value="{{ $state }}" @selected(old('estado', $lot->estado) === $state)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Motivo del cambio de estado
                    <input name="motivo_estado" maxlength="255" value="{{ old('motivo_estado') }}" placeholder="Obligatorio cuando cambies el estado">
                </label>
                <p class="catalog-help">El estado comercial se registra manualmente por administración.</p>
            @else
                <p class="catalog-help">El lote se registra inicialmente como disponible.</p>
            @endif
            <label>Observaciones <small>(opcional)</small>
                <textarea name="observaciones" maxlength="3000" rows="3">{{ old('observaciones', $lot->observaciones) }}</textarea>
            </label>
            <div class="catalog-actions">
                <button class="catalog-button catalog-primary" type="submit">{{ $lot->exists ? 'Guardar cambios' : 'Registrar lote' }}</button>
                <a class="catalog-button" href="{{ route('commercial.projects.show', $project) }}">Cancelar</a>
            </div>
        </form>
        @endif
    </section>
</x-app-layout>
