<span class="catalog-badge {{ match($lot->estado) {
    'DISPONIBLE' => 'catalog-good',
    'RESERVADO' => 'catalog-warning',
    default => 'bg-slate-100 text-slate-700',
} }}">{{ \App\Models\LandLot::STATUSES[$lot->estado] }}</span>
