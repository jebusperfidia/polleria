<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('id');
            $table->string('nombre', 150);
            $table->string('codigo_proveedor', 10);
            $table->string('barcode', 150)->unique();
            $table->double('costo_kilo');
            $table->double('stock_kilos');
            $table->integer('stock_cajas');
            $table->integer('stock_tapas');
            $table->unsignedBigInteger('proveedor_id');
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('providers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
