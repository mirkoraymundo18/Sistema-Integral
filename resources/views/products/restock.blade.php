<x-app-layout>
<section class="catalog">
    <header class="kardex-heading">
<div>
<p class="catalog-eyebrow">Almacén central / Abastecimiento</p>
<h1>Lista de reposición</h1>
<p>Productos en el mínimo o por debajo y la cantidad faltante para alcanzarlo.</p>
</div>
        <a class="catalog-button catalog-primary" href="{{ route('restock.index', ['exportar' => 1]) }}">Descargar lista Excel</a>
</header>
    <p class="catalog-help">El faltante se calcula por producto y unidad. Un producto en su mínimo tiene faltante cero.</p>
    <section class="catalog-panel">
<p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
<table class="catalog-table">
    <thead>
<tr>
<th scope="col">Producto</th>
<th scope="col">Familia</th>
<th scope="col">Disponible</th>
<th scope="col">Mínimo</th>
<th scope="col">Faltante al mínimo</th>
<th scope="col">Acción</th>
</tr>
</thead>
    <tbody>@forelse($products as $product)
        <tr>
<td>
<a class="catalog-product" href="{{ route('products.show', $product) }}">{{ $product->nombre }}</a>
<small>{{ $product->codigo_sku }}</small>
</td>
<td>{{ $product->family?->nombre ?? 'Sin clasificar' }}</td>
<td>{{ $product->stock_actual }}</td>
<td>{{ $product->stock_minimo }}</td>
<td>{{ max(0, $product->faltante) }} {{ $product->unit?->simbolo ?? 'und.' }}</td>
<td>
<a class="catalog-link" href="{{ route('kardex.index', ['product_id' => $product->id, 'operacion' => 'ENTRADA']) }}">Registrar entrada</a>
</td>
</tr>
    @empty <tr>
<td colspan="6">Todos los productos están por encima de su mínimo.</td>
</tr>@endforelse</tbody>
    </table>
</div>
<div class="catalog-pagination">{{ $products->links() }}</div>
</section>
</section>
</x-app-layout>
