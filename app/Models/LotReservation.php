<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotReservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'vence_en' => 'datetime',
        'cancelled_at' => 'datetime',
        'precio_acordado' => 'decimal:2',
        'ficha_lote' => 'array',
        'ficha_cliente' => 'array',
    ];

    public function getCodigoAttribute(): string
    {
        return 'RES-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function lot()
    {
        return $this->belongsTo(LandLot::class, 'land_lot_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
    public function sale() { return $this->hasOne(LotSale::class, 'lot_reservation_id'); }
}
