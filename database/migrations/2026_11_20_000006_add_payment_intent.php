<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('lot_payments', function (Blueprint $table) {
            $table->string('modo_pago',16)->default('LIBRE');
            $table->unsignedSmallInteger('cantidad_cuotas')->nullable();
        });
    }
    public function down(): void {
        Schema::table('lot_payments', fn (Blueprint $table) => $table->dropColumn(['modo_pago','cantidad_cuotas']));
    }
};
