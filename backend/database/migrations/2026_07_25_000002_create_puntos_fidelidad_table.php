<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_fidelidad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->integer('puntos')->default(0);
            $table->integer('acumulado_total')->default(0);
            $table->timestamps();

            // Nota: la tabla `clientes` debe existir; si no, la FK puede omitirse.
            // $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_fidelidad');
    }
};
