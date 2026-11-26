<x-app-layout>
    <section class="catalog" aria-labelledby="kardex-title">
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Movimientos</p>
                <h1 id="kardex-title">Kardex</h1>
                <p>Registra movimientos y consulta quién hizo cada entrada o salida.</p>
            </div>
            <a class="catalog-button" href="#kardex-history">Ir al historial ↓</a>
        </header>

        @if (session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif

        @if ($activeProducts->isEmpty())
            <div class="catalog-panel catalog-empty">
                <h2>Necesitas un producto activo</h2>
                <p>Una vez registrado en el catálogo, podrás mover sus existencias desde aquí.</p>
                <a class="catalog-link" href="{{ route('products.index') }}">Abrir catálogo</a>
            </div>
        @else
            <form id="registro" class="catalog-panel kardex-register" method="POST" action="{{ route('kardex.store') }}">
                @csrf
                @php($formOperation = old('tipo', session('kardex_operation', $operation)) === 'SALIDA' ? 'SALIDA' : 'ENTRADA')
                <div class="catalog-form">
                    <div>
                        <h2>Nuevo movimiento</h2>
                        <p class="catalog-help">Elige una operación y completa los datos del producto.</p>
                    </div>
                    <fieldset class="kardex-operation">
                        <legend>Tipo de movimiento</legend>
                        <div class="kardex-operation-choices">
                            <label class="kardex-mode">
                                <input type="radio" name="tipo" value="ENTRADA" @checked($formOperation === 'ENTRADA')>
                                <span><strong>+ Entrada</strong><small>Agrega existencias</small></span>
                            </label>
                            <label class="kardex-mode">
                                <input type="radio" name="tipo" value="SALIDA" @checked($formOperation === 'SALIDA')>
                                <span><strong>− Salida</strong><small>Retira existencias</small></span>
                            </label>
                        </div>
                    </fieldset>
                    <div class="kardex-product-fields">
                        <div id="kardex-product-search" hidden>
                            <label>Buscar por nombre o SKU
                                <input type="search" autocomplete="off" placeholder="Ej. Cemento o MAT-001" aria-describedby="kardex-search-status">
                            </label>
                            <p id="kardex-search-status" class="catalog-help mt-1" role="status"></p>
                        </div>
                        <label>Producto
                            <select name="product_id" required>
                                <option value="">Selecciona un producto</option>
                                @foreach ($activeProducts as $product)
                                    <option value="{{ $product->id }}" data-step="{{ $product->unit?->permite_decimales ? '0.01' : '1' }}" data-stock="{{ $product->stock_actual }}" data-minimum="{{ $product->stock_minimo }}" data-unit="{{ $product->unit?->simbolo ?? 'und.' }}" data-price="{{ $product->precio_unitario }}" @selected(old('product_id', $filters['product_id'] ?? null) == $product->id)>{{ $product->codigo_sku }} · {{ $product->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="catalog-fields">
                        <label>Cantidad
                            <input type="number" name="cantidad" min="0.01" max="2147483647" step="0.01" required value="{{ old('cantidad') }}" aria-describedby="kardex-balance kardex-stock-message">
                        </label>
                        <label>Precio unitario (S/)
                            <input type="number" name="precio_unitario" min="0" max="99999999.99" step="0.01" required value="{{ old('precio_unitario') }}">
                        </label>
                    </div>
                    <label>Motivo o proyecto
                        <input name="motivo_o_proyecto" required maxlength="255" value="{{ old('motivo_o_proyecto') }}" placeholder="{{ $formOperation === 'SALIDA' ? 'Ej. Despacho para obra A' : 'Ej. Compra a proveedor' }}">
                    </label>
                    <div class="catalog-actions">
                        <button class="catalog-button catalog-primary" type="submit" data-kardex-submit>Confirmar {{ $formOperation === 'SALIDA' ? 'salida' : 'entrada' }}</button>
                    </div>
                </div>
                <aside class="kardex-preview" aria-labelledby="kardex-preview-title">
                    <h2 id="kardex-preview-title">Vista previa del saldo</h2>
                    <p class="catalog-help">Se actualiza al seleccionar un producto y escribir una cantidad.</p>
                    <dl id="kardex-balance" class="kardex-balances" aria-live="polite">
                        <div><dt>Disponible</dt><dd data-balance="current">—</dd></div>
                        <div><dt data-balance-label>{{ $formOperation === 'SALIDA' ? 'Salida' : 'Entrada' }}</dt><dd data-balance="movement">—</dd></div>
                        <div class="kardex-total"><dt>Saldo previsto</dt><dd data-balance="result">—</dd></div>
                    </dl>
                    <p id="kardex-stock-message" class="kardex-alert" role="status" hidden></p>
                </aside>
            </form>
        @endif

        <section class="catalog-panel" aria-labelledby="kardex-history">
            <div class="catalog-body catalog-heading">
                <div>
                    <h2 id="kardex-history" class="text-lg font-semibold text-slate-900">Historial de movimientos</h2>
                    <p>{{ $movements->total() }} {{ $movements->total() === 1 ? 'movimiento' : 'movimientos' }} en la consulta</p>
                </div>
                <a class="catalog-button" href="{{ route('kardex.export', $filters) }}">Descargar Excel (.xlsx)</a>
            </div>
            <form class="catalog-filters" method="GET" action="{{ route('kardex.index') }}">
                <input type="hidden" name="operacion" value="{{ $operation }}">
                <label>Producto
                    <select name="product_id">
                        <option value="">Todos los productos</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? null) == $product->id)>{{ $product->codigo_sku }} · {{ $product->nombre }}{{ $product->trashed() ? ' (archivado)' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Tipo
                    <select name="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="ENTRADA" @selected(($filters['tipo'] ?? null) === 'ENTRADA')>Entradas</option>
                        <option value="SALIDA" @selected(($filters['tipo'] ?? null) === 'SALIDA')>Salidas</option>
                    </select>
                </label>
                <label>Desde<input type="date" name="desde" value="{{ $filters['desde'] ?? '' }}"></label>
                <label>Hasta<input type="date" name="hasta" value="{{ $filters['hasta'] ?? '' }}"></label>
                <button class="catalog-button" type="submit">Filtrar</button>
                @if ($filters)
                    <a class="catalog-link" href="{{ route('kardex.index', ['operacion' => $operation]) }}">Limpiar</a>
                @endif
            </form>
            @if ($movements->isEmpty())
                <div class="catalog-empty">
                    <h2>{{ $filters ? 'Sin coincidencias' : 'Todavía no hay movimientos' }}</h2>
                    <p>{{ $filters ? 'Cambia los filtros para consultar otros movimientos.' : 'Las entradas y salidas registradas aparecerán aquí.' }}</p>
                </div>
            @else
                <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
                    <table class="catalog-table" aria-label="Historial de Kardex">
                        <thead><tr>
                            <th scope="col">Fecha y hora</th><th scope="col">Producto</th><th scope="col">Tipo</th>
                            <th scope="col">Cantidad</th><th scope="col">Precio unitario</th><th scope="col">Motivo</th><th scope="col">Responsable</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($movements as $movement)
                                @php($entry = $movement->tipo !== 'SALIDA')
                                <tr>
                                    <td class="whitespace-nowrap">{{ $movement->created_at->format('d/m/Y') }}<small>{{ $movement->created_at->format('H:i:s') }}</small></td>
                                    <td>
                                        @if ($movement->product && !$movement->product->trashed())
                                            <a class="catalog-product" href="{{ route('products.show', $movement->product) }}">{{ $movement->product->nombre }}</a>
                                        @else
                                            {{ $movement->product?->nombre ?? 'Producto sin referencia' }}
                                        @endif
                                        <small>{{ $movement->product?->codigo_sku }}</small>
                                    </td>
                                    <td><span class="catalog-badge {{ $entry ? 'catalog-good' : 'catalog-warning' }}">{{ $entry ? 'Entrada' : 'Salida' }}</span></td>
                                    <td class="whitespace-nowrap tabular-nums">{{ $entry ? '+' : '−' }}{{ $movement->cantidad }} {{ $movement->product?->unit?->simbolo ?? 'und.' }}</td>
                                    <td class="catalog-price">S/ {{ number_format($movement->precio_unitario, 2) }}</td>
                                    <td>{{ $movement->motivo_o_proyecto }}</td>
                                    <td>{{ $movement->user?->name ?? 'Sin responsable registrado' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="catalog-pagination">{{ $movements->links() }}</div>
                @endif
            @endif
        </section>
    </section>

    <script>
        (() => {
            const form = document.getElementById('registro');
            if (!form) return;

            const product = form.elements.product_id;
            const quantity = form.elements.cantidad;
            const price = form.elements.precio_unitario;
            const operation = form.elements.tipo;
            const searchWrapper = document.getElementById('kardex-product-search');
            const search = searchWrapper.querySelector('input');
            const searchStatus = document.getElementById('kardex-search-status');
            const stockMessage = document.getElementById('kardex-stock-message');
            const currentBalance = form.querySelector('[data-balance="current"]');
            const movementBalance = form.querySelector('[data-balance="movement"]');
            const resultBalance = form.querySelector('[data-balance="result"]');
            const movementLabel = form.querySelector('[data-balance-label]');
            const submit = form.querySelector('[data-kardex-submit]');
            const [placeholder, ...options] = product.options;
            const number = new Intl.NumberFormat('es-PE');
            const maxStock = 2147483647;
            const normalized = value => value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
            const searchable = options.map(option => [option, normalized(option.textContent)]);

            function refresh() {
                const outgoing = operation.value === 'SALIDA';
                const selected = product.selectedOptions[0];
                const hasProduct = Boolean(selected?.value);
                const stock = hasProduct ? Number(selected.dataset.stock) : 0;
                const units = quantity.valueAsNumber;
                const validUnits = Number.isFinite(units) && units > 0 && units <= maxStock;
                const after = Math.round((stock + (outgoing ? -units : units)) * 100) / 100;
                const format = value => number.format(value) + ' ' + selected.dataset.unit;
                let error = '';

                if (hasProduct && validUnits && outgoing && after < 0) {
                    error = 'Stock insuficiente. Disponible: ' + format(stock) + '.';
                } else if (hasProduct && validUnits && !outgoing && after > maxStock) {
                    error = 'La entrada supera el máximo de stock admitido.';
                }

                const warning = hasProduct && validUnits && outgoing && !error && after <= Number(selected.dataset.minimum)
                    ? 'El saldo quedará en el mínimo o por debajo. Mínimo: ' + format(Number(selected.dataset.minimum)) + '.'
                    : '';

                movementLabel.textContent = outgoing ? 'Salida' : 'Entrada';
                submit.textContent = outgoing ? 'Confirmar salida' : 'Confirmar entrada';
                form.elements.motivo_o_proyecto.placeholder = outgoing ? 'Ej. Despacho para obra A' : 'Ej. Compra a proveedor';
                quantity.step = selected?.dataset.step ?? '0.01';
                quantity.min = quantity.step;
                currentBalance.textContent = hasProduct ? format(stock) : '—';
                movementBalance.textContent = hasProduct && validUnits ? format(units) : '—';
                resultBalance.textContent = hasProduct && validUnits && !error ? format(after) : '—';
                stockMessage.textContent = error || warning;
                stockMessage.hidden = !(error || warning);
                stockMessage.classList.toggle('kardex-alert-error', Boolean(error));
                quantity.setCustomValidity(error);
            }

            function chooseProduct() {
                price.value = product.selectedOptions[0]?.dataset.price ?? '';
                refresh();
            }

            function filterProducts() {
                const query = normalized(search.value.trim());
                const selected = product.value;
                const matches = searchable.filter(([, text]) => text.includes(query)).map(([option]) => option);
                const matchingOptions = new Set(matches);
                const visible = options.filter(option => option.value === selected || matchingOptions.has(option));
                product.replaceChildren(placeholder, ...visible);
                product.value = selected;
                searchStatus.textContent = query
                    ? matches.length + (matches.length === 1 ? ' coincidencia' : ' coincidencias')
                        + (selected && !matches.some(option => option.value === selected) ? ' · Se conserva tu selección.' : '')
                    : '';
                return matches;
            }

            searchWrapper.hidden = false;
            search.addEventListener('input', filterProducts);
            search.addEventListener('keydown', event => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const matches = filterProducts();
                if (matches.length === 1) {
                    product.value = matches[0].value;
                    chooseProduct();
                    quantity.focus();
                } else {
                    product.focus();
                }
            });
            form.querySelectorAll('[name="tipo"]').forEach(radio => radio.addEventListener('change', refresh));
            product.addEventListener('change', chooseProduct);
            quantity.addEventListener('input', refresh);
            if (price.value === '') price.value = product.selectedOptions[0]?.dataset.price ?? '';
            refresh();
        })();
    </script>
</x-app-layout>
