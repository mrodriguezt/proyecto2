<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Documento_recibido extends Model
{
    protected $table ="documento_recibidos";
    protected $fillable = [
        'id','comprobante','serie_comprobante','ruc_emisor','razon_social_emisor','fecha_emision','fecha_autorizacion','tipo_emision','documento_relacionado','identificacion_receptor','clave_acceso','numero_autorizador','importe_total','mensaje','voucher_no','invoice_no','company'
    ];
}
