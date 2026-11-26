<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Relación con el producto
            $table->enum('tipo', ['ENTRADA', 'SALIDA']); // Tipo de movimiento
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->string('motivo_o_proyecto'); // Ej: "Compra a proveedor", "Asignación a Obra A"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_movements');
    }
};