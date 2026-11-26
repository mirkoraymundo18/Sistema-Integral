<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 8)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 120);
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('lot_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('land_lot_id')->constrained()->restrictOnDelete();
            $table->foreignId('active_lot_id')->nullable()->unique()->constrained('land_lots')->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('estado', 16)->default('ACTIVA');
            $table->timestamp('vence_en');
            $table->decimal('precio_acordado', 14, 2);
            $table->string('moneda', 3);
            $table->text('condiciones')->nullable();
            $table->json('ficha_lote');
            $table->json('ficha_cliente');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->timestamps();
            $table->index(['estado', 'vence_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_reservations');
        Schema::dropIfExists('clients');
    }
};
