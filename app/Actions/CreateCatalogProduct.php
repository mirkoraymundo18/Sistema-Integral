<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Support\Facades\DB;

class CreateCatalogProduct
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $family = ProductFamily::lockForUpdate()->findOrFail($data['product_family_id']);
            $number = $family->ultimo_correlativo;

            do {
                $sku = sprintf('%s-%03d', $family->prefijo, ++$number);
            } while (Product::withTrashed()->whereRaw('UPPER(codigo_sku) = ?', [$sku])->exists());

            $family->ultimo_correlativo = $number;
            $family->save();

            return Product::create([...$data, 'codigo_sku' => $sku]);
        }, 3);
    }
}
