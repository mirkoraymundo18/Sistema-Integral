<?php

namespace App\Exports;

class KardexExport
{
    public function __construct(private SpreadsheetExport $spreadsheet)
    {
    }

    public function write(iterable $movements): string
    {
        $rows = function () use ($movements) {
            foreach ($movements as $movement) {
                yield [
                    $movement->created_at->format('d/m/Y H:i:s'),
                    $movement->product?->codigo_sku ?? '',
                    $movement->product?->nombre ?? '',
                    $movement->tipo,
                    (float) $movement->cantidad,
                    $movement->product?->unit?->simbolo ?? 'und.',
                    (float) $movement->precio_unitario,
                    $movement->motivo_o_proyecto,
                    $movement->user?->name ?? 'Sin responsable registrado',
                ];
            }
        };

        return $this->spreadsheet->write(
            'Kardex',
            ['Fecha y hora', 'SKU', 'Producto', 'Tipo', 'Cantidad', 'Unidad', 'Precio unitario (S/)', 'Motivo', 'Responsable'],
            [23, 16, 34, 13, 13, 13, 22, 34, 34],
            $rows(),
        );
    }
}
