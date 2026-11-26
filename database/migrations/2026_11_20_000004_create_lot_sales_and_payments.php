<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('lot_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_reservation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('land_lot_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->decimal('precio_total', 14, 2);
            $table->decimal('total_pagado', 14, 2)->default(0);
            $table->string('moneda', 3);
            $table->string('estado', 16)->default('PENDIENTE');
            $table->json('ficha_cliente');
            $table->json('ficha_lote');
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('created_at');
            $table->index('estado');
        });
        Schema::create('lot_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('lot_sale_id')->constrained()->restrictOnDelete();
            $table->decimal('monto', 14, 2);
            $table->date('fecha_pago');
            $table->string('metodo', 20);
            $table->string('referencia', 100)->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['lot_sale_id', 'metodo', 'referencia']);
            $table->index('fecha_pago');
        });
    }
    public function down(): void {
        Schema::dropIfExists('lot_payments');
        Schema::dropIfExists('lot_sales');
    }
};
