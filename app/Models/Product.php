<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_sku',
        'nombre',
        'descripcion',
        'stock_actual',
        'precio_unitario',
        'product_family_id',
        'measurement_unit_id',
        'stock_minimo',
    ];

    public function family()
    {
        return $this->belongsTo(ProductFamily::class, 'product_family_id');
    }

    public function unit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id');
    }

    public function scopePorReponer(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    public function kardexMovements()
    {
        return $this->hasMany(KardexMovement::class);
    }
}