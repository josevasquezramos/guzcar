<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trabajo_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajo_id')
                ->constrained('trabajos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('servicio_id')
                ->constrained('servicios')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->text('detalle')->nullable();
            $table->decimal('precio');
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajo_servicios');
    }
};
