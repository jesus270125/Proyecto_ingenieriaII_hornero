<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntosFidelidad extends Model
{
    use HasFactory;

    protected $table = 'puntos_fidelidad';

    protected $fillable = [
        'cliente_id',
        'puntos',
        'acumulado_total',
    ];
}
