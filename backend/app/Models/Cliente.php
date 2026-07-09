<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'num_documento',
        'nombre',
        'telefono',
        'email',
        'preferencias',
        'puntos_fidelidad',
    ];
}