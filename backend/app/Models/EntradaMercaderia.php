<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo EntradaMercaderia - RF32: Registro de Entradas de Mercadería
 *
 * Registra cada ingreso de insumos al almacén, manteniendo
 * un historial completo de compras/abastecimiento.
 */
class EntradaMercaderia extends Model
{
    use HasFactory;

    protected $table = 'entrada_mercaderia';
    public $timestamps = false; // Solo tiene created_at, no updated_at

    protected $fillable = [
        'insumo_id',
        'cantidad',
        'costo',
        'proveedor',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo' => 'decimal:2',
        'fecha' => 'date',
    ];

    /**
     * Insumo al que pertenece esta entrada.
     */
    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }
}
