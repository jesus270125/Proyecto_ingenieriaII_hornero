<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PropinaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('propinas')->insert([
            [
                'venta_id' => 1,
                'monto' => 5.00,
                'metodo_pago' => 'Yape',
                'usuario_id' => 4,
                'referencia' => 'YP12345',
                'fecha' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'venta_id' => 2,
                'monto' => 3.00,
                'metodo_pago' => 'Tarjeta',
                'usuario_id' => 4,
                'referencia' => 'TX98765',
                'fecha' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
