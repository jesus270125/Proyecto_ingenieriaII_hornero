<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'precio',
        'descripcion',
        'imagen',
        'categoria',
    ];

    /**
     * RF33: Insumos que componen la receta de este producto.
     */
    public function insumos()
    {
        return $this->belongsToMany(Insumo::class, 'menu_insumo', 'menu_id', 'insumo_id')
                    ->withPivot('cantidad_requerida');
    }
}
