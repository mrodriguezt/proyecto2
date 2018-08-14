<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentoRecibidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documento_recibidos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('comprobante',100);
            $table->string('serie_comprobante',100);
            $table->string('ruc_emisor',100);
            $table->string('razon_social_emisor',100);
            $table->date('fecha_emision');
            $table->date('fecha_autorizacion');
            $table->string('tipo_emision',10);
            $table->string('documento_relacionado',10);
            $table->string('identificacion_receptor',100);
            $table->string('clave_acceso',300);
            $table->string('numero_autorizador',300);
            $table->float('importe_total');
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
        Schema::dropIfExists('documento_recibidos');
    }
}
