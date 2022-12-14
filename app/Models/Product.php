<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $attributes = [
        'stock_kilos' => 0,
    ];

    protected $fillable = [
        'barcode',
        'nombre',
        'costo_kilo',
        'proveedor_id'
    ];
}
