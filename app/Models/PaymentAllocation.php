<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentAllocation extends Model {
    protected $guarded = ['id'];
    protected $casts = ['monto' => 'decimal:2'];
    public function installment() { return $this->belongsTo(SaleInstallment::class, 'sale_installment_id'); }
}
