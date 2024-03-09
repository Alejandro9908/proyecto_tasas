<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasaCambiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasa_cambios', function (Blueprint $table) {
            $table->id();
            $table->integer('moneda');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('venta_promedio', 10, 5);
            $table->decimal('compra_promedio', 10, 5);
            $table->integer('estado')->default(1);
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
        Schema::dropIfExists('tasa_cambios');
    }
}
