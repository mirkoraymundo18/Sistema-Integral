<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'cantidad',
        'motivo',
        'estado',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    // Relación: Una solicitud pertenece a un Usuario (Operario)
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function decisionMaker()
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }

    public function movement()
    {
        return $this->belongsTo(KardexMovement::class, 'kardex_movement_id');
    }

    // Relación: Una solicitud pide un Producto específico
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}