@php
    $name = request()->route()?->getName() ?? '';
    $target = route('dashboard');
    $label = 'Volver al inicio';
    $parameter = fn ($key) => request()->route($key);

    if ($name === 'commercial.payments.index') {
        $target = route('commercial.sales.index');
        $label = 'Volver a cobros y deudas';
    } elseif ($name === 'commercial.sales.create' && $parameter('reservation')) {
        $target = route('commercial.reservations.show', $parameter('reservation'));
        $label = 'Volver a la reserva';
    } elseif ($name === 'commercial.sales.receipt' && $parameter('sale')) {
        $target = route('commercial.sales.show', $parameter('sale')).'#payment-history';
        $label = 'Volver a la compra y sus pagos';
    } elseif ($name === 'commercial.sales.configure' && $parameter('sale')) {
        $target = route('commercial.sales.show', $parameter('sale'));
        $label = 'Volver a la venta';
    } elseif (str_starts_with($name, 'commercial.lots.')) {
        $lot = $parameter('lot');
        if ($name === 'commercial.lots.edit' && $lot) {
            $target = route('commercial.lots.show', $lot);
            $label = 'Volver a la ficha del lote';
        } else {
            $project = $parameter('project') ?? $lot?->project;
            $target = $project ? route('commercial.projects.show', $project) : route('commercial.projects.index');
            $label = 'Volver a los lotes del proyecto';
        }
    } else {
        foreach ([
            'commercial.projects.' => ['commercial.projects.index', 'Volver a proyectos', 'project', 'commercial.projects.show'],
            'commercial.clients.' => ['commercial.clients.index', 'Volver a clientes', 'client', 'commercial.clients.show'],
            'commercial.plans.' => ['commercial.plans.index', 'Volver a planes de pago', null, null],
            'commercial.sales.' => ['commercial.sales.index', 'Volver a cobros y deudas', null, null],
            'commercial.reservations.' => ['commercial.reservations.index', 'Volver a reservas', null, null],
            'products.' => ['products.index', 'Volver a productos', 'product', 'products.show'],
            'users.' => ['users.index', 'Volver a usuarios', null, null],
        ] as $prefix => [$index, $text, $key, $detail]) {
            if (str_starts_with($name, $prefix) && $name !== $index) {
                if ($key && $detail && str_ends_with($name, '.edit') && $parameter($key)) {
                    $target = route($detail, $parameter($key));
                    $label = 'Volver a la ficha';
                } else {
                    $target = route($index);
                    $label = $text;
                }
                break;
            }
        }
    }
@endphp
@if($name !== 'dashboard')
    <nav class="erp-backbar reservation-no-print" aria-label="Navegación de regreso">
        <a class="catalog-button" href="{{ $target }}"><span aria-hidden="true">←</span> {{ $label }}</a>
    </nav>
@endif
