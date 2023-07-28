<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $attributes = [
        'stock_kilos' => 0,
        'stock_cajas' => 0,
        'stock_tapas' => 0
    ];

    protected $fillable = [
        'barcode',
        'nombre',
        'codigo_proveedor',
        'costo_kilo',
        'stock_kilos',
        'stock_cajas',
        'stock_tapas',
        'proveedor_id'
    ];
}
