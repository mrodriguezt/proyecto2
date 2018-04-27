<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Xml extends Model
{
    protected $table ="xml";
    protected $fillable = [
        'ambiente','tipoEmision','razonSocial','nombreComercial','ruc','claveAcceso','codDoc','estab','ptoEmi','secuencial','dirMatriz','fechaEmision','dirEstablecimiento','obligadoContabilidad','tipoIdentificacionComprador','razonSocialComprador','identificacionComprador','direccionComprador','totalSinImpuestos','totalDescuento','propina','importeTotal','campoAdicional','enviado_ifs','fecha_envio','path','remember_token','created_at','updated_at','company'
    ];

}
