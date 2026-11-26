<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('financing_plans', function (Blueprint $table) {
            $table->id(); $table->string('nombre', 80)->unique();
            $table->unsignedSmallInteger('meses'); $table->decimal('inicial_minima_porcentaje', 5, 2);
            $table->boolean('activo')->default(true); $table->timestamps();
        });
        foreach ([6,12,24,48] as $months) {
            DB::table('financing_plans')->insert(['nombre' => 'Plan '.$months.' meses', 'meses' => $months, 'inicial_minima_porcentaje' => 10, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('lot_sales', function (Blueprint $table) {
            $table->string('modalidad', 16)->nullable();
            $table->foreignId('financing_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('condiciones_financiamiento')->nullable();
        });
        Schema::create('sale_installments', function (Blueprint $table) {
            $table->id(); $table->foreignId('lot_sale_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('numero'); $table->date('vence_el');
            $table->decimal('monto', 14, 2); $table->decimal('pagado', 14, 2)->default(0);
            $table->timestamps(); $table->unique(['lot_sale_id','numero']); $table->index('vence_el');
        });
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id(); $table->foreignId('lot_payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_installment_id')->constrained()->restrictOnDelete();
            $table->decimal('monto', 14, 2); $table->timestamps();
            $table->unique(['lot_payment_id','sale_installment_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payment_allocations'); Schema::dropIfExists('sale_installments');
        Schema::table('lot_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financing_plan_id');
            $table->dropColumn(['modalidad','condiciones_financiamiento']);
        });
        Schema::dropIfExists('financing_plans');
    }
};
