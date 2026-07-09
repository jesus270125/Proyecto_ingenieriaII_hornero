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
            $table->string('num_documento', 11)->unique(); // Para DNI (8 dígitos) o RUC (11 dígitos)
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('preferencias')->nullable(); // RF38: Historial y Preferencias (Ej: "Prefiere parte pecho")
            $table->integer('puntos_fidelidad')->default(0); // Para estrategias de fidelización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};