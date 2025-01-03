<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 12)->unique();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("
            ALTER TABLE clientes
            ADD COLUMN nombre_completo VARCHAR(255)
            GENERATED ALWAYS AS (
                CONCAT(
                    identificador, ' - ',
                    nombre
                )
            ) VIRTUAL;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
