<?php

namespace App\Http\Controllers;

use App\Company_tab;
use Illuminate\Http\Request;
use DOMDocument;
use SoapClient;
class FacturacionElectronicaController extends Controller
{
    //
    public function facturacionElectronica()
    {
       $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');
       return view('documentosElectronicos.facturacion')->with('companias',$companias);
    }

    public function layoutFacturacion()
    {
        return response()->view("documentosElectronicos.layout.layoutFacturacion")->header('Content-Type', 'text/xml');
    }
    public function dataFacturacion($fechaInicio,$fechaFin,$compania){

        $facturaIFS = \DB::connection('oracle')->table('INSTANT_INVOICE')
            ->whereIn('SERIES_ID', ['PR','18','01'])
            ->whereBetween('INVOICE_DATE', [$fechaInicio,$fechaFin])
            ->where('COMPANY', $compania)
            ->where('INVOICE_NO', 'LIKE','%-002%')
            ->select('INVOICE_NO','INVOICE_DATE','NAME','GROSS_AMOUNT','NET_AMOUNT','PAYMENT_ADDRESS_ID','SERIES_ID','OBJSTATE')
            ->get();
        foreach ($facturaIFS as $fact){
            $DOCUMENTOENVIADO= \DB::connection('ifscmi_int')->table('FE_DOCUMENTOS_ENVIADOS')
                ->where('COMPANIA', $compania)
                ->where('TIPO_DOCUMENTO', '01')
                ->where('NO_DOCUMENTO', $fact->invoice_no)
                ->select('FECHA_ENVIO','USUARIO_ENVIO','ERROR')
                ->get()->first();
            /*if($fact->invoice_no==='001-002-000000994')
                echo $DOCUMENTOENVIADO->fecha_envio;*/
            if(isset($DOCUMENTOENVIADO->fecha_envio)) {
                $fact->fecha_envio = $DOCUMENTOENVIADO->fecha_envio;
                $fact->usuario_envio = $DOCUMENTOENVIADO->usuario_envio;
                $fact->error = $DOCUMENTOENVIADO->error;
            }else{
                $fact->fecha_envio = "";
                $fact->usuario_envio = "";
                $fact->error = "";
            }

        }

        $data = ["facturas"=>$facturaIFS];
        return response()->view("documentosElectronicos.data.dataFacturacion",$data)->header('Content-Type', 'text/xml');
    }
    function cambiarExpresionRegular($cadena){
        return preg_replace('/[^\p{L}\p{N}\&%#\ С$\/=?\}\]\{\[+*~^;:,.-_ͼ\(\)\'\"]/u', '', $cadena);
    }
    public function enviarFacturaTandi(Request $request){
        $INVOICE_ID=$request["INVOICE_ID"];
        $codigoFactura = explode("-",$INVOICE_ID);
        $COMPANY = \DB::connection('oracle')->table('COMPANY')
                    ->where('COMPANY',$request["COMPANY"])
                    ->select('COMPANY','NAME')
                    ->get()->first();
        $RUC = \DB::connection('oracle')->table('COMPANY_INVOICE_INFO')
            ->where('COMPANY',$request["COMPANY"])
            ->select('VAT_NO')
            ->get()->first();

        $FACTURA = \DB::connection('oracle')->table('INSTANT_INVOICE')
           ->where('INVOICE_NO', $INVOICE_ID)
            ->select('INVOICE_NO','INVOICE_DATE','IDENTITY','NAME','NET_AMOUNT','INVOICE_ID','GROSS_AMOUNT','CUST_REF','AUTHORIZE_CODE','PAYMENT_ADDRESS_ID','PAY_TERM_DESCRIPTION')
            ->get()->first();
        $CUSTOMER = \DB::connection('oracle')->table('CUSTOMER_INFO_ADDRESS')
            ->where('CUSTOMER_ID', $FACTURA->identity)
            ->where('ADDRESS_ID', '01')
            ->select('ADDRESS')
            ->get()->first();

         $AMBIENTE= \DB::connection('ifscmi_int')->table('FE_TABLA_5')
           ->where('ACTIVO', '1')
            ->select('IP_WEBSERVICE','CODIGO')
            ->get()->first();
         $DIRECCION = \DB::connection('oracle')->table('COMPANY_ADDRESS')
           ->where('COMPANY', $request["COMPANY"])
           ->where('ADDRESS_ID', "1")
            ->select('ADDRESS_LOV')
            ->get()->first();

           /* $xml = new DomDocument('1.0', 'UTF-8');
            $raiz = $xml->createElement('factura');

            $domAttribute = $xml->createAttribute('id');
            $domAttribute->value = 'comprobante';
            $raiz->appendChild($domAttribute);
            $domAttribute = $xml->createAttribute('version');
            $domAttribute->value = '1.1.0';
            $raiz->appendChild($domAttribute);
            $xml->appendChild($raiz);
            $razonSocial = 'SANTOSCMI S.A.';
            $infoTributaria = $xml->createElement('infoTributaria');
            $infoTributaria = $raiz->appendChild($infoTributaria);
            $nodo = $xml->createElement('ambiente', $AMBIENTE->codigo);
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('tipoEmision', '1');
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('razonSocial', $razonSocial);
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('ruc', '1791280733001');
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('codDoc', '01');
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('estab', $codigoFactura[0]);
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('ptoEmi', $codigoFactura[1]);
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('secuencial', $codigoFactura[2]);
            $infoTributaria->appendChild($nodo);
            $nodo = $xml->createElement('dirMatriz', $DIRECCION->address_lov);
            $infoTributaria->appendChild($nodo);*/
            $tipoID = strlen($FACTURA->identity);
            $tipoIdentificacionComprador=0;
            if($tipoID==10){
                $pos = strpos($FACTURA->identity,"-");
                if ($pos === false){
                    $tipoIdentificacionComprador= '05';
                }else{
                    $tipoIdentificacionComprador= '06';
                }
            }else{
                if($tipoID==13){
                    $tipoIdentificacionComprador= '04';
                }else{
                    $tipoIdentificacionComprador= '06';
                }
            }
           /* $infoFactura = $xml->createElement('infoFactura');
            $infoFactura = $raiz->appendChild($infoFactura);
            $nodo = $xml->createElement('fechaEmision', date('d/m/Y',strtotime($FACTURA->invoice_date)));
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('contribuyenteEspecial',"2289");
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('obligadoContabilidad', "SI");
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('tipoIdentificacionComprador', $tipoIdentificacionComprador);
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('razonSocialComprador', $FACTURA->name);
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('identificacionComprador', $FACTURA->identity);
            $infoFactura->appendChild($nodo);
             $nodo = $xml->createElement('direccionComprador', "PRUEBA DIRECCION");
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('direccionComprador', "PRUEBA DE FACTURA");
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('totalSinImpuestos', $FACTURA->net_amount);
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('totalDescuento', 0);
            $infoFactura->appendChild($nodo);
            $nodo = $xml->createElement('totalConImpuestos');
            $totalConImpuestos = $infoFactura->appendChild($nodo);*/

            $IMPUESTOS = \DB::connection('oracle')->table('INSTANT_INVOICE_ITEM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->whereNotNull('VAT_CODE')
            ->select(\DB::connection('oracle')->raw('SUM(VAT_CURR_AMOUNT) AS IMPUESTO'),\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) AS BASE_IMPONIBLE,VAT_CODE,VAT_PERCENT'))
            ->groupBy('VAT_CODE','VAT_PERCENT')
            ->get();
            $a_totalConImpuestos = array();
            $i=0;
            foreach($IMPUESTOS AS $IMPUESTO) {

                $porcentaje = \DB::connection('oracle')->table('STATUTORY_FEE_DEDUCT_MULTIPLE')
                    ->where('COMPANY', $COMPANY->company)
                    ->where('FEE_CODE', $IMPUESTO->vat_code)
                    ->select('FEE_RATE')
                    ->get()->first();

                $TABLA16= \DB::connection('ifscmi_int')->table('FE_TABLA_16')
                    ->where('PORCENTAJE', $porcentaje->fee_rate)
                    ->get()->first();

               /* $nodo = $xml->createElement('totalImpuesto');
                $totalImpuesto = $totalConImpuestos->appendChild($nodo);
                $nodo = $xml->createElement('codigo', $TABLA15->codigo_sri);
                $totalImpuesto->appendChild($nodo);
                $nodo = $xml->createElement('codigoPorcentaje', $TABLA16->codigo);
                $totalImpuesto->appendChild($nodo);
                $nodo = $xml->createElement('baseImponible', round($IMPUESTO->base_imponible,2));
                $totalImpuesto->appendChild($nodo);
                $nodo = $xml->createElement('tarifa', round($IMPUESTO->vat_percent,2));
                $totalImpuesto->appendChild($nodo);
                $nodo = $xml->createElement('valor', $IMPUESTO->impuesto);
                $totalImpuesto->appendChild($nodo);*/

                $a_totalConImpuestos["totalImpuesto"][$i] = array('codigo' => $TABLA16->codigo,
                    'codigoPorcentaje'  => $TABLA16->codigo,
                    'tarifa'=>$porcentaje->fee_rate,
                    'baseImponible'  =>  round($IMPUESTO->base_imponible,2),
                    'valor'  => round($IMPUESTO->impuesto,2)
                );
                $i++;
            }
      /*  $nodo = $xml->createElement('propina', 0);
        $infoFactura->appendChild($nodo);
        $nodo = $xml->createElement('importeTotal', round($FACTURA->gross_amount,2));
        $infoFactura->appendChild($nodo);
        $nodo = $xml->createElement('moneda', "DOLAR");
        $infoFactura->appendChild($nodo);
        $nodo = $xml->createElement('pagos');
        $PAGOS = $infoFactura->appendChild($nodo);
        $nodo = $xml->createElement('pago');
        $PAGO = $PAGOS->appendChild($nodo);
        $nodo = $xml->createElement('formaPago',$FACTURA->payment_address_id);
        $PAGO->appendChild($nodo);
        $nodo = $xml->createElement('total',$FACTURA->gross_amount);
        $PAGO->appendChild($nodo);
        $nodo = $xml->createElement('plazo',30);
        $PAGO->appendChild($nodo);
        $nodo = $xml->createElement('unidadTiempo','dias');
        $PAGO->appendChild($nodo);
        $detalles = $xml->createElement('detalles');
        $detalles = $raiz->appendChild($detalles);*/
        $a_pago= array();
        $dias= floatval($FACTURA->pay_term_description);

        $a_pago["pago"][0] = array('formaPago' =>$FACTURA->payment_address_id,
            'total'  => $FACTURA->gross_amount,
            'plazo'  => $dias,
            'unidadTiempo'  => "dias"
        );

        $a_detalle = array();
        $i=0;
        $ITEMS = \DB::connection('oracle')->table('INSTANT_INVOICE_ITEM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->whereNotNull('QUANTITY')
            ->whereNotNull('PRICE')
           ->select('VAT_CODE','VAT_PERCENT','OBJECT_ID','DESCRIPTION','QUANTITY','PRICE','NET_CURR_AMOUNT','VAT_CURR_AMOUNT')
            ->get();
        foreach($ITEMS AS $ITEM){


            $porcentaje = \DB::connection('oracle')->table('STATUTORY_FEE_DEDUCT_MULTIPLE')
                ->where('COMPANY', $COMPANY->company)
                ->where('FEE_CODE', $ITEM->vat_code)
                ->select('FEE_RATE')
                ->get()->first();

            $TABLA16= \DB::connection('ifscmi_int')->table('FE_TABLA_16')
                ->where('PORCENTAJE', $porcentaje->fee_rate)
                ->get()->first();

            $ITEM->description = substr($ITEM->description,0,300);


            $a_detalle["detalle"][$i] = array('codigoPrincipal' => $ITEM->object_id,
                'descripcion'  => $this->cambiarExpresionRegular($ITEM->description),
                'cantidad'  =>  $ITEM->quantity,
                'precioUnitario'  => abs(round($ITEM->price,2)),
                'descuento'  => 0,
                'precioTotalSinImpuesto'  => abs(round($ITEM->net_curr_amount,2)),
                'impuestos'  => array('impuesto' => array(
                    'codigo'=>$TABLA16->codigo,
                    'codigoPorcentaje'=>$TABLA16->codigo,
                    'tarifa'=>$ITEM->vat_percent,
                    'baseImponible'=>abs(round($ITEM->net_curr_amount,2)),
                    'valor'=>abs(round($ITEM->vat_curr_amount,2))
                ))
            );
            $i++;

/*            $nodo = $xml->createElement('detalle');
            $detalle = $detalles->appendChild($nodo);
            $nodo = $xml->createElement('codigoPrincipal',$ITEM->object_id);
            $detalle->appendChild($nodo);
            //$nodo = $xml->createElement('descripcion',$ITEM->description);
            //$nodo = $xml->createElement('descripcion',$this->cambiarExpresionRegular($ITEM->description));
            $nodo = $xml->createElement('descripcion',"");
            $detalle->appendChild($nodo);
            $nodo = $xml->createElement('cantidad',$ITEM->quantity);
            $detalle->appendChild($nodo);
            $nodo = $xml->createElement('precioUnitario',abs(round($ITEM->price,2)));
            $detalle->appendChild($nodo);
            $nodo = $xml->createElement('descuento',0);
            $detalle->appendChild($nodo);
            $nodo = $xml->createElement('precioTotalSinImpuesto',abs(round($ITEM->net_curr_amount,2)));
            $detalle->appendChild($nodo);


            $nodo = $xml->createElement('impuestos');
            $impuestos = $detalle->appendChild($nodo);
            $nodo = $xml->createElement('impuesto');
            $impuesto = $impuestos->appendChild($nodo);

            $nodo = $xml->createElement('codigo', $TABLA15->codigo_sri);
            $impuesto->appendChild($nodo);
            $nodo = $xml->createElement('codigoPorcentaje', $TABLA16->codigo);
            $impuesto->appendChild($nodo);
            $nodo = $xml->createElement('tarifa', $ITEM->vat_percent);
            $impuesto->appendChild($nodo);
           $nodo = $xml->createElement('baseImponible', abs(round($ITEM->net_curr_amount,2)));
            $impuesto->appendChild($nodo);
            $nodo = $xml->createElement('valor', $ITEM->vat_curr_amount);
            $impuesto->appendChild($nodo);*/

        }

       /* $infoAdicional = $xml->createElement('infoAdicional');
        $infoAdicional = $raiz->appendChild($infoAdicional);
        $campoAdicional = $xml->createElement('campoAdicional','RVASCONEZ');
        $campoAdicional = $infoAdicional->appendChild($campoAdicional);
        $domAttribute = $xml->createAttribute('nombre');
        $domAttribute->value = 'CERTIFICADO PROPIETARIO';
        $campoAdicional->appendChild($domAttribute);
        $campoAdicional = $xml->createElement('campoAdicional','COMPANY');
        $campoAdicional = $infoAdicional->appendChild($campoAdicional);
        $domAttribute = $xml->createAttribute('nombre');
        $domAttribute->value = 'EC01';
        $campoAdicional->appendChild($domAttribute);
        $campoAdicional = $xml->createElement('campoAdicional','INVOICE_ID');
        $campoAdicional = $infoAdicional->appendChild($campoAdicional);
        $domAttribute = $xml->createAttribute('nombre');
        $domAttribute->value = $_POST["INVOICE_ID"];
        $campoAdicional->appendChild($domAttribute);

        $email = $xml->createElement('email','rvasconez@santoscmi.com');
        $raiz->appendChild($email);
        $nombreUsuario = $xml->createElement('nombreUsuario','mrodriguezt');
        $raiz->appendChild($nombreUsuario);
        $nombreUsuario = $xml->createElement('claveUsuario','mrodriguezt');
        $raiz->appendChild($nombreUsuario);
        $nombreUsuario = $xml->createElement('adicional');
        $raiz->appendChild($nombreUsuario);


        $xml->formatOutput = true;
        //$path = public_path().'/rfq/';
        $el_xml = $xml->saveXML();
        $nombreArchivo = public_path()."/atsExport/FACTURA-".$FACTURA->invoice_no.".xml";
        $xml->save($nombreArchivo);*/


        $request = array('factura' => array(
            array(
                'id'=> 'comprobante',
                'version'=> '1.1.0'
            ),
            'infoTributaria' => array('ambiente'=>$AMBIENTE->codigo,
                'tipoEmision'=>"1",
                'razonSocial'=>$COMPANY->name,
                'ruc'=>$RUC->vat_no,
                'codDoc'=>'01',
                'estab'=>$codigoFactura[0],
                'ptoEmi'=>$codigoFactura[1],
                'secuencial'=>$codigoFactura[2],
                'dirMatriz'=> $DIRECCION->address_lov
            ),
            'infoFactura' => array('fechaEmision'=>date('d/m/Y',strtotime($FACTURA->invoice_date)),
                'dirEstablecimiento'=>$DIRECCION->address_lov,
                'contribuyenteEspecial'=>'2289',// CONTRIBUYENTE ESPECIAL
                'obligadoContabilidad'=>"SI",
                'tipoIdentificacionComprador'=>$tipoIdentificacionComprador,
                'razonSocialComprador'=>$FACTURA->name,
                'identificacionComprador'=>$FACTURA->identity,
                'direccionComprador'=>$this->cambiarExpresionRegular($CUSTOMER->address),
                'totalSinImpuestos'=>$FACTURA->net_amount,
                'totalDescuento'=>0,
                'totalConImpuestos' => $a_totalConImpuestos,
                'propina' => 0,
                'importeTotal' => round($FACTURA->gross_amount,2),
                'pagos' => $a_pago
            ),
            'detalles' => $a_detalle,
            'tipoNegociable' => array('correo'=>'mrodriguezt@santoscmi.com'),
            'infoAdicional' => array(
                'campoAdicional'=>array(
                    array(
                        '_'=>"EC01",
                        'nombre'=>'COMPANY'
                    ),
                    array(
                        '_'=>$_POST["INVOICE_ID"],
                        'nombre'=>'INVOICE_ID'
                    ),
                    array(
                        '_'=>'RVASCONEZ',
                        'nombre'=>'CERTIFICADO PROPIETARIO'
                    )
                )
            )
        ),
            'email' => 'mrodriguezt@santoscmi.com',
            'nombreUsuario' => 'mrodriguezt',
            'claveUsuario' => 'mrodriguezt',
            'adicional' => ""
        );




        $url='http://'.$AMBIENTE->ip_webservice.'/ws/invoiceIn?wsdl';
        try{
            $opts = array(
                'http'=>array(
                    'user_agent' => 'foo'
                )
            );

            //cabeceras
            $context = stream_context_create($opts);
            $ws = new SoapClient($url,array(
                    'stream_context' => $context,
                    'trace'=>true,
                    'cache_wsdl' => WSDL_CACHE_NONE
                )
            );
            $DOCUMENTOENVIADO= \DB::connection('ifscmi_int')->table('FE_DOCUMENTOS_ENVIADOS')
                ->where('COMPANIA', $COMPANY->company)
                ->where('TIPO_DOCUMENTO', '01')
                ->where('NO_DOCUMENTO', $FACTURA->invoice_no)
                ->select('NO_DOCUMENTO')
                ->get()->first();
            try{
//               $resultado = $ws->generateInvoice(array('factura'=>new \SoapVar($el_xml,\XSD_ANYXML),'email'=>'rvasconez@santoscmi.com','nombreUsuario'=>'rvasconez','claveUsuario'=>'rvasconez'),"mrodriguezt@santoscmi.com","mrodriguezt","mrodriguezt","");
               $resultado = $ws->generateInvoice($request,"mrodriguezt@santoscmi.com","mrodriguezt","mrodriguezt","");
                if($resultado->response=="0"){
                    $error="OK";

                }else{
                    if($resultado->response==2){
                        $error="Usuario WEBSERVICE Incorrecto";
                    }else{
                        $error=$resultado->response;
                    }
                }
                if(isset($DOCUMENTOENVIADO->no_documento)){
                    \DB::connection('ifscmi_int')->table('FE_DOCUMENTOS_ENVIADOS')
                        ->where('COMPANIA', $COMPANY->company)
                        ->where('TIPO_DOCUMENTO', '01')
                        ->where('NO_DOCUMENTO', $FACTURA->invoice_no)
                        ->update(['FECHA_ENVIO' => date('Y-m-d'),'USUARIO_ENVIO'=>auth()->user()->username,'ERROR'=>$error]);
                }else{
                    \DB::connection('ifscmi_int')->table('FE_DOCUMENTOS_ENVIADOS')->insert(
                        ['COMPANIA'=>$COMPANY->company,'TIPO_DOCUMENTO' => '01', 'NO_DOCUMENTO' => $FACTURA->invoice_no, 'FECHA_ENVIO' => date('Y-m-d'),'USUARIO_ENVIO'=>auth()->user()->username,'ERROR'=>$error]
                    );
                }

            }catch(Exception $e){
                print_r($e);

            }
           echo '<pre>';
            print_r($resultado);
            //echo $error;
            echo '</pre>';
        }catch(Exception $e){
            print_r($e);
        }

    }

}
