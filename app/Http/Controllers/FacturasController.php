<?php

namespace App\Http\Controllers;

use App\Documento_recibido;
use App\Xml;
use Illuminate\Http\Request;

use App\Company_tab;
use Illuminate\Support\Carbon;



class FacturasController extends Controller
{
    public function subir()
    {
        $mensaje="";
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

        return view('facturas.index')->with('mensaje',$mensaje)->with('companias',$companias)->with('compania','EC01');
    }
    public function validarFacturas()
    {
        $mensaje="";
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

        return view('facturas.validar')->with('companias',$companias)->with('mensaje',$mensaje)->with('compania','EC01');;
    }
    public function addDocument($compania,$comprobante,$serie_comprobante,$ruc_emisor,$razon_social_emisor,$fecha_emision,$fecha_autorizacion,$tipo_emision,$documento_relacionado,$identificacion_receptor,$clave_acceso,$numero_autorizador,$importe_total,$mensaje,$voucher_no,$invoice_no,$anio,$mes){

        $fecha_emision = Carbon::createFromFormat('d/m/Y',$fecha_emision)->format('Y-m-d');
        $fecha_autorizacion = Carbon::createFromFormat('d/m/Y  H:i:s',$fecha_autorizacion)->format('Y-m-d H:i:s');
         $documento = Documento_recibido::where("comprobante",$comprobante)->where("serie_comprobante",$serie_comprobante)
                    ->where("ruc_emisor",$ruc_emisor)->where("company",$compania)->get()->first();

        if(isset($documento->serie_comprobante)){
            $documento->mensaje = $mensaje;
            $documento->voucher_no = $voucher_no;
            $documento->invoice_no = $invoice_no;
            $documento->factura_existe = 1;
            $documento->anio =  $anio;
            $documento->mes = $mes;
            $documento->save();
        }else {
            Documento_recibido::insert(
                [
                    'company' => $compania,
                    'comprobante' => $comprobante,
                    'serie_comprobante' => $serie_comprobante,
                    'ruc_emisor' => $ruc_emisor,
                    'razon_social_emisor' => $razon_social_emisor,
                    'fecha_emision' => $fecha_emision,
                    'fecha_autorizacion' => $fecha_autorizacion,
                    'tipo_emision' => $tipo_emision,
                    'documento_relacionado' => $documento_relacionado,
                    'identificacion_receptor' => $identificacion_receptor,
                    'clave_acceso' => $clave_acceso,
                    'numero_autorizador' => $numero_autorizador,
                    'importe_total' => $importe_total,
                    'mensaje' => $mensaje,
                    'voucher_no' => $voucher_no,
                    'anio' => $anio,
                    'mes' => $mes,
                    'invoice_no' => $invoice_no
                ]

            );
        }
    }
    public function validarArchivo(Request $request)
    {

        Documento_recibido::where("anio",$request["anio"])->where("mes",$request["mes"])->where("company",$request["compania"])->update(['factura_existe' => 0]);
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');
        $facturas= [];
        $comprobantes= [];
        $notasCredito= [];
        $compania = $request["compania"];
        $companiaTab = \DB::connection('oracle')->table('COMPANY_INVOICE_INFO')
            ->where('COMPANY', $compania)
            ->select('VAT_NO')
            ->get()->first();

        $VAT_NO = $companiaTab->vat_no;

        if($request->file('file')) {
            $file = $request->file('file');
            if($file->getClientMimeType()=="text/plain"){
                $mensaje=array();
                $archivo = fopen($file->getRealPath(), "r");
                $i=-1;
                $c=-1;
                $nc=-1;
                $esFactura=0;
                while(!feof($archivo)) {
                    $linea = fgets($archivo);
                    $fields = explode("\t", $linea);
                    $fields[0] = utf8_encode($fields[0]);
                    $validacion = 0;
                    if(isset($fields[8]) &&  $fields[0]=="Factura" && $validacion==0){
                        $RUC = utf8_encode(trim($fields[8]));
                        if($VAT_NO==$RUC) {
                            $validacion = 1;
                        }else{
                            $mensaje = 'El archivo no contiene información de la compañía '.$compania;
                            return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias)->with('compania',$compania);
                        }
                    }
                    switch ($fields[0]) {
                        case "COMPROBANTE":
                            $esFactura=0;
                            break;
                         case "Factura":
                             $esFactura=1;
                             $facturaIFS = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                                 ->where('COMPANY', $compania)
                                 ->where('IDENTITY', $fields[2])
                                 ->where('INVOICE_NO', $fields[1])
                                 ->where('SERIES_ID','!=','04')
                                 ->where('SERIES_ID','!=','05')
                                 ->select('INVOICE_NO','SERIES_ID','VOUCHER_NO_REF')
                                 ->get()->first();

                             $i++;
                             $facturas[$i]["comprobante"] = "Factura";
                             $facturas[$i]["serie_comprobante"] = $fields[1];
                             $facturas[$i]["ruc_emisor"] =  $fields[2];
                             $facturas[$i]["razon_social_emisor"] =  $this->limpiaCadena(utf8_encode($fields[3]));
                             $facturas[$i]["fecha_emision"] = $fields[4];
                             $facturas[$i]["fecha_autorizacion"] = $fields[5];
                             $facturas[$i]["tipo_emision"] = $fields[6];
                             $facturas[$i]["documento_relacionado"] = "";
                             $facturas[$i]["identificacion_receptor"] = $fields[8];
                             $facturas[$i]["clave_acceso"] = $fields[9];
                             $facturas[$i]["numero_autorizador"] = $fields[10];
                             if(isset($facturaIFS->invoice_no)){
                                 $facturas[$i]["mensaje"] ="SI";
                                 $facturas[$i]["invoice_no"] = $facturaIFS->invoice_no;
                                 $facturas[$i]["voucher"] = $facturaIFS->voucher_no_ref;
                             }else{
                                 $facturas[$i]["mensaje"] ="NO";
                                 $facturas[$i]["invoice_no"] = "";
                                 $facturas[$i]["voucher"] = "";
                             }
                             $facturas[$i]["importe_total"] = 0;
                            break;
                        case "Notas de Crédito":
                            $esFactura=0;
                            $notaCredito = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                                ->where('COMPANY', $compania)
                                ->where('IDENTITY', $fields[2])
                                ->where('INVOICE_NO', $fields[1])
                                ->where('SERIES_ID','=','04')
                                ->select('INVOICE_NO','SERIES_ID','VOUCHER_NO_REF')
                                ->get()->first();
                            $nc++;
                            $notasCredito[$nc]["comprobante"] = $fields[0];
                            $notasCredito[$nc]["serie_comprobante"] = $fields[1];
                            $notasCredito[$nc]["ruc_emisor"] =  $fields[2];
                            $notasCredito[$nc]["razon_social_emisor"] =  $this->limpiaCadena(utf8_encode($fields[3]));
                            $notasCredito[$nc]["fecha_emision"] = $fields[4];
                            $notasCredito[$nc]["fecha_autorizacion"] = $fields[5];
                            $notasCredito[$nc]["tipo_emision"] = $fields[6];
                            $notasCredito[$nc]["documento_relacionado"] =$fields[7];
                            $notasCredito[$nc]["identificacion_receptor"] = $fields[8];
                            $notasCredito[$nc]["clave_acceso"] = $fields[9];
                            $notasCredito[$nc]["numero_autorizador"] = $fields[10];
                            if(isset($notaCredito->invoice_no)){
                                $notasCredito[$nc]["mensaje"] ="SI";
                                $notasCredito[$nc]["invoice_no"] = $notaCredito->invoice_no;
                                $notasCredito[$nc]["voucher"] = $notaCredito->voucher_no_ref;
                            }else{
                                $notasCredito[$nc]["mensaje"] ="NO";
                                $notasCredito[$nc]["invoice_no"] = "";
                                $notasCredito[$nc]["voucher"] = "";
                            }
                            $this->addDocument($compania,$fields[0],$fields[1],$fields[2],$this->limpiaCadena(utf8_encode($fields[3])),$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],0, $notasCredito[$nc]["mensaje"],$notasCredito[$nc]["voucher"], $notasCredito[$nc]["invoice_no"],$request["anio"],$request["mes"]);

                            break;
                        case "Notas de Débito":
                            $esFactura=0;
                            break;
                        case "Comprobante de Retención":
                            $esFactura=0;
                            $comprobante = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
                                ->where('COMPANY', $compania)
                                ->where('IDENTITY', $fields[2])
                                ->where('LEDGER_ITEM_ID', $fields[1])
                                ->where('LEDGER_ITEM_SERIES_ID', 'LIKE','07%')
                                ->where('STATE', '!=','Cancelado')
                                ->select('VOUCHER_NO','ADDRESS_DESC')
                                ->get()->first();
                            $c++;
                            $comprobantes[$c]["comprobante"] = $fields[0];
                            $comprobantes[$c]["serie_comprobante"] = $fields[1];
                            $comprobantes[$c]["ruc_emisor"] =  $fields[2];
                            $comprobantes[$c]["razon_social_emisor"] =  $this->limpiaCadena(utf8_encode($fields[3]));
                            $comprobantes[$c]["fecha_emision"] = $fields[4];
                            $comprobantes[$c]["fecha_autorizacion"] = $fields[5];
                            $comprobantes[$c]["tipo_emision"] = $fields[6];
                            $comprobantes[$c]["documento_relacionado"] = $fields[7];
                            $comprobantes[$c]["identificacion_receptor"] = $fields[8];
                            $comprobantes[$c]["clave_acceso"] = $fields[9];
                            $comprobantes[$c]["numero_autorizador"] = $fields[10];
                            if(isset($comprobante->voucher_no)){
                                $comprobantes[$c]["mensaje"] ="SI";
                                $comprobantes[$c]["voucher"] = $comprobante->voucher_no;
                            }else{
                                $comprobantes[$c]["mensaje"] ="NO";
                                $comprobantes[$c]["voucher"] = "";
                            }
                            $this->addDocument($compania,$fields[0],$fields[1],$fields[2],$this->limpiaCadena(utf8_encode($fields[3])),$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],0,$comprobantes[$c]["mensaje"],$comprobantes[$c]["voucher"],"",$request["anio"],$request["mes"]);
                            break;
                        default:
                            if($esFactura==1 && floatval($fields[0])>0) {
                                $facturas[$i]["importe_total"] = floatval($fields[0]);
                                $esFactura=0;
                            }
                    }
                }
                //dd($facturas);
                foreach ($facturas as $factura){
                 //   dd($factura);
                   $this->addDocument($compania,$factura["comprobante"],$factura["serie_comprobante"],$factura["ruc_emisor"],$this->limpiaCadena(utf8_encode($factura["razon_social_emisor"])),$factura["fecha_emision"],$factura["fecha_autorizacion"],$factura["tipo_emision"],"",$factura["identificacion_receptor"],$factura["clave_acceso"],$factura["numero_autorizador"],$factura["importe_total"],$factura["mensaje"],$factura["voucher"],$factura["invoice_no"],$request["anio"],$request["mes"]);
                }
                $mensaje = "El archivo ha sido subido exitosamente";
                return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias)->with('compania',$compania);
            }else{
                $mensaje = "El archivo debe ser .txt";
                return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias)->with('compania',$compania);
            }

        }
        $mensaje = "El archivo debe ser .txt";
        return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias)->with('compania',$compania);
    }
    public function limpiaCadena($cadena) {
        //return (preg_replace('[^ A-Za-z0-9_-ñÑ]', '', $cadena));
        return preg_replace("[^A-Za-z0-9]", "", $cadena);

    }

    public function enviarDocumentoIFS(Request $request)
    {
        $documento = Documento_recibido::find($request["id"]);
        $invoice = \DB::connection('oracle')->table('INVOICE_TAB')
            ->where('COMPANY', strval($documento->company))
            ->where('IDENTITY', strval($documento->ruc_emisor))
            ->where('INVOICE_NO', strval($documento->serie_comprobante))
            ->select('INVOICE_TAB.COMPANY')
            ->get();

        if(!isset($invoice->company)){
            $invoiceID = \DB::connection('oracle')->table('dual')
                ->select(\DB::connection('oracle')->raw('INVOICE_ID_SEQ.nextval AS VALOR'))
                ->get()->first();

            \DB::connection('oracle')->table('INVOICE_TAB')->insert(
                [
                 'COMPANY' => $documento->company,
                 //'IDENTITY' =>'1709036949',
                 'IDENTITY' => $documento->ruc_emisor,
                 'PARTY_TYPE' => 'SUPPLIER',
                 'INVOICE_ID' => $invoiceID->valor,
                 'ROWVERSION' => '11',
                 'ROWSTATE' => 'Preliminary',
                 'SERIES_ID' => '01',
                 'INVOICE_NO' => $documento->serie_comprobante,
                 'HEAD_DATA' => '',
                 'CREATOR' => 'MAN_SUPP_INVOICE_API',
                 'INVOICE_DATE' => $documento->fecha_emision,
                 'DUE_DATE' => $documento->fecha_emision,
                 'CASH' => 'FALSE',
                 'COLLECT' => 'FALSE',
                 'INT_ALLOWED' => 'TRUE',
                 'INVOICE_TYPE' => 'FAC_LOCAL',
                 'PAY_TERM_ID' => '1IEM',
                 'AFF_BASE_LEDG_POST' => 'TRUE',
                 'AFF_LINE_POST' => 'FALSE',
                 'DELIVERY_DATE' => $documento->fecha_emision,
                 'ARRIVAL_DATE' => $documento->fecha_emision,
                 'DELIVERY_ADDRESS_ID' => '1',
                 'CREATION_DATE' => date('Y-m-d'),
                 'PRELIM_CODE' => '*',
                 'CURR_RATE' => '1',
                 'DIV_FACTOR' => '1',
                 'PL_PAY_DATE' => $documento->fecha_emision,
                 'NET_CURR_AMOUNT' => $documento->importe_total,
                 'VAT_CURR_AMOUNT' => 0,
                 'NET_DOM_AMOUNT' =>  $documento->importe_total,
                 'VAT_DOM_AMOUNT' => 0,
                 'CURRENCY' => 'USD',
                 'SENT' => 'FALSE',
                 'MULTI_COMPANY_INVOICE' => 'FALSE',
                 'ROWTYPE' => 'ManSuppInvoice',
                 'TRANSFER_IDENTITY' => '*',
                 'INVOICE_VERSION' => '1',
                 'GROSS_UP' => 'FALSE',
                 'PAY_TERM_BASE_DATE' => $documento->fecha_emision,
                 'ADV_INV' => 'FALSE',
                 'INVOICE_RECIPIENT' => 'IFSCMI',
                 'PROPOSAL_EXIST' => 'FALSE',
                 'TAX_CURR_RATE' => '1',
                 'VOUCHER_TEXT' => $documento->ruc_emisor,
                 'OLD_ADV_INV' => 'FALSE',
                 'C_TERM_BILL' => $documento->fecha_emision,
                 'C_INVOICE_TYPE' => 'SERVICES',
                 'C_CASH_ACCOUNT' => 'CTA_PICHINCHA',
                 'C_SUSTENANCE_ID' => '01',
                 'C_AUTH_ID_SRI' => $documento->clave_acceso,
                 'C_AUTHOR_PRINTED' => '9999',
                 'ID_PAYMENT_TYPE' => '01',
                 'C_ELECTRONIC_INVOICE' => 'TRUE'
                ]
            );
        }else{
            echo "ERROR LA FACTURA YA EXISTE";
        }
    }
    public function subirXML(Request $request)
    {
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');
        $compania = $request["compania"];
        if($request->file('image')){
            $file = $request->file('image');
            $carpeta = public_path()."/".time();
            mkdir($carpeta, 0700);
            if($file->getClientMimeType()=="application/x-zip-compressed"){
                $zip = new \ZipArchive;
                if ($zip->open($file) === TRUE) {
                    $zip->extractTo($carpeta);
                    $zip->close();
                    $mensaje="";
                    if ($gestor = opendir($carpeta)) {
                        while (false !== ($archivo = readdir($gestor))) {
                            if($archivo!="." && $archivo!=".." && $archivo!="--" ){
                                $file = $archivo;
                                $archivo = $carpeta."/".$archivo;
                                $xml2 = file_get_contents($archivo);
                                $xml2 = simplexml_load_string($xml2);

                                if(isset($xml2->comprobante)) {
                                    $xml = simplexml_load_string($xml2->comprobante);
                                }else{
                                    if(isset($xml2->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante)) {
                                        $xml = simplexml_load_string($xml2->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante);
                                    }else{
                                        if(isset($xml2->autorizaciones->autorizacion->comprobante)) {
                                            $xml = simplexml_load_string($xml2->autorizaciones->autorizacion->comprobante);
                                        }else {
                                            $xml = false;
                                        }
                                    }
                                    /*try {
                                        $xml2 = simplexml_load_file($archivo);
                                        $ns = $xml2->getNamespaces(true);
                                        dd($archivo);
                                        $xml2->registerXPathNamespace('ns2', $ns['ns2']);
                                        $data = $xml2->xpath('//ns2:autorizacionComprobanteResponse');

                                        libxml_use_internal_errors(true);
                                        $xml = simplexml_load_string($data[0]->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante);

                                    }catch(Exception $e){
                                        //return view('facturas.index')->with('mensaje',$e."<br>El archivo".$archivo." no se encuentra creado correctamente");
                                        $mensaje .= $archivo." mal formacion XML";
                                    }*/
                                }

                                if($xml===false){
                                    $mensaje .= $file."\n";
                                }else {
                                    $ruc = $xml->infoTributaria->ruc;
                                    $claveAcceso = $xml->infoTributaria->claveAcceso;
                                    $estab = $xml->infoTributaria->estab;
                                    $ptoEmi = $xml->infoTributaria->ptoEmi;
                                    $secuencial = $xml->infoTributaria->secuencial;
                                    $fechaEmision = $xml->infoFactura->fechaEmision;
                                    $invoiceID = \DB::connection('oracle')->table('dual')
                                        ->select(\DB::connection('oracle')->raw('INVOICE_ID_SEQ.nextval AS VALOR'))
                                        ->get()->first();

                                    $noFactura = $estab . "-" . $ptoEmi . "-" . $secuencial;
                                    $fecha = date("d/m/Y");
                                    $invoices = \DB::connection('oracle')->table('INVOICE_TAB')
                                        ->where('IDENTITY', strval($ruc))
                                        ->where('INVOICE_NO', strval($noFactura))
                                        ->where('ROWSTATE', '!=', 'Cancelled')
                                        ->select('INVOICE_TAB.COMPANY')
                                        ->get();


                                    $destino = public_path() . "/facturasProveedores/" . $file;
                                    copy($archivo, $destino);

                                    if ($invoices->count() > 0) {
                                        //$xml = Xml::where('')
                                        $xmlTable = Xml::where('claveAcceso', $xml->infoTributaria->claveAcceso)->get()->first();
                                        if (!isset($xmlTable->id)) {
                                            Xml::insert(
                                                [
                                                    'ambiente' => $xml->infoTributaria->ambiente,
                                                    'tipoEmision' => $xml->infoTributaria->tipoEmision,
                                                    'razonSocial' => $xml->infoTributaria->razonSocial,
                                                    'nombreComercial' => $xml->infoTributaria->nombreComercial,
                                                    'ruc' => $xml->infoTributaria->ruc,
                                                    'claveAcceso' => $xml->infoTributaria->claveAcceso,
                                                    'codDoc' => $xml->infoTributaria->codDoc,
                                                    'estab' => $xml->infoTributaria->estab,
                                                    'ptoEmi' => $xml->infoTributaria->ptoEmi,
                                                    'secuencial' => $xml->infoTributaria->secuencial,
                                                    'dirMatriz' => $xml->infoTributaria->dirMatriz,
                                                    'fechaEmision' => date('Y-m-d', strtotime($xml->infoFactura->fechaEmision)),
                                                    'dirEstablecimiento' => $xml->infoFactura->dirEstablecimiento,
                                                    'obligadoContabilidad' => $xml->infoFactura->obligadoContabilidad,
                                                    'tipoIdentificacionComprador' => $xml->infoFactura->tipoIdentificacionComprador,
                                                    'razonSocialComprador' => $xml->infoFactura->razonSocialComprador,
                                                    'identificacionComprador' => $xml->infoFactura->identificacionComprador,
                                                    'direccionComprador' => $xml->infoFactura->direccionComprador,
                                                    'totalSinImpuestos' => $xml->infoFactura->totalSinImpuestos,
                                                    'totalDescuento' => $xml->infoFactura->totalDescuento,
                                                    'propina' => $xml->infoFactura->propina,
                                                    'importeTotal' => $xml->infoFactura->importeTotal,
                                                    'enviado_ifs' => "NO",
                                                    'fecha_envio' => date('Y-m-d'),
                                                    'path' => $file,
                                                    'company' => $compania
                                                ]

                                            );
                                        } else {
                                            Xml::where('claveAcceso', $xml->infoTributaria->claveAcceso)->update(
                                                [
                                                    'ambiente' => $xml->infoTributaria->ambiente,
                                                    'tipoEmision' => $xml->infoTributaria->tipoEmision,
                                                    'razonSocial' => $xml->infoTributaria->razonSocial,
                                                    'nombreComercial' => $xml->infoTributaria->nombreComercial,
                                                    'ruc' => $xml->infoTributaria->ruc,
                                                    'claveAcceso' => $xml->infoTributaria->claveAcceso,
                                                    'codDoc' => $xml->infoTributaria->codDoc,
                                                    'estab' => $xml->infoTributaria->estab,
                                                    'ptoEmi' => $xml->infoTributaria->ptoEmi,
                                                    'secuencial' => $xml->infoTributaria->secuencial,
                                                    'dirMatriz' => $xml->infoTributaria->dirMatriz,
                                                    'fechaEmision' => date('Y-m-d', strtotime($xml->infoFactura->fechaEmision)),
                                                    'dirEstablecimiento' => $xml->infoFactura->dirEstablecimiento,
                                                    'obligadoContabilidad' => $xml->infoFactura->obligadoContabilidad,
                                                    'tipoIdentificacionComprador' => $xml->infoFactura->tipoIdentificacionComprador,
                                                    'razonSocialComprador' => $xml->infoFactura->razonSocialComprador,
                                                    'identificacionComprador' => $xml->infoFactura->identificacionComprador,
                                                    'direccionComprador' => $xml->infoFactura->direccionComprador,
                                                    'totalSinImpuestos' => $xml->infoFactura->totalSinImpuestos,
                                                    'totalDescuento' => $xml->infoFactura->totalDescuento,
                                                    'propina' => $xml->infoFactura->propina,
                                                    'importeTotal' => $xml->infoFactura->importeTotal,
                                                    'enviado_ifs' => "NO",
                                                    'fecha_envio' => date('Y-m-d'),
                                                    'path' => $file,
                                                    'company' => $compania
                                                ]

                                            );

                                        }

                                    } else {
                                        $totalSinImpuestos = floatval($xml->infoFactura->totalSinImpuestos);
                                        $importeTotal = floatval($xml->infoFactura->importeTotal);
                                        $totalImpuestos = $importeTotal - $totalSinImpuestos;
                                        $xmlTable = Xml::where('claveAcceso', $xml->infoTributaria->claveAcceso)->get()->first();
                                        if (!isset($xmlTable->id)) {
                                            Xml::insert([
                                                'ambiente' => $xml->infoTributaria->ambiente,
                                                'tipoEmision' => $xml->infoTributaria->tipoEmision,
                                                'razonSocial' => $xml->infoTributaria->razonSocial,
                                                'nombreComercial' => $xml->infoTributaria->nombreComercial,
                                                'ruc' => $xml->infoTributaria->ruc,
                                                'claveAcceso' => $xml->infoTributaria->claveAcceso,
                                                'codDoc' => $xml->infoTributaria->codDoc,
                                                'estab' => $xml->infoTributaria->estab,
                                                'ptoEmi' => $xml->infoTributaria->ptoEmi,
                                                'secuencial' => $xml->infoTributaria->secuencial,
                                                'dirMatriz' => $xml->infoTributaria->dirMatriz,
                                                'fechaEmision' => date('Y-m-d', strtotime($xml->infoFactura->fechaEmision)),
                                                'dirEstablecimiento' => $xml->infoFactura->dirEstablecimiento,
                                                'obligadoContabilidad' => $xml->infoFactura->obligadoContabilidad,
                                                'tipoIdentificacionComprador' => $xml->infoFactura->tipoIdentificacionComprador,
                                                'razonSocialComprador' => $xml->infoFactura->razonSocialComprador,
                                                'identificacionComprador' => $xml->infoFactura->identificacionComprador,
                                                'direccionComprador' => $xml->infoFactura->direccionComprador,
                                                'totalSinImpuestos' => $xml->infoFactura->totalSinImpuestos,
                                                'totalDescuento' => $xml->infoFactura->totalDescuento,
                                                'propina' => $xml->infoFactura->propina,
                                                'importeTotal' => $xml->infoFactura->importeTotal,
                                                'enviado_ifs' => "SI",
                                                'fecha_envio' => date('Y-m-d'),
                                                'path' => $file,
                                                'company' => $compania

                                            ]);
                                        } else {
                                            Xml::where('claveAcceso', $xml->infoTributaria->claveAcceso)->update([
                                                'ambiente' => $xml->infoTributaria->ambiente,
                                                'tipoEmision' => $xml->infoTributaria->tipoEmision,
                                                'razonSocial' => $xml->infoTributaria->razonSocial,
                                                'nombreComercial' => $xml->infoTributaria->nombreComercial,
                                                'ruc' => $xml->infoTributaria->ruc,
                                                'claveAcceso' => $xml->infoTributaria->claveAcceso,
                                                'codDoc' => $xml->infoTributaria->codDoc,
                                                'estab' => $xml->infoTributaria->estab,
                                                'ptoEmi' => $xml->infoTributaria->ptoEmi,
                                                'secuencial' => $xml->infoTributaria->secuencial,
                                                'dirMatriz' => $xml->infoTributaria->dirMatriz,
                                                'fechaEmision' => date('Y-m-d', strtotime($xml->infoFactura->fechaEmision)),
                                                'dirEstablecimiento' => $xml->infoFactura->dirEstablecimiento,
                                                'obligadoContabilidad' => $xml->infoFactura->obligadoContabilidad,
                                                'tipoIdentificacionComprador' => $xml->infoFactura->tipoIdentificacionComprador,
                                                'razonSocialComprador' => $xml->infoFactura->razonSocialComprador,
                                                'identificacionComprador' => $xml->infoFactura->identificacionComprador,
                                                'direccionComprador' => $xml->infoFactura->direccionComprador,
                                                'totalSinImpuestos' => $xml->infoFactura->totalSinImpuestos,
                                                'totalDescuento' => $xml->infoFactura->totalDescuento,
                                                'propina' => $xml->infoFactura->propina,
                                                'importeTotal' => $xml->infoFactura->importeTotal,
                                                'enviado_ifs' => "SI",
                                                'fecha_envio' => date('Y-m-d'),
                                                'path' => $file,
                                                'company' => $compania
                                            ]);
                                        }
                                    }

                                }
                                unlink($archivo);
                            }
                        }
                        closedir($gestor);
                    }
                    if($mensaje==""){
                        return view('facturas.index')->with('mensaje',"El archivo ha sido subido exitosamente")->with('companias',$companias);
                    }else{
                        return view('facturas.index')->with('mensaje',"Los siguientes archivo no pudieron ser procesados: \n".$mensaje)->with('companias',$companias);
                    }

                }else{
                    return view('facturas.index')->with('mensaje',"El archivo debe ser un .zip, No es posible subir")->with('companias',$companias);
                }
            }else{
                return view('facturas.index')->with('mensaje',"El archivo debe ser un .zip, No es posible subir")->with('companias',$companias);
            }
            rmdir($carpeta);
        }
    }
    public function layoutXML(){
        return response()->view("facturas.layoutXML")->header('Content-Type', 'text/xml');
    }
    public function dataXML(){
        $datos = Xml::all();
        $data = [   "datos"=>$datos];
        return response()->view("facturas.dataXML",$data)->header('Content-Type', 'text/xml');
    }
    public function layoutDocumentos(){
        return response()->view("facturas.layoutDocumentos")->header('Content-Type', 'text/xml');
    }
    public function dataDocumentos($compania){
        $datos = Documento_recibido::where("company",$compania)->get();

        foreach($datos as $dato){

            if($dato->voucher_no_ref==""){
                switch ($dato->comprobante) {
                    case "COMPROBANTE":
                        $esFactura=0;
                        break;
                    case "Factura":
                        $esFactura=1;

                        $facturaIFS = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                            ->where('COMPANY', $dato->company)
                            ->where('IDENTITY', $dato->ruc_emisor)
                            ->where('INVOICE_NO', $dato->serie_comprobante)
                            ->where('SERIES_ID','!=','04')
                            ->where('SERIES_ID','!=','05')
                            ->select('INVOICE_NO','SERIES_ID','VOUCHER_NO_REF')
                            ->get()->first();

                        if(isset($facturaIFS->invoice_no)){
                            $dato->mensaje ="SI";
                            $dato->invoice_no = $facturaIFS->invoice_no;
                            $dato->voucher_no = $facturaIFS->voucher_no_ref;
                            $dato->save();

                        }


                        break;
                    case "Notas de Crédito":
                        $esFactura=0;
                        $notaCredito = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                            ->where('COMPANY', $dato->company)
                            ->where('IDENTITY',  $dato->ruc_emisor)
                            ->where('INVOICE_NO',$dato->serie_comprobante)
                            ->where('SERIES_ID','=','04')
                            ->select('INVOICE_NO','SERIES_ID','VOUCHER_NO_REF')
                            ->get()->first();
                        if(isset($notaCredito->invoice_no)){
                            $dato->mensaje ="SI";
                            $dato->invoice_no = $notaCredito->invoice_no;
                            $dato->voucher_no = $notaCredito->voucher_no_ref;
                            $dato->save();
                        }


                        break;
                    case "Notas de Débito":
                        $esFactura=0;
                        break;
                    case "Comprobante de Retención":
                        $esFactura=0;
                        $comprobante = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
                            ->where('COMPANY', $dato->company)
                            ->where('IDENTITY',  $dato->ruc_emisor)
                            ->where('LEDGER_ITEM_ID', $dato->serie_comprobante)
                            ->where('LEDGER_ITEM_SERIES_ID', 'LIKE','07%')
                            ->where('STATE', '!=','Cancelado')
                            ->select('VOUCHER_NO','ADDRESS_DESC')
                            ->get()->first();
                        if(isset($comprobante->voucher_no)){
                            $dato->mensaje ="SI";
                            $dato->voucher_no = $comprobante->voucher_no;
                            $dato->save();
                        }
                        break;
                }

            }
        }
        $datos = Documento_recibido::where("company",$compania)->get();
        $data = ["documentos"=>$datos];

        return response()->view("facturas.dataDocumentos",$data)->header('Content-Type', 'text/xml');
    }
}
