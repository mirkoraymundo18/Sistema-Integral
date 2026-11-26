<span class="catalog-badge {{ match($reservation->estado) {
    'ACTIVA' => 'catalog-good',
    'CANCELADA' => 'catalog-danger',
    'CONVERTIDA' => 'bg-blue-50 text-blue-800',
    default => 'catalog-warning',
} }}">{{ match($reservation->estado) {
    'ACTIVA' => 'Activa',
    'CANCELADA' => 'Cancelada',
    'CONVERTIDA' => 'Convertida en venta',
    default => 'Vencida',
} }}</span>
