<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('usuario_id');
            if (Schema::hasTable('clientes')) {
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('SET NULL');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedido', function (Blueprint $table) {
            if (Schema::hasColumn('pedido', 'cliente_id')) {
                $table->dropForeign(['cliente_id']);
                $table->dropColumn('cliente_id');
            }
        });
    }
};
