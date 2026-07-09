<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propina extends Model
{
    use HasFactory;

    protected $table = 'propinas';

    protected $fillable = [
        'venta_id',
        'usuario_id',
        'monto',
        'metodo_pago',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}