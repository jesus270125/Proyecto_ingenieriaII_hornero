<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propinas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->decimal('monto', 8, 2)->default(0);
            $table->string('metodo_pago')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('referencia')->nullable();
            $table->dateTime('fecha')->useCurrent();
            $table->timestamps();

            $table->foreign('venta_id')->references('id')->on('venta')->onDelete('SET NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propina');
    }
};
