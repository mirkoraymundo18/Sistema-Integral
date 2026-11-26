<x-app-layout>
    <section class="catalog" aria-labelledby="catalog-title">
        <header class="catalog-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Inventario</p>
                <h1 id="catalog-title">Catálogo de productos</h1>
                <p>Consulta existencias y detecta qué materiales necesitan reposición.</p>
            </div>
            <div class="catalog-actions">
                <a class="catalog-button" href="{{ route('products.export', array_filter($filters)) }}">Exportar consulta (.xlsx)</a>
                @can('administrar-almacen')
                    <a class="catalog-button" href="{{ route('products.settings') }}">Familias y unidades</a>
                    <a class="catalog-button catalog-primary" href="{{ route('products.create') }}">+ Nuevo producto</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif

        <div class="catalog-stats" aria-label="Resumen del inventario">
            <a href="{{ route('products.index') }}" class="catalog-stat">
                <span>Productos activos</span><strong>{{ number_format($catalogCount) }}</strong>
            </a>
            <a href="{{ route('products.index', ['stock' => 'por-reponer']) }}" class="catalog-stat">
                <span>Por reponer</span><strong>{{ number_format($lowStockCount) }}</strong>
            </a>
        </div>

        <div class="catalog-panel">
            <form method="GET" action="{{ route('products.index') }}" class="catalog-filters" aria-label="Filtros del catálogo">
                <label>Buscar material
                    <input name="search" type="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre o código SKU">
                </label>
                <label>Familia
                    <select name="familia">
                        <option value="">Todas las familias</option>
                        @foreach ($families as $family)
                            <option value="{{ $family->id }}" @selected(($filters['familia'] ?? null) == $family->id)>{{ $family->nombre }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Disponibilidad
                    <select name="stock">
                        <option value="">Todos los estados</option>
                        <option value="por-reponer" @selected(isset($filters['stock']))>Por reponer</option>
                    </select>
                </label>
                <button class="catalog-button" type="submit">Filtrar</button>
                @if (array_filter($filters))
                    <a class="catalog-link" href="{{ route('products.index') }}">Limpiar</a>
                @endif
            </form>

            @if ($products->isEmpty())
                <div class="catalog-empty">
                    <h2>{{ array_filter($filters) ? 'Sin coincidencias' : 'Tu catálogo está vacío' }}</h2>
                    <p>{{ array_filter($filters) ? 'Prueba otro nombre, código o filtro.' : 'Los productos aparecerán aquí cuando se registren.' }}</p>
                    @if (array_filter($filters))
                        <a class="catalog-link" href="{{ route('products.index') }}">Ver todos los productos</a>
                    @else
                        @can('administrar-almacen')
                            <a class="catalog-link" href="{{ route('products.create') }}">Crear el primer producto</a>
                        @endcan
                    @endif
                </div>
            @else
                <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
                    <table class="catalog-table" aria-label="Productos registrados">
                        <thead><tr>
                            <th scope="col">Producto</th><th scope="col">Familia</th><th scope="col">Stock / mínimo</th>
                            <th scope="col">Estado</th><th scope="col">Precio unitario</th><th scope="col">Ficha</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td><a class="catalog-product" href="{{ route('products.show', $product) }}">{{ $product->nombre }}</a><small>{{ $product->codigo_sku }}</small></td>
                                    <td>{{ $product->family?->nombre ?? 'Sin clasificar' }}</td>
                                    <td>{{ $product->stock_actual }} {{ $product->unit?->simbolo ?? 'und.' }}<small>Mín. {{ $product->stock_minimo }}</small></td>
                                    <td>@include('products.stock-status')</td>
                                    <td class="catalog-price">S/ {{ number_format($product->precio_unitario, 2) }}</td>
                                    <td><a class="catalog-link" href="{{ route('products.show', $product) }}" aria-label="Ver ficha de {{ $product->nombre }}">Ver ficha</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())
                    <div class="catalog-pagination">{{ $products->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-app-layout>
