<section id="payment-section" class="pay-panel reservation-no-print" data-payment-panel aria-labelledby="collect-tab" tabindex="0">
    <div class="pay-section-heading"><div><h2>Registrar un pago</h2><p>Completa los datos y revisa el importe antes de confirmar.</p></div><span class="pay-safe-label">{{ $sale->moneda }} · {{ $sale->codigo }}</span></div>
    <form method="POST" action="{{ route('commercial.sales.payment',$sale) }}" data-sale-payment data-sale-id="{{ $sale->id }}" data-balance-cents="{{ \App\Support\Money::cents($sale->saldo) }}" data-currency="{{ $sale->moneda }}" class="pay-form-layout">
        @csrf
        <input type="hidden" name="token" value="{{ old('token',$token) }}">
        <input type="hidden" name="saldo_esperado" value="{{ $sale->saldo }}">
        <script type="application/json" data-payment-schedule>@json($paymentSchedule)</script>

        <div class="pay-fields-card catalog-form">
            <fieldset class="pay-fieldset"><legend><span>1</span> Elige qué vas a pagar</legend><p class="pay-fieldset-help">El importe se calcula con los pendientes de esta compra.</p>
                <div class="catalog-fields">
                    <label>Tipo de pago<select name="modo_pago" required>
                        @if($paymentSchedule->isNotEmpty())
                            <option value="SIGUIENTE" @selected(old('modo_pago',$defaultPaymentMode) === 'SIGUIENTE')>Pagar {{ $paymentSchedule->first()['concepto'] }} pendiente</option>
                        @endif
                        @if($sale->modalidad === 'CUOTAS' && $paymentSchedule->where('numero','>',0)->count())
                            <option value="ADELANTO" @selected(old('modo_pago',$defaultPaymentMode) === 'ADELANTO')>Adelantar varias cuotas</option>
                        @endif
                        <option value="SALDO" @selected(old('modo_pago',$defaultPaymentMode) === 'SALDO')>Cancelar toda la deuda</option>
                        <option value="LIBRE" @selected(old('modo_pago',$defaultPaymentMode) === 'LIBRE')>Otro importe / abono parcial</option>
                    </select></label>
                    @if($sale->modalidad === 'CUOTAS' && $paymentSchedule->where('numero','>',0)->count())
                        <label data-payment-count>Cuotas a cubrir<select name="cantidad_cuotas">
                            @foreach(range(1,$paymentSchedule->where('numero','>',0)->count()) as $count)
                                <option value="{{ $count }}" @selected((int)old('cantidad_cuotas',1) === $count)>{{ $count }} {{ $count === 1 ? 'cuota' : 'cuotas' }}</option>
                            @endforeach
                        </select><small>La inicial pendiente se cubre primero.</small></label>
                    @endif
                </div>
            </fieldset>

            <fieldset class="pay-fieldset"><legend><span>2</span> Importe y fecha</legend>
                <div class="catalog-fields"><label>Importe recibido ({{ $sale->moneda }})<input class="pay-amount-input" name="monto" type="number" step="0.01" min="0.01" max="{{ $sale->saldo }}" required value="{{ old('monto',$suggestedAmount) }}" placeholder="0.00"><small data-payment-amount-help>Importe sugerido según el saldo pendiente.</small></label><label>Fecha del pago<input type="date" name="fecha_pago" required min="{{ $sale->created_at->copy()->timezone('America/Lima')->toDateString() }}" max="{{ now('America/Lima')->toDateString() }}" value="{{ old('fecha_pago',now('America/Lima')->toDateString()) }}"><small>Fecha actual de Perú; ajustable para pagos anteriores.</small></label></div>
            </fieldset>

            <fieldset class="pay-fieldset"><legend><span>3</span> ¿Cómo se recibió el dinero?</legend>
                <div class="catalog-fields"><label>Medio de pago<select name="metodo" required>
                    @foreach(['EFECTIVO'=>'Efectivo','TRANSFERENCIA'=>'Transferencia bancaria','DEPOSITO'=>'Depósito bancario'] as $key=>$text)
                        <option value="{{ $key }}" @selected(old('metodo') === $key)>{{ $text }}</option>
                    @endforeach
                </select></label><label>Operación / referencia<input name="referencia" maxlength="100" value="{{ old('referencia') }}" placeholder="Número de operación bancaria"><small>Obligatorio para transferencia o depósito.</small></label></div>
                <label>Observación <small>(opcional)</small><textarea name="observaciones" maxlength="500" rows="2" placeholder="Agrega una aclaración sobre este pago">{{ old('observaciones') }}</textarea></label>
            </fieldset>
        </div>

        <aside class="pay-review-card" aria-label="Resumen del pago a registrar">
            <p class="catalog-eyebrow">Resumen del pago</p><h3>Importe a registrar</h3><strong class="pay-review-amount" data-payment-total>{{ $sale->moneda }} {{ number_format((float)$suggestedAmount,2) }}</strong>
            <p class="pay-review-description" role="status" data-payment-summary>Se cubren primero los pendientes más antiguos.</p>
            <div class="pay-review-balance" data-payment-balance></div>
            <details class="pay-allocation-details"><summary>Ver distribución por cuotas</summary><p class="catalog-help sm:hidden">Desliza para ver los importes.</p><div class="catalog-table-wrap payment-preview-table"><table class="catalog-table"><thead><tr><th>Concepto</th><th>Vence</th><th>Aplicar</th><th>Saldo</th></tr></thead><tbody data-payment-rows></tbody></table></div></details>
            <button class="catalog-button catalog-primary pay-submit" type="submit" data-payment-submit>Revisar y registrar pago</button>
            <p class="pay-review-note">Podrás revisar los datos antes de confirmar. Registra únicamente dinero recibido.</p>
        </aside>
    </form>

    <dialog class="reservation-dialog pay-confirm-dialog" data-payment-review aria-labelledby="payment-review-title"><div class="catalog-form"><p class="catalog-eyebrow">Última revisión</p><h2 id="payment-review-title" tabindex="-1">Confirmar pago recibido</h2><p class="pay-confirm-client">{{ $sale->ficha_cliente['nombre'] }} · {{ $sale->codigo }}</p><p class="pay-confirm-amount" data-payment-review-summary></p><p class="catalog-help" data-payment-review-date></p><ul class="payment-review-list" data-payment-review-rows></ul><p class="pay-review-balance" data-payment-review-balance></p><div class="catalog-actions"><button class="catalog-button" type="button" data-payment-edit>Seguir revisando</button><button class="catalog-button catalog-primary" type="button" data-payment-confirm>Confirmar pago recibido</button></div></div></dialog>
</section>
