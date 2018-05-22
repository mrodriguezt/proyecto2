<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\EnviarAlertaRetenciones;

class AlertaRetenciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:alertaRetenciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alertar de las retenciones q no han terminado de ser procesadas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $facturasSinRetencion = array();
        $facturasSinAutorizacion = array();
        $facturas = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
            ->where('INVOICE_DATE','>=','2018-01-01')
            ->where('SERIES_ID','!=','SI')
            ->where('SERIES_ID','!=','41')
            ->where('SERIES_ID','!=','04')
            ->where('SERIES_ID','!=','05')
            ->whereIn('COMPANY',['EC01','EC03'])
            ->select('INVOICE_ID','COMPANY','INVOICE_NO')
            ->get();
        foreach ($facturas as $factura){
            $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION')
                ->where('INVOICE_ID',$factura->invoice_id)
                ->where('COMPANY',$factura->company)
                ->where('ROWSTATE','!=','Cancelled')
                ->select('RETENTION_NO',\DB::connection('oracle')->raw('NVL(C_ELECTRONIC_INVOICE_AUTH_API.Get_Obj_State(COMPANY,C_INVOICE_ID),\'NO\') AS AUTORIZADO'))
                ->get()->first();
            if(isset($retencion->autorizado)){
                if($retencion->autorizado=="NO" || $retencion->autorizado!="Autorizada") {
                    echo "NO AUTORIZADO".$factura->invoice_no;
                    $facturasSinAutorizacion[] = $factura->company." ".$factura->invoice_no;
                }

            }else{
                echo "NO EXISTE RETENCION".$factura->invoice_no;
                $facturasSinRetencion[] = $factura->company." ".$factura->invoice_no;

            }

        }

        if(count($facturasSinRetencion)>0 || count($facturasSinAutorizacion)>0) {
            Mail::to('jleon@santoscmi.com')->send(new EnviarAlertaRetenciones($facturasSinAutorizacion, $facturasSinRetencion));
            Mail::to('rvasconez@santoscmi.com')->send(new EnviarAlertaRetenciones($facturasSinAutorizacion, $facturasSinRetencion));
        }
    }
}
