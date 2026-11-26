<x-app-layout>
<section class="catalog erp-dashboard biz-dashboard" aria-labelledby="dashboard-title">
    @php
        $rangeFrom = $months[0]['from'];
        $rangeTo = $months[count($months)-1]['to'];
        $totalLots = $business['lotStates']->sum();
    @endphp
    <header class="dash-hero"><div><p class="dash-kicker">Leidy Inmobiliaria / Dirección comercial</p><h1 id="dashboard-title">Resumen de la inmobiliaria</h1><p>Ventas, cobros, cartera, lotes y operaciones del almacén.</p></div><div class="dash-hero-actions"><a class="dash-hero-primary" href="{{ route('commercial.reservations.create') }}">+ Nueva reserva</a><a class="dash-hero-secondary" href="{{ route('commercial.payments.index',['desde'=>$rangeFrom,'hasta'=>$rangeTo]) }}">Consultar cobros</a></div></header>

    <section class="biz-filter" aria-label="Periodo de consulta"><form method="GET" action="{{ route('dashboard') }}" x-data="{ mode: '{{ $custom ? 'personalizado' : 'reciente' }}' }">
        <div class="biz-range-grid"><label>Agrupar por<select name="agrupacion"><option value="semana" @selected($group === 'semana')>Semanas</option><option value="mes" @selected($group === 'mes')>Meses</option></select></label><label>Consultar<select name="rango" x-model="mode"><option value="reciente" @selected(!$custom)>Últimos periodos</option><option value="personalizado" @selected($custom)>Elegir fechas</option></select></label><label x-show="mode === 'reciente'">Cantidad de periodos<select name="periodos">
            @foreach([1,3,4,6,8,12] as $number)
                <option value="{{ $number }}" @selected($period === $number)>{{ $number }} {{ $number === 1 ? 'periodo' : 'periodos' }}</option>
            @endforeach
        </select></label><label x-show="mode === 'personalizado'">Desde<input type="date" name="desde" value="{{ $filters['desde'] ?? '' }}" max="{{ now('America/Lima')->toDateString() }}" :disabled="mode !== 'personalizado'" :required="mode === 'personalizado'"></label><label x-show="mode === 'personalizado'">Hasta<input type="date" name="hasta" value="{{ $filters['hasta'] ?? '' }}" max="{{ now('America/Lima')->toDateString() }}" :disabled="mode !== 'personalizado'" :required="mode === 'personalizado'"></label><button type="submit" class="catalog-button catalog-primary">Aplicar periodo</button></div>
        <div class="biz-range-footer"><nav aria-label="Periodos rápidos"><a href="{{ route('dashboard',['agrupacion'=>'semana','periodos'=>3]) }}" @if(!$custom && $group === 'semana' && $period === 3) aria-current="page" @endif>Últimas 3 semanas</a><a href="{{ route('dashboard',['agrupacion'=>'mes','periodos'=>3]) }}" @if(!$custom && $group === 'mes' && $period === 3) aria-current="page" @endif>Últimos 3 meses</a><a href="{{ route('dashboard',['agrupacion'=>'mes','periodos'=>6]) }}">6 meses</a></nav><p>{{ $periodLabel }} · Hora de Perú</p></div>
    </form></section>

    <div class="biz-kpis" aria-label="Indicadores de la inmobiliaria">
        <a class="biz-kpi" href="{{ route('commercial.sales.index') }}"><span class="biz-kpi-label">Ventas confirmadas <small>Periodo</small></span><strong>{{ $business['totals']['sales'] }}</strong><div class="biz-kpi-extra">
            @forelse($business['saleValues'] as $total)
                <span>{{ $total->moneda }} {{ number_format((float)$total->total,2) }} acordados</span>
            @empty
                <span>Sin ventas registradas en estas fechas</span>
            @endforelse
        </div></a>
        <a class="biz-kpi biz-kpi-cash" href="{{ route('commercial.payments.index',['desde'=>$rangeFrom,'hasta'=>$rangeTo]) }}"><span class="biz-kpi-label">Dinero cobrado <small>Periodo</small></span><div class="biz-kpi-money">
            @forelse($business['cashTotals'] as $currency=>$amount)
                <strong>{{ $currency }} {{ number_format($amount,2) }}</strong>
            @empty
                <strong>0.00</strong>
            @endforelse
        </div><div class="biz-kpi-extra"><span>Dinero recibido según fecha del pago</span></div></a>
        <a class="biz-kpi" href="{{ route('commercial.sales.index',['estado'=>'PENDIENTE']) }}"><span class="biz-kpi-label">Saldo por cobrar <small>Hoy</small></span><div class="biz-kpi-money">
            @forelse($commercial['balances'] as $total)
                <strong>{{ $total->moneda }} {{ number_format((float)$total->total,2) }}</strong>
            @empty
                <strong>0.00</strong>
            @endforelse
        </div><div class="biz-kpi-extra"><span>Deuda pendiente de todas las compras</span></div></a>
        <a class="biz-kpi" href="{{ route('commercial.projects.index') }}"><span class="biz-kpi-label">Lotes disponibles <small>Hoy</small></span><strong>{{ $business['lotStates']['DISPONIBLE'] ?? 0 }} <em>/ {{ $totalLots }}</em></strong><div class="biz-kpi-extra"><span>{{ $business['lotStates']['RESERVADO'] ?? 0 }} reservados · {{ $business['lotStates']['VENDIDO'] ?? 0 }} vendidos</span></div></a>
    </div>
    <section class="biz-pulse" aria-label="Seguimiento del periodo"><div><span>Clientes nuevos</span><strong>{{ $business['totals']['clients'] }}</strong><small>{{ $commercial['clients'] }} registrados en total</small></div><div><span>Reservas creadas</span><strong>{{ $business['totals']['reservations'] }}</strong><small>{{ $commercial['reservations'] }} activas actualmente</small></div><div><span>Reservas del periodo convertidas</span><strong>{{ $business['totals']['reservations'] ? number_format($business['cohortConverted']/$business['totals']['reservations']*100,1).'%' : '—' }}</strong><small>{{ $business['cohortConverted'] }} con compra confirmada al día de hoy</small></div><a href="{{ route('commercial.sales.index',['vencidas'=>1]) }}"><span>Cuotas vencidas hoy</span><strong>{{ $overdueCount }}</strong><small>Consultar compras con atraso →</small></a></section>

    <div class="biz-main-grid">
        <section class="dash-panel"><div class="dash-panel-heading"><div><p class="dash-section-label">Dinero recibido / {{ $group === 'semana' ? 'Semanas' : 'Meses' }}</p><h2>Cobros del periodo</h2><p>Pulsa una barra para consultar los pagos y sus constancias.</p></div><a class="catalog-link" href="{{ route('commercial.payments.index',['desde'=>$rangeFrom,'hasta'=>$rangeTo]) }}">Ver detalle →</a></div>
            @foreach($business['currencyKeys'] ?: ['PEN'] as $currency)
                <div class="biz-cash-chart"><p class="biz-chart-currency">{{ $currency === 'PEN' ? 'Soles · PEN' : 'Dólares · USD' }}</p>
                    @foreach($business['series'] as $bucket)
                        <a class="biz-cash-row" href="{{ route('commercial.payments.index',['desde'=>$bucket['from'],'hasta'=>$bucket['to'],'moneda'=>$currency]) }}" aria-label="{{ $bucket['label'] }}: {{ $currency }} {{ number_format($bucket['payments'][$currency] ?? 0,2) }} cobrados. Ver pagos"><span>{{ $bucket['label'] }}</span><i aria-hidden="true"><b style="width: {{ ($bucket['payments'][$currency] ?? 0)/($business['cashMax'][$currency] ?? 1)*100 }}%"></b></i><strong>{{ number_format($bucket['payments'][$currency] ?? 0,2) }}</strong></a>
                    @endforeach
                </div>
            @endforeach
            <p class="biz-panel-note">Las ventas acordadas y los cobros recibidos se muestran por separado. Las monedas no se suman entre sí.</p>
        </section>
        <section class="dash-panel"><div class="dash-panel-heading"><div><p class="dash-section-label">Disponibilidad actual</p><h2>Proyectos y lotes</h2><p>Situación de los lotes al día de hoy.</p></div></div><div class="biz-lot-legend"><span>{{ $business['lotStates']['DISPONIBLE'] ?? 0 }} disponibles</span><span>{{ $business['lotStates']['RESERVADO'] ?? 0 }} reservados</span><span>{{ $business['lotStates']['VENDIDO'] ?? 0 }} vendidos</span></div>
            @forelse($business['projects'] as $project)
                <a class="biz-project-row" href="{{ route('commercial.projects.show',$project) }}"><div><strong>{{ $project->nombre }}</strong><small>{{ $project->lots_count }} lotes · {{ $project->available_count }} disponibles</small></div><span><b>{{ $project->sold_count }}</b> vendidos<small>{{ $project->reserved_count }} reservados</small></span></a>
            @empty
                <p class="dash-empty">Registra proyectos y lotes para consultar su disponibilidad.</p>
            @endforelse
            <a class="dash-footer-link" href="{{ route('commercial.projects.index') }}">Abrir todos los proyectos →</a>
        </section>
    </div>

    <section class="dash-panel dash-table-panel"><div class="catalog-body"><p class="dash-section-label">Actividad completa / {{ $group === 'semana' ? 'Semanas' : 'Meses' }}</p><h2>Evolución de la operación</h2><p class="catalog-help">Cada fila usa el mismo rango para clientes, reservas, ventas y materiales.</p></div><div class="catalog-table-wrap biz-timeline-table"><table class="catalog-table"><thead><tr><th>Periodo</th><th>Clientes nuevos</th><th>Reservas creadas</th><th>Ventas confirmadas</th><th>Solicitudes creadas</th><th>Entregas realizadas</th></tr></thead><tbody>
        @foreach($business['series'] as $bucket)
            <tr><td><strong>{{ $bucket['label'] }}</strong><small>{{ \Illuminate\Support\Carbon::parse($bucket['from'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($bucket['to'])->format('d/m/Y') }}</small></td><td>{{ $bucket['clients'] }}</td><td>{{ $bucket['reservations'] }}</td><td>{{ $bucket['sales'] }}</td><td><a class="catalog-link" href="{{ route('requests.index',['desde'=>$bucket['from'],'hasta'=>$bucket['to']]) }}">{{ $bucket['requests'] }}</a></td><td>{{ $bucket['deliveries'] }}</td></tr>
        @endforeach
    </tbody></table></div><p class="biz-panel-note catalog-body">Las entregas se cuentan por la fecha de su salida de almacén; pueden corresponder a solicitudes de fechas anteriores.</p></section>

    <div class="dash-lower-grid">
        <section class="dash-panel"><div class="dash-panel-heading"><div><p class="dash-section-label">Cartera / Situación actual</p><h2>Pagos que requieren atención</h2><p>Vencidos hoy y próximos vencimientos de los siguientes 7 días.</p></div></div>
            <a class="dash-task {{ $overdueCount ? 'dash-task-alert' : '' }}" href="{{ route('commercial.sales.index',['vencidas'=>1]) }}"><span>Cuotas vencidas<small>Saldo pendiente fuera de plazo</small></span><strong>{{ $overdueCount }}</strong></a>
            @foreach($overdueAmounts as $total)
                <p class="biz-overdue">{{ $total->moneda }} {{ number_format((float)$total->total,2) }} vencidos</p>
            @endforeach
            @forelse($upcoming as $installment)
                <a class="dash-list-item" href="{{ route('commercial.sales.show',$installment->sale) }}#sale-schedule"><span class="dash-date"><strong>{{ $installment->vence_el->format('d') }}</strong><small>{{ $installment->vence_el->copy()->locale('es')->isoFormat('MMM') }}</small></span><span class="dash-list-detail"><strong>{{ $installment->sale->ficha_cliente['nombre'] }}</strong><small>{{ $installment->sale->codigo }} · {{ $installment->numero === 0 ? 'Inicial' : ($installment->sale->modalidad === 'CONTADO' ? 'Pago único' : 'Cuota '.$installment->numero) }}</small></span><span class="dash-list-value">{{ $installment->sale->moneda }} {{ number_format((float)$installment->saldo,2) }}</span></a>
            @empty
                <p class="dash-empty">No hay cuotas pendientes con vencimiento en los próximos 7 días.</p>
            @endforelse
        </section>
        <section class="dash-panel"><div class="dash-panel-heading"><div><p class="dash-section-label">Almacén / Materiales</p><h2>Requerimientos y abastecimiento</h2><p>{{ $business['totals']['requests'] }} solicitudes creadas · {{ $business['totals']['deliveries'] }} entregas realizadas en el periodo.</p></div></div>
            <a class="dash-task" href="{{ route('requests.index',['estado'=>'Pendiente']) }}"><span>{{ $isAdmin ? 'Por revisar ahora' : 'Mis solicitudes por revisar' }}<small>Solicitudes todavía sin decisión</small></span><strong>{{ $requests['Pendiente'] ?? 0 }}</strong></a><a class="dash-task" href="{{ route('requests.index',['estado'=>'Aprobado']) }}"><span>Por entregar ahora<small>Aprobadas y pendientes de despacho</small></span><strong>{{ $requests['Aprobado'] ?? 0 }}</strong></a><a class="dash-task" href="{{ route('restock.index') }}"><span>Productos por reponer<small>Saldo en el mínimo o por debajo</small></span><strong>{{ $lowStockCount }}</strong></a>
            <div class="dash-balances"><span>Valor actual del inventario · Catálogo</span><strong>S/ {{ number_format((float)$inventory->valor,2) }}</strong><span>{{ $inventory->total }} productos activos</span></div><a class="dash-footer-link" href="{{ route('requests.index') }}">Gestionar requerimientos →</a>
        </section>
    </div>

    <section class="dash-panel dash-table-panel"><div class="catalog-body catalog-heading"><div><p class="dash-section-label">Trazabilidad de cobros</p><h2>Últimos pagos del periodo</h2></div><a class="catalog-link" href="{{ route('commercial.payments.index',['desde'=>$rangeFrom,'hasta'=>$rangeTo]) }}">Ver todos →</a></div><div class="catalog-table-wrap"><table class="catalog-table"><thead><tr><th>Pago / Fecha</th><th>Cliente / Compra</th><th>Importe</th><th>Medio / Referencia</th><th>Constancia</th></tr></thead><tbody>
        @forelse($business['latestPayments'] as $payment)
            <tr><td>{{ $payment->codigo }}<small>{{ $payment->fecha_pago->format('d/m/Y') }}</small></td><td>{{ $payment->sale->ficha_cliente['nombre'] }}<small>{{ $payment->sale->codigo }}</small></td><td>{{ $payment->sale->moneda }} {{ number_format((float)$payment->monto,2) }}</td><td>{{ ['EFECTIVO'=>'Efectivo','TRANSFERENCIA'=>'Transferencia','DEPOSITO'=>'Depósito'][$payment->metodo] ?? $payment->metodo }}<small>{{ $payment->referencia ?: 'Sin referencia' }}</small></td><td><a class="catalog-link" href="{{ route('commercial.sales.receipt',[$payment->sale,$payment]) }}">Ver / imprimir</a></td></tr>
        @empty
            <tr><td colspan="5">No hay pagos registrados en estas fechas. Puedes cambiar el periodo de consulta.</td></tr>
        @endforelse
    </tbody></table></div></section>

    <details class="dash-panel biz-storage"><summary>Actividad de Kardex del mismo periodo <span>Entradas y salidas por {{ $group === 'semana' ? 'semana' : 'mes' }}</span></summary><p class="dashboard-legend dash-legend"><span><i class="dashboard-entry" aria-hidden="true"></i>Entradas</span><span><i class="dashboard-exit" aria-hidden="true"></i>Salidas</span></p>
    <ul class="dashboard-chart" aria-label="Movimientos del periodo. Selecciona una barra para abrir su historial" >
                    @foreach ($months as $month)
                        <li>
                            <span class="dashboard-month">{{ $month['label'] }}</span>
                            <div class="dashboard-bars">
                                @foreach (['ENTRADA' => ['entries', 'entradas', 'dashboard-entry'], 'SALIDA' => ['exits', 'salidas', 'dashboard-exit']] as $type => [$key, $label, $class])
                                    <a href="{{ route('kardex.index', ['tipo' => $type, 'desde' => $month['from'], 'hasta' => $month['to']]) }}"
                                        aria-label="{{ $month['label'] }}: {{ $month[$key] }} {{ $label }}. Ver en Kardex">
                                        <span class="dashboard-track" aria-hidden="true"><span class="{{ $class }}" style="width: {{ $month[$key] / $chartMax * 100 }}%"></span></span>
                                        <strong>{{ $month[$key] }}</strong>
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ul>
    </details>
    <footer class="dash-footer"><span>Los indicadores del periodo respetan el rango seleccionado; cartera y disponibilidad muestran la situación actual.</span><a href="{{ route('commercial.clients.index') }}">Directorio de clientes →</a></footer>
</section>
</x-app-layout>
