@if ($product->stock_actual <= 0)
    <span class="catalog-badge catalog-danger">Agotado</span>
@elseif ($product->stock_actual <= $product->stock_minimo)
    <span class="catalog-badge catalog-warning">Por reponer</span>
@else
    <span class="catalog-badge catalog-good">Disponible</span>
@endif
