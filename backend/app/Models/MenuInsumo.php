<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MenuInsumo - RF33: Tabla pivote de Recetas
 *
 * Define la relación entre productos del menú e insumos,
 * especificando qué cantidad de cada insumo se requiere
 * para preparar una unidad del producto.
 */
class MenuInsumo extends Model
{
    use HasFactory;

    protected $table = 'menu_insumo';
    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'insumo_id',
        'cantidad_requerida',
    ];

    protected $casts = [
        'cantidad_requerida' => 'decimal:3',
    ];

    /**
     * Producto del menú de esta receta.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Insumo utilizado en esta receta.
     */
    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }
}
