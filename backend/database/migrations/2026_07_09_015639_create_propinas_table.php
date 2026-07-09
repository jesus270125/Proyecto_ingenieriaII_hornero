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
        Schema::create('propinas', function (Blueprint $table) {
            $table->id();
            // Relación con la tabla 'venta' existente en tu MySQL de XAMPP
            $table->foreignId('venta_id')->constrained('venta')->onDelete('cascade');
            // Relación con el mesero de la tabla 'usuarios'
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade'); 
            $table->decimal('monto', 8, 2);
            $table->string('metodo_pago'); // Tarjeta, Yape, Plin, Efectivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propinas');
    }
};