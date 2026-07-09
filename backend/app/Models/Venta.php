<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'venta';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'monto',
        'metodo_pago', // Se añade para registrar cómo pagaron la venta base
    ];
    
 public function propina()
    {
        return $this->hasOne(Propina::class, 'venta_id');
    }   
        'metodo_pago',
    ];

    /**
     * Pedidos asociados a esta venta.
     */
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'venta_id');
    }

    /**
     * Detalle de productos vendidos.
     */
    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }
}
