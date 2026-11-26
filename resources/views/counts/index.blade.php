<x-app-layout>
<section class="catalog" aria-labelledby="counts-title">
    <header class="kardex-heading">
<div>
<p class="catalog-eyebrow">Almacén central / Control de inventario</p>
<h1 id="counts-title">Conteo físico</h1>
<p>Compara las existencias reales y registra la diferencia con un motivo.</p>
</div>
</header>
    @if(session('success'))<p class="catalog-notice" role="status">{{ session('success') }}</p>@endif
    <form class="catalog-panel catalog-form" id="count-form" action="{{ route('counts.store') }}" method="POST">
        @csrf
        <label>Producto
            <select name="product_id" required>
                <option value="">Selecciona un producto</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-stock="{{ $product->stock_actual }}" data-unit="{{ $product->unit?->simbolo ?? 'und.' }}" data-step="{{ $product->unit?->permite_decimales ? '0.01' : '1' }}">{{ $product->codigo_sku }} · {{ $product->nombre }}</option>
                @endforeach
            </select>
        </label>
        <input type="hidden" name="saldo_esperado" value="">
        <label>Cantidad encontrada<input name="cantidad" required type="number" min="0" step="0.01" max="2147483647">
</label>
        <p id="count-preview" class="catalog-help" role="status">Selecciona un producto para comparar el saldo.</p>
        <label>Motivo del conteo o diferencia<input name="motivo" required maxlength="255" placeholder="Ej. Conteo semanal, material dañado o diferencia de registro">
</label>
        <p class="catalog-help">Al confirmar, se registra el conteo y la diferencia genera una entrada o salida de ajuste en Kardex.</p>
        <div>
<button class="catalog-button catalog-primary" type="submit">Confirmar conteo y ajustar saldo</button>
</div>
    </form>
    <section class="catalog-panel">
<div class="catalog-body">
<h2>Historial de conteos</h2>
</div>
        <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
<table class="catalog-table">
            <thead>
<tr>
<th scope="col">Fecha</th>
<th scope="col">Producto</th>
<th scope="col">Saldo anterior</th>
<th scope="col">Contado</th>
<th scope="col">Motivo y responsable</th>
<th scope="col">Ajuste</th>
</tr>
</thead>
            <tbody>@forelse($counts as $count)
                <tr>
<td>{{ $count->created_at->format('d/m/Y H:i') }}</td>
<td>{{ $count->product->nombre }}</td>
<td>{{ $count->saldo_anterior }}</td>
<td>{{ $count->cantidad_contada }} {{ $count->product->unit?->simbolo ?? 'und.' }}</td>
<td>{{ $count->motivo }}<small>{{ $count->user->name }}</small>
</td>
                    <td>@if($count->kardex_movement_id)<a class="catalog-link" href="{{ route('kardex.index', ['movimiento' => $count->kardex_movement_id]) }}">Ver ajuste</a>@else Coincide @endif</td>
</tr>
            @empty <tr>
<td colspan="6">Los conteos confirmados aparecerán aquí.</td>
</tr>@endforelse</tbody>
        </table>
</div>
<div class="catalog-pagination">{{ $counts->links() }}</div>
    </section>
</section>
<script>
(() => {
    const form = document.getElementById('count-form');
    const product = form.elements.product_id;
    const quantity = form.elements.cantidad;
    const preview = document.getElementById('count-preview');
    function refresh() {
        const selected = product.selectedOptions[0];
        if (!selected?.value) { preview.textContent = 'Selecciona un producto para comparar el saldo.'; return; }
        quantity.step = selected.dataset.step;
        const stock = Number(selected.dataset.stock);
        const counted = quantity.valueAsNumber;
        preview.textContent = 'Sistema: ' + stock + ' ' + selected.dataset.unit
            + (Number.isFinite(counted) ? ' · Diferencia: ' + (Math.round((counted - stock) * 100) / 100) + ' ' + selected.dataset.unit : '');
    }
    product.addEventListener('change', () => {
        form.elements.saldo_esperado.value = product.selectedOptions[0]?.dataset.stock ?? '';
        refresh();
    });
    quantity.addEventListener('input', refresh);
})();
</script>
</x-app-layout>
