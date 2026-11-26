<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_families', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('prefijo', 8)->unique();
            $table->unsignedBigInteger('ultimo_correlativo')->default(0);
            $table->timestamps();
        });

        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('simbolo', 12)->unique();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_family_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('measurement_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('stock_minimo')->default(5);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_family_id');
            $table->dropConstrainedForeignId('measurement_unit_id');
            $table->dropColumn('stock_minimo');
        });

        Schema::dropIfExists('measurement_units');
        Schema::dropIfExists('product_families');
    }
};
