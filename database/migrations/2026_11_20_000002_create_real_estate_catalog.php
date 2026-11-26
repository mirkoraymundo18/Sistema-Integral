<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_projects', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 160);
            $table->string('ubicacion', 255);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('land_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('real_estate_project_id')->constrained()->restrictOnDelete();
            $table->string('manzana', 24);
            $table->string('numero', 24);
            $table->decimal('area_m2', 12, 2);
            $table->decimal('precio_total', 14, 2);
            $table->string('moneda', 3)->default('PEN');
            $table->string('estado', 16)->default('DISPONIBLE')->index();
            $table->string('partida_registral', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('motivo_estado', 255)->nullable();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['real_estate_project_id', 'manzana', 'numero'], 'land_lots_project_location_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_lots');
        Schema::dropIfExists('real_estate_projects');
    }
};
