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
    public function facturacionElectronicaVT()
    {
       $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');
       return view('documentosElectronicos.facturacionVT')->with('companias',$companias);
    }

    public function layoutFacturacion()
    {
        return response()->view("documentosElectronicos.layout.layoutFacturacion")->header('Content-Type', 'text/xml');
    }
    public function layoutFacturacionVT()
    {
        return response()->view("documentosElectronicos.layout.layoutFacturacionVT")->header('Content-Type', 'text/xml');
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
    public function dataFacturacionVT($fechaInicio,$fechaFin,$compania){

        $facturaIFS = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
            ->whereIn('SERIES_ID', ['PR','18'])
            ->whereBetween('INVOICE_DATE', [$fechaInicio,$fechaFin])
            ->where('COMPANY', $compania)
            ->select('INVOICE_NO','INVOICE_DATE','NAME','GROSS_AMOUNT','NET_AMOUNT','SERIES_ID','OBJSTATE')
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
        return response()->view("documentosElectronicos.data.dataFacturacionVT",$data)->header('Content-Type', 'text/xml');
    }
    function cambiarExpresionRegular($cadena){
        return preg_replace('/[^\p{L}\p{N}\&%#\ С$\/=?\}\]\{\[+*~^;:,.-_ͼ\(\)\'\"]/u', '', $cadena);
    }
    public function actualizarIFS(Request $request){
        $compania = $request["COMPANY"];
        $documentos= \DB::connection('ifscmi_int')->table('FE_DOCUMENTOS_ENVIADOS')
            ->where('COMPANIA', $compania)
            ->where('ERROR','OK')
            ->select('TIPO_DOCUMENTO','NO_DOCUMENTO','COMPANIA')
            ->get();
        foreach ($documentos as $documento){
            $FACTURA = \DB::connection('oracle')->table('INSTANT_INVOICE')
                ->where('INVOICE_NO', $documento->no_documento)
                ->where('COMPANY', $compania)
                ->where('SERIES_ID', 'PR')
                ->select('INVOICE_NO','INVOICE_ID','IDENTITY')
                ->get()->first();

            if(isset($FACTURA->invoice_no)){
                $dato = explode('-',$FACTURA->invoice_no);

                $receipt= \DB::connection('invoice')->table('RECEIPT')
                    ->where('SERIAL', $dato[0].$dato[1])
                    ->where('RECEIPT_NUMBER', $dato[2])
                    ->where('RECEIPT_TYPE_ID','1')
                    ->select('RECEIPT_ID')
                    ->get()->first();

                if(isset($receipt->receipt_id)){
                    $autorizacion= \DB::connection('invoice')->table('AUTHORIZATION')
                        ->where('receipt_id',$receipt->receipt_id)
                        ->select('AUTHORIZATION_NUMBER','AUTHORIZATION_DATE')
                        ->get()->first();

                    if(isset($autorizacion->authorization_date)){
                        $facturaElec = \DB::connection('oracle')->table('C_ELECTRONIC_INVOICE_AUTH_TAB')
                            ->where('INVOICE_ID', $FACTURA->invoice_id)
                            ->where('COMPANY', $compania)
                            ->select('ROWSTATE')
                            ->get()->first();
                        if(isset($facturaElec->rowstate)){
                            if($facturaElec->rowstate=='Preliminary'){
                                \DB::connection('oracle')->table('C_ELECTRONIC_INVOICE_AUTH_TAB')
                                    ->where('INVOICE_ID', $FACTURA->invoice_id)
                                    ->where('COMPANY', $compania)
                                    ->update(['C_SEND_AUTH_DATE' => $autorizacion->authorization_date,'C_AUTH_ID_SRI'=>$autorizacion->authorization_number,'USERID'=>'RVASCONEZ','C_RECEIVE_AUTH_DATE'=>$autorizacion->authorization_date,'C_ACCESS_KEY'=>$autorizacion->authorization_number,'C_EMISSION_TYPE'=>1,'ROWSTATE'=>'Authorized']);
                            }
                        }
                    }
                }

            }
        }

    }
    public function enviarFacturaVTTandi(Request $request){
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

        $FACTURA = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
            ->where('INVOICE_NO', $INVOICE_ID)
            ->where('COMPANY', $request["COMPANY"])
            ->select('INVOICE_NO','INVOICE_DATE','IDENTITY','NAME','NET_AMOUNT','INVOICE_ID','GROSS_AMOUNT',\DB::connection('oracle')->raw('Payment_Term_API.Get_Description(COMPANY,PAY_TERM_ID) as PAY_TERM_DESCRIPTION'))
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

        $contactoCliente = \DB::connection('oracle')->table('CUSTOMER_INFO_CONTACT')
            ->where('CUSTOMER_ID', $FACTURA->identity)
            ->select(\DB::connection('oracle')->raw('PERSON_INFO_ADDRESS_API.Get_E_Mail(person_id,CONTACT_ADDRESS) as correoCliente'))
            ->get()->first();

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

        $IMPUESTOS = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_ITEM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->whereNotNull('VAT_CODE')
            ->select(\DB::connection('oracle')->raw('SUM(VAT_CURR_AMOUNT) AS IMPUESTO'),\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) AS BASE_IMPONIBLE,VAT_CODE'))
            ->groupBy('VAT_CODE')
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

            $a_totalConImpuestos["totalImpuesto"][$i] = array('codigo' => $TABLA16->codigo,
                'codigoPorcentaje'  => $TABLA16->codigo_porcentaje,
                'tarifa'=>$porcentaje->fee_rate,
                'baseImponible'  =>  round($IMPUESTO->base_imponible,2),
                'valor'  => round($IMPUESTO->impuesto,2)
            );
            $i++;
        }

        $a_pago= array();
        $dias= floatval($FACTURA->pay_term_description);
        $paymentTerms = \DB::connection('oracle')->table('C_INVOICE_PAYMENT_FORM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->where('COMPANY', $COMPANY->company)
            ->select('id_payment_form')
            ->get()->first();

        $a_pago["pago"][0] = array('formaPago' =>$paymentTerms->id_payment_form,
            'total'  => $FACTURA->gross_amount,
            'plazo'  => $dias,
            'unidadTiempo'  => "dias"
        );

        $a_detalle = array();
        $i=0;
        $ITEMS = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_ITEM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->where('COMPANY', $COMPANY->company)
            ->whereNotNull('INVOICED_QTY')
            ->whereNotNull('SALE_UNIT_PRICE')
            ->select('VAT_CODE','CATALOG_NO','DESCRIPTION','INVOICED_QTY','DISCOUNT','SALE_UNIT_PRICE','NET_CURR_AMOUNT','VAT_CURR_AMOUNT')
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
            $DESCUENTO_ITEM = $ITEM->invoiced_qty * ($ITEM->sale_unit_price*($ITEM->discount/100));

            $a_detalle["detalle"][$i] = array('codigoPrincipal' => $ITEM->catalog_no,
                'descripcion'  => $this->cambiarExpresionRegular($ITEM->description),
                'cantidad'  =>  $ITEM->invoiced_qty,
                'precioUnitario'  => abs(round($ITEM->sale_unit_price,2)),
                'descuento'  => abs(round($DESCUENTO_ITEM,2)),
                'precioTotalSinImpuesto'  => abs(round($ITEM->net_curr_amount,2)),
                'impuestos'  => array('impuesto' => array(
                    'codigo'=>$TABLA16->codigo,
                    'codigoPorcentaje'=>$TABLA16->codigo_porcentaje,
                    'tarifa'=>$porcentaje->fee_rate,
                    'baseImponible'=>abs(round($ITEM->net_curr_amount,2)),
                    'valor'=>abs(round($ITEM->vat_curr_amount,2))
                ))
            );
            $i++;

        }
        $errorFact="";
        if(isset($CUSTOMER->address) &&  isset($contactoCliente->correocliente)) {
            if ($CUSTOMER->address == "" || $contactoCliente->correocliente == "") {
                $errorFact = "ERROR";
            }
        }else{
            $errorFact = "ERROR";

        }
        if($errorFact!=""){
            echo $errorFact;
            die();
        }
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
            'tipoNegociable' => array('correo'=>$contactoCliente->correocliente),
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
           ->where('COMPANY', $request["COMPANY"])
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

        $contactoCliente = \DB::connection('oracle')->table('CUSTOMER_INFO_CONTACT')
            ->where('CUSTOMER_ID', $FACTURA->identity)
            ->select(\DB::connection('oracle')->raw('PERSON_INFO_ADDRESS_API.Get_E_Mail(person_id,CONTACT_ADDRESS) as correoCliente'))
            ->get()->first();

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

            $IMPUESTOS = \DB::connection('oracle')->table('INSTANT_INVOICE_ITEM')
            ->where('INVOICE_ID', $FACTURA->invoice_id)
            ->where('COMPANY', $COMPANY->company)
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



                $a_totalConImpuestos["totalImpuesto"][$i] = array('codigo' => $TABLA16->codigo,
                    'codigoPorcentaje'  => $TABLA16->codigo_porcentaje,
                    'tarifa'=>$porcentaje->fee_rate,
                    'baseImponible'  =>  round($IMPUESTO->base_imponible,2),
                    'valor'  => round($IMPUESTO->impuesto,2)
                );
                $i++;
            }

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
            ->where('COMPANY', $COMPANY->company)
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
                    'codigoPorcentaje'=>$TABLA16->codigo_porcentaje,
                    'tarifa'=>$ITEM->vat_percent,
                    'baseImponible'=>abs(round($ITEM->net_curr_amount,2)),
                    'valor'=>abs(round($ITEM->vat_curr_amount,2))
                ))
            );
            $i++;

        }
        $errorFact="";
        if(isset($CUSTOMER->address) &&  isset($contactoCliente->correocliente)) {
            if ($CUSTOMER->address == "" || $contactoCliente->correocliente == "") {
                $errorFact = "ERROR";
            }
        }else{
            $errorFact = "ERROR";

        }
        if($errorFact!=""){
            echo $errorFact;
            die();
        }
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
            'tipoNegociable' => array('correo'=>$contactoCliente->correocliente),
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
