<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandLot extends Model
{
    public const STATUSES = [
        'DISPONIBLE' => 'Disponible',
        'RESERVADO' => 'Reservado',
        'VENDIDO' => 'Vendido',
    ];

    protected $fillable = [
        'manzana', 'numero', 'area_m2', 'precio_total', 'moneda',
        'estado', 'partida_registral', 'observaciones', 'motivo_estado',
    ];

    protected $casts = ['area_m2' => 'decimal:2', 'precio_total' => 'decimal:2'];

    public function activeReservation()
    {
        return $this->hasOne(LotReservation::class, 'active_lot_id');
    }

    public function project()
    {
        return $this->belongsTo(RealEstateProject::class, 'real_estate_project_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
    public function sale() { return $this->hasOne(LotSale::class, 'land_lot_id'); }
}
