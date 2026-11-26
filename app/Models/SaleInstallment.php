<?php
namespace App\Models;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
class SaleInstallment extends Model {
    protected $guarded = ['id'];
    protected $casts = ['numero' => 'integer', 'vence_el' => 'date', 'monto' => 'decimal:2', 'pagado' => 'decimal:2'];
    public function sale() { return $this->belongsTo(LotSale::class, 'lot_sale_id'); }
    public function getSaldoAttribute(): string { return Money::format(Money::cents($this->monto) - Money::cents($this->pagado)); }
    public function getEstadoAttribute(): string {
        if (Money::cents($this->pagado) >= Money::cents($this->monto)) return 'Pagada';
        if ($this->vence_el->toDateString() < now('America/Lima')->toDateString()) return 'Vencida';
        return Money::cents($this->pagado) > 0 ? 'Pago parcial' : 'Pendiente';
    }
}
