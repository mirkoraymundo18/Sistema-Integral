<?php
namespace App\Models;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
class LotSale extends Model {
    protected $guarded = ['id'];
    protected $casts = ['precio_total' => 'decimal:2', 'total_pagado' => 'decimal:2', 'ficha_cliente' => 'array', 'ficha_lote' => 'array', 'condiciones_financiamiento' => 'array'];
    public function getCodigoAttribute(): string { return 'VEN-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
    public function getSaldoAttribute(): string { return Money::format(Money::cents($this->precio_total) - Money::cents($this->total_pagado)); }
    public function reservation() { return $this->belongsTo(LotReservation::class, 'lot_reservation_id'); }
    public function lot() { return $this->belongsTo(LandLot::class, 'land_lot_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function payments() { return $this->hasMany(LotPayment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function installments() { return $this->hasMany(SaleInstallment::class); }
    public function plan() { return $this->belongsTo(FinancingPlan::class, 'financing_plan_id'); }
}
