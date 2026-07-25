<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PuntosFidelidadSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('puntos_fidelidad')->insert([
            [
                'cliente_id' => null,
                'puntos' => 120,
                'acumulado_total' => 120,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
