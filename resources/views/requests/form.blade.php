<details id="nueva-solicitud" class="req-compose" @if(old('product_id')) open @endif>
    <summary>Nueva solicitud de material <span>Selecciona el producto e indica para qué se necesita</span></summary>
    <form id="request-form" class="catalog-form" method="POST" action="{{ route('requests.store') }}">
        @csrf
        <div class="catalog-fields"><label>Buscar en el catálogo<input type="search" data-request-product-search placeholder="Escribe el nombre o código del material" autocomplete="off"></label><p class="catalog-help req-form-tip">Una solicitud corresponde a un material y su unidad de medida.</p></div>
        <div class="catalog-fields"><label>Producto<select name="product_id" required aria-describedby="request-stock"><option value="">Selecciona un material</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-step="{{ $product->unit?->permite_decimales ? '0.01' : '1' }}" data-stock="{{ $product->stock_actual }}" data-unit="{{ $product->unit?->simbolo ?? 'und.' }}" @selected(old('product_id') == $product->id)>{{ $product->codigo_sku }} · {{ $product->nombre }}</option>
            @endforeach
        </select></label><label>Cantidad<input name="cantidad" type="number" min="0.01" max="2147483647" step="0.01" required value="{{ old('cantidad') }}"></label></div>
        <p class="req-stock-hint" id="request-stock" role="status">Selecciona un producto para consultar su stock.</p>
        <label>Motivo o frente de trabajo<textarea name="motivo" required rows="2" maxlength="255" placeholder="Ej. Material para habilitación de vías internas">{{ old('motivo') }}</textarea></label>
        <div class="catalog-actions"><button type="submit" class="catalog-button catalog-primary">Enviar a revisión</button><button type="button" class="catalog-button" data-close-request>Cerrar</button></div>
    </form>
</details>
