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
}
