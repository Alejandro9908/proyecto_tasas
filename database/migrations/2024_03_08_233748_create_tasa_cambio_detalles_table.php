<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasaCambioDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasa_cambio_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tasa_cambio_id')->references('id')->on('tasa_cambios');
            $table->date('fecha');
            $table->decimal('venta', 10, 5);
            $table->decimal('compra', 10, 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasa_cambio_detalles');
    }
}
