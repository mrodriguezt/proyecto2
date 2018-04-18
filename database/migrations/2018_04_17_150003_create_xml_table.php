<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXmlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xml', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ambiente',3);
            $table->string('tipoEmision',3);
            $table->string('razonSocial',200);
            $table->string('nombreComercial',150);
            $table->string('ruc',20);
            $table->string('claveAcceso',250);
            $table->string('codDoc',3);
            $table->string('estab',3);
            $table->string('ptoEmi',3);
            $table->string('secuencial',10);
            $table->string('dirMatriz',200);
            $table->date('fechaEmision');
            $table->string('dirEstablecimiento',100);
            $table->string('obligadoContabilidad',3);
            $table->string('tipoIdentificacionComprador',3);
            $table->string('razonSocialComprador',200);
            $table->string('identificacionComprador',100);
            $table->string('direccionComprador',400);
            $table->float('totalSinImpuestos');
            $table->float('totalDescuento');
            $table->float('propina');
            $table->float('importeTotal');
            $table->text('campoAdicional');
            $table->string('enviado_ifs',3);
            $table->string('fecha_envio',3);
            $table->string('path');
            $table->rememberToken();
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
        Schema::dropIfExists('xml');
    }
}
