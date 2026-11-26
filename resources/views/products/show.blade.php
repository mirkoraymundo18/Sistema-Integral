<x-app-layout>
    <section class="catalog" aria-labelledby="product-title">
        
        <header class="catalog-heading">
            <div>
                <p class="catalog-eyebrow">{{ $product->codigo_sku }} · {{ $product->family?->nombre ?? 'Sin clasificar' }}</p>
                <h1 id="product-title">{{ $product->nombre }}</h1>
                @include('products.stock-status')
            </div>
            <div class="catalog-actions">
                @can('administrar-almacen')
                    <a class="catalog-button" href="{{ route('products.edit', $product) }}">Editar ficha</a>
                @endcan
                <a class="catalog-button catalog-primary" href="{{ route('kardex.index', ['product_id' => $product->id]) }}">
                    {{ $movements->isEmpty() && $product->stock_actual == 0 ? 'Registrar entrada inicial' : 'Registrar movimiento' }}
                </a>
            </div>
        </header>

        @if (session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif

        <dl class="catalog-stats">
            <div class="catalog-stat"><dt>Disponible</dt><dd>{{ $product->stock_actual }} {{ $product->unit?->simbolo ?? 'und.' }}</dd></div>
            <div class="catalog-stat"><dt>Stock mínimo</dt><dd>{{ $product->stock_minimo }} {{ $product->unit?->simbolo ?? 'und.' }}</dd></div>
            <div class="catalog-stat"><dt>Faltante al mínimo</dt><dd>{{ max(0, $product->stock_minimo - $product->stock_actual) }} {{ $product->unit?->simbolo ?? 'und.' }}</dd></div>
            <div class="catalog-stat"><dt>Precio unitario</dt><dd>S/ {{ number_format($product->precio_unitario, 2) }}</dd></div>
        </dl>

        @if ($product->descripcion)
            <section class="catalog-panel catalog-body">
                <h2>Descripción</h2>
                <p class="whitespace-pre-wrap">{{ $product->descripcion }}</p>
            </section>
        @endif

        <section class="catalog-panel">
            <div class="catalog-body catalog-heading">
                <h2>Movimientos recientes</h2>
                <a class="catalog-link" href="{{ route('kardex.index', ['product_id' => $product->id]) }}">Abrir Kardex</a>
            </div>
            @if ($movements->isEmpty())
                <div class="catalog-empty"><p>Todavía no hay movimientos para este producto.</p></div>
            @else
                <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
                    <table class="catalog-table" aria-label="Movimientos recientes del producto">
                        <thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Cantidad</th><th scope="col">Motivo</th><th scope="col">Responsable</th></tr></thead>
                        <tbody>
                            @foreach ($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $movement->tipo }}</td>
                                    <td>{{ $movement->cantidad }} {{ $product->unit?->simbolo ?? 'und.' }}</td>
                                    <td>{{ $movement->motivo_o_proyecto }}</td>
                                    <td>{{ $movement->user?->name ?? 'Sin responsable registrado' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @can('administrar-almacen')
            @if ($product->stock_actual == 0)
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('¿Archivar este producto? Su historial quedará disponible en Kardex.')">
                    @csrf
                    @method('DELETE')
                    <button class="catalog-button catalog-danger" type="submit">Archivar producto</button>
                </form>
            @endif
        @endcan
    </section>
</x-app-layout>
