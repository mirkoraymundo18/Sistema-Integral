<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_units', function (Blueprint $table) {
            $table->boolean('permite_decimales')->default(false);
        });
        DB::table('measurement_units')->whereIn('simbolo', ['m', 'kg', 'l', 'L', 'litro'])->update(['permite_decimales' => true]);
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock_actual', 14, 2)->default(0)->change();
            $table->decimal('stock_minimo', 14, 2)->default(5)->change();
        });
        Schema::table('kardex_movements', function (Blueprint $table) {
            $table->decimal('cantidad', 14, 2)->change();
        });
        Schema::table('material_requests', function (Blueprint $table) {
            $table->decimal('cantidad', 14, 2)->change();
        });
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('saldo_anterior', 14, 2);
            $table->decimal('cantidad_contada', 14, 2);
            $table->string('motivo', 255);
            $table->foreignId('kardex_movement_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Keep decimal precision so a rollback does not truncate fractional balances.
        Schema::dropIfExists('stock_counts');
        Schema::table('measurement_units', function (Blueprint $table) {
            $table->dropColumn('permite_decimales');
        });
    }
};
