<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo VentaDetalle - Detalle de productos vendidos.
 *
 * Registra cada producto individual dentro de una venta,
 * permitiendo al módulo de inventarios saber qué se vendió.
 */
class VentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_detalle';
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'menu_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
