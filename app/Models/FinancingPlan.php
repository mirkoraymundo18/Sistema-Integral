<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinancingPlan extends Model {
    protected $fillable = ['nombre','meses','inicial_minima_porcentaje','activo'];
    protected $casts = ['activo' => 'boolean', 'meses' => 'integer', 'inicial_minima_porcentaje' => 'decimal:2'];
}
