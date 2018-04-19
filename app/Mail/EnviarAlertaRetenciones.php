<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnviarAlertaRetenciones extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected  $facturasSinAutorizacion;
    protected  $facturasSinRetencion;
    public function __construct($facturasSinAutorizacion, $facturasSinRetencion)
    {
        $this->facturasSinAutorizacion = $facturasSinAutorizacion;
        $this->facturasSinRetencion = $facturasSinRetencion;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject("Retenciones")->
        //bcc("mrodriguezt@santoscmi.com","Mary Rodríguez")->
        view("correos.alertaRetencion")->with("facturasSinAutorizacion",$this->facturasSinAutorizacion)->with("facturasSinRetencion",$this->facturasSinRetencion);

    }
}
