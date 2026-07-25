<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('metodo_pago');
            // Si la tabla clientes existe, agregar FK
            if (Schema::hasTable('clientes')) {
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('SET NULL');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venta', function (Blueprint $table) {
            if (Schema::hasColumn('venta', 'cliente_id')) {
                $table->dropForeign([ 'cliente_id' ]);
                $table->dropColumn('cliente_id');
            }
        });
    }
};
