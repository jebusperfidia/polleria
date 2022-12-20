<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    use HasFactory;



    protected $attributes = [
        'stock_cajas' => 0,
        'stock_tapas' => 0
    ];

       protected $fillable = [
        'barcode',
        'kilos',
        'producto_id'
    ];
}
