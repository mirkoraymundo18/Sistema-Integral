@php
    $product = $order->product;
    $quantity = rtrim(rtrim(number_format((float)$order->cantidad,2,'.',''),'0'),'.');
    $unit = $product?->unit?->simbolo ?? 'und.';
    $canDispatch = $product && !$product->trashed() && $quantity > 0 && ($product->unit?->permite_decimales || (int)$quantity == $quantity);
    $hasStock = $canDispatch && $product->stock_actual >= $order->cantidad;
    $badge = match($order->estado) { 'Pendiente'=>'catalog-warning', 'Entregado'=>'catalog-good', 'Rechazado'=>'catalog-danger', default=>'bg-blue-50 text-blue-800' };
@endphp
<details class="req-item" id="sol-{{ $order->id }}" @if((int)old('_request') === $order->id) open @endif>
    <summary class="req-row-summary">
        <span class="req-material"><small>SOL-{{ str_pad((string)$order->id,6,'0',STR_PAD_LEFT) }}</small><strong>{{ $product?->nombre ?? 'Material sin referencia' }}</strong><small>{{ $product?->codigo_sku }}</small></span>
        <span class="req-quantity"><strong>{{ $quantity }}</strong> {{ $unit }}</span>
        <span class="req-person"><strong>{{ $order->user?->name ?? 'Usuario sin referencia' }}</strong><small>{{ $order->created_at->copy()->timezone('America/Lima')->format('d/m/Y H:i') }}</small></span>
        <span class="req-state"><span class="catalog-badge {{ $badge }}">{{ $order->estado }}</span>
            @if($order->estado === 'Aprobado')
                <small class="{{ $hasStock ? 'text-emerald-600' : 'text-amber-700' }}">{{ $hasStock ? 'Listo para entregar' : 'Requiere reposición' }}</small>
            @endif
        </span>
        <span class="req-manage">{{ $isAdmin && in_array($order->estado,['Pendiente','Aprobado']) ? 'Gestionar' : 'Ver detalle' }} <span aria-hidden="true">⌄</span></span>
    </summary>
    <div class="req-detail-grid">
        <div class="req-detail-copy"><p class="catalog-eyebrow">Destino del material</p><h3>{{ $order->motivo }}</h3>
            <ol class="req-timeline"><li><strong>Solicitud registrada</strong><span>{{ $order->created_at->copy()->timezone('America/Lima')->format('d/m/Y H:i') }} · {{ $order->user?->name }}</span></li>
                @if($order->decided_at)
                    <li><strong>{{ $order->estado === 'Rechazado' ? 'Solicitud rechazada' : 'Solicitud aprobada' }}</strong><span>{{ $order->decided_at->copy()->timezone('America/Lima')->format('d/m/Y H:i') }} · {{ $order->decisionMaker?->name }}</span><p>{{ $order->decision_note }}</p></li>
                @endif
                @if($order->movement)
                    <li><strong>Material entregado</strong><span>{{ $order->movement->created_at->copy()->timezone('America/Lima')->format('d/m/Y H:i') }} · {{ $order->movement->user?->name }}</span></li>
                @endif
            </ol>
        </div>
        <div class="req-action-card">
            @if($order->estado === 'Entregado' && $order->movement)
                <h3>Entrega completada</h3><p>{{ $quantity }} {{ $unit }} retirados del almacén. La salida está vinculada a esta solicitud.</p><a class="catalog-button" href="{{ route('requests.receipt',$order) }}">Ver comprobante de entrega</a><a class="catalog-link" href="{{ route('kardex.index',['movimiento'=>$order->movement->id]) }}">Consultar salida en Kardex →</a>
            @elseif($isAdmin && $order->estado === 'Pendiente')
                <h3>Revisar solicitud</h3><p>Disponible ahora: {{ $product?->stock_actual ?? '—' }} {{ $unit }}. La aprobación todavía no descuenta stock.</p>
                <form class="catalog-form !p-0" method="POST" action="{{ route('requests.updateStatus',$order) }}">@csrf @method('PATCH')
                    <input type="hidden" name="_request" value="{{ $order->id }}"><label>Comentario de revisión <small>(opcional)</small><textarea name="decision_note" maxlength="255" rows="2" placeholder="Indica una observación o coordina la entrega"></textarea></label>
                    @if(!$canDispatch)
                        <p class="catalog-help">Para aprobar se requiere un producto activo y cantidad compatible con su unidad.</p>
                    @endif
                    <div class="catalog-actions">
                        @if($canDispatch)
                            <button type="submit" name="estado" value="Aprobado" class="catalog-button catalog-primary">Aprobar solicitud</button>
                        @endif
                        <button type="submit" name="estado" value="Rechazado" class="catalog-button">Rechazar</button>
                    </div>
                </form>
            @elseif($isAdmin && $order->estado === 'Aprobado')
                <h3>Preparar entrega</h3><p>Solicitado: {{ $quantity }} {{ $unit }} · Disponible: {{ $product?->stock_actual ?? '—' }} {{ $unit }}</p>
                @if($hasStock)
                    <form method="POST" action="{{ route('requests.dispatch',$order) }}">@csrf<input type="hidden" name="_request" value="{{ $order->id }}"><p>Confirma cuando el material se entregue. Se registrará una sola salida de Kardex.</p><button type="submit" class="catalog-button catalog-primary">Confirmar entrega</button></form>
                @else
                    <p class="req-stock-warning">Revisa la unidad y registra el abastecimiento necesario antes de entregar.</p><a class="catalog-link" href="{{ route('kardex.index') }}">Abrir Kardex →</a>
                @endif
            @elseif($order->estado === 'Rechazado')
                <h3>Solicitud cerrada</h3><p>La decisión y su comentario quedan conservados en el historial.</p><a class="catalog-link" href="#nueva-solicitud" data-open-request>Crear otra solicitud →</a>
            @else
                <h3>{{ $order->estado === 'Aprobado' ? 'Pendiente de retiro' : 'En revisión' }}</h3><p>{{ $order->estado === 'Aprobado' ? 'Coordina con administración la entrega del material.' : 'Administración revisará la solicitud y su disponibilidad.' }}</p>
            @endif
        </div>
    </div>
</details>
