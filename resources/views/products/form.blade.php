<x-app-layout>
    <section class="catalog catalog-narrow" aria-labelledby="product-form-title">
        
        <header class="catalog-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Inventario</p>
                <h1 id="product-form-title">{{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>
                <p>{{ $product->exists ? 'Actualiza la ficha del material.' : 'El SKU se genera automáticamente con el prefijo de la familia.' }}</p>
            </div>
        </header>

        @if ($families->isEmpty() || $units->isEmpty())
            <div class="catalog-panel catalog-empty">
                <h2>Configura el catálogo</h2>
                <p>Crea al menos una familia y una unidad de medida antes de registrar un producto.</p>
                <a class="catalog-button catalog-primary" href="{{ route('products.settings') }}">Configurar familias y unidades</a>
            </div>
        @else
            <form class="catalog-panel catalog-form" method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
                @csrf
                @if ($product->exists)
                    @method('PATCH')
                @endif
                <label>Nombre del producto
                    <input name="nombre" maxlength="255" required value="{{ old('nombre', $product->nombre) }}">
                </label>
                <label>Descripción <small>(opcional)</small>
                    <textarea name="descripcion" maxlength="2000" rows="3">{{ old('descripcion', $product->descripcion) }}</textarea>
                </label>
                <div class="catalog-fields">
                    @if ($product->product_family_id)
                        <label>Familia
                            <input value="{{ $product->family->nombre }}" readonly>
                            <input type="hidden" name="product_family_id" value="{{ $product->product_family_id }}">
                        </label>
                    @else
                        <label>Familia
                            <select name="product_family_id" required>
                                <option value="">Selecciona una familia</option>
                                @foreach ($families as $family)
                                    <option value="{{ $family->id }}" @selected(old('product_family_id') == $family->id)>{{ $family->nombre }} ({{ $family->prefijo }})</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label>Unidad de medida
                        <select name="measurement_unit_id" required>
                            <option value="">Selecciona una unidad</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('measurement_unit_id', $product->measurement_unit_id) == $unit->id)>{{ $unit->nombre }} ({{ $unit->simbolo }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Stock mínimo
                        <input name="stock_minimo" type="number" min="0" max="2147483647" step="0.01" required value="{{ old('stock_minimo', $product->stock_minimo ?? 5) }}">
                    </label>
                    <label>Precio unitario (S/)
                        <input name="precio_unitario" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('precio_unitario', $product->precio_unitario ?? 0) }}">
                    </label>
                </div>
                <p class="catalog-help">El saldo disponible se actualiza al registrar entradas y salidas en Kardex.</p>
                <div class="catalog-actions">
                    <button type="submit" class="catalog-button catalog-primary">{{ $product->exists ? 'Guardar cambios' : 'Crear producto' }}</button>
                    <a class="catalog-button" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">Cancelar</a>
                </div>
            </form>
        @endif
    </section>
</x-app-layout>
