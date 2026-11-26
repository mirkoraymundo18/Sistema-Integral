<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LotPayment extends Model {
    protected $guarded = ['id'];
    protected $casts = ['monto' => 'decimal:2', 'fecha_pago' => 'date'];
    public function sale() { return $this->belongsTo(LotSale::class, 'lot_sale_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function getCodigoAttribute(): string { return 'PAG-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class); }
    public function getConceptoAttribute(): string {
        return match ($this->modo_pago) {
            'SIGUIENTE' => 'Pago del siguiente vencimiento',
            'ADELANTO' => 'Pago o adelanto de '.$this->cantidad_cuotas.' cuotas',
            'SALDO' => 'Cancelación del saldo total',
            default => 'Abono libre',
        };
    }
}
