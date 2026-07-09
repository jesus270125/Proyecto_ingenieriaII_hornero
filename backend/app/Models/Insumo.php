<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Insumo - RF31: Catálogo y Registro de Insumos Base
 *
 * Representa los insumos/materias primas utilizados en la
 * preparación de productos del menú.
 */
class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumo';

    protected $fillable = [
        'nombre',
        'categoria',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'estado',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
    ];

    /**
     * RF32: Historial de entradas de mercadería de este insumo.
     */
    public function entradas()
    {
        return $this->hasMany(EntradaMercaderia::class, 'insumo_id');
    }

    /**
     * RF33: Productos del menú que usan este insumo (recetas).
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_insumo', 'insumo_id', 'menu_id')
                    ->withPivot('cantidad_requerida');
    }

    /**
     * RF34: Verifica si el stock actual está por debajo del mínimo.
     */
    public function getStockBajoAttribute()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    /**
     * Scope para obtener solo insumos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * RF34: Scope para obtener insumos con stock bajo.
     */
    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo')
                     ->where('estado', 'activo');
    }
}
