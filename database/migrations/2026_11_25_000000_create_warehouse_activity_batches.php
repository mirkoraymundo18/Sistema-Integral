<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('warehouse_activity_batches')) return;
        Schema::create('warehouse_activity_batches', function (Blueprint $table) {
            $table->string('batch_key', 100)->primary();
            $table->string('origin', 40);
            $table->foreignId('responsible_id')->constrained('users')->restrictOnDelete();
            $table->json('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // The loader refuses to import a month if its ledger markers survive without metadata.
        Schema::dropIfExists('warehouse_activity_batches');
    }
};
