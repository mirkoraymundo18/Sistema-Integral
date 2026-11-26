<x-app-layout>
    <section class="catalog" aria-labelledby="settings-title">
        
        <header class="catalog-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Configuración</p>
                <h1 id="settings-title">Familias y unidades</h1>
                <p>Organiza tus materiales y define cómo se mide el stock.</p>
            </div>
            <a class="catalog-button catalog-primary" href="{{ route('products.create') }}">Nuevo producto</a>
        </header>
        @if (session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif

        <div class="catalog-fields">
            <section class="catalog-panel catalog-form">
                <h2>Familias</h2>
                <p class="catalog-help">El prefijo se conserva para mantener la secuencia del SKU.</p>
                <form method="POST" action="{{ route('products.families.store') }}" class="catalog-form-fields">
                    @csrf
                    <label>Nombre de la familia<input name="nombre" required maxlength="100" placeholder="Ej. Materiales de obra"></label>
                    <label>Prefijo del SKU<input name="prefijo" required minlength="2" maxlength="8" pattern="[A-Za-z][A-Za-z0-9]{1,7}" placeholder="Ej. MAT"></label>
                    <button class="catalog-button catalog-primary" type="submit">Crear familia</button>
                </form>
                <ul class="catalog-settings-list">
                    @foreach ($families as $family)
                        <li>
                            <details>
                                <summary>{{ $family->nombre }} <span class="catalog-code">{{ $family->prefijo }}</span></summary>
                                <form method="POST" action="{{ route('products.families.update', $family) }}" class="catalog-form-fields">
                                    @csrf
                                    @method('PATCH')
                                    <label>Nombre de la familia<input name="nombre" value="{{ $family->nombre }}" required maxlength="100"></label>
                                    <button class="catalog-button" type="submit">Guardar nombre</button>
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="catalog-panel catalog-form">
                <h2>Unidades de medida</h2>
                <p class="catalog-help">El símbolo aparece junto al saldo del producto.</p>
                <form method="POST" action="{{ route('products.units.store') }}" class="catalog-form-fields">
                    @csrf
                    <label>Nombre de la unidad<input name="nombre" required maxlength="60" placeholder="Ej. Bolsa"></label>
                    <label>Símbolo<input name="simbolo" required maxlength="12" placeholder="Ej. bolsa"></label>
                    <label>Precisión
                        <select name="permite_decimales" required>
                            <option value="0">Unidades enteras</option>
                            <option value="1">Hasta dos decimales</option>
                        </select>
                    </label>
                    <button class="catalog-button catalog-primary" type="submit">Crear unidad</button>
                </form>
                <ul class="catalog-settings-list">
                    @foreach ($units as $unit)
                        <li>
                            {{ $unit->nombre }} <span class="catalog-code">{{ $unit->simbolo }}</span>
                            @if ($unit->permite_decimales)
                                <small>Hasta dos decimales</small>
                            @else
                                <form method="POST" action="{{ route('products.units.update', $unit) }}" class="mt-2">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="permite_decimales" value="1">
                                    <button class="catalog-button" type="submit">Habilitar decimales</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </section>
</x-app-layout>
