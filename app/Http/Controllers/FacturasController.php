<?php

namespace App\Http\Controllers;

use App\Xml;
use Illuminate\Http\Request;

use App\Company_tab;


class FacturasController extends Controller
{
    public function subir()
    {
        $mensaje="";
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

        return view('facturas.index')->with('mensaje',$mensaje)->with('companias',$companias);
    }
    public function validarFacturas()
    {
        $mensaje=[];
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

        return view('facturas.validar')->with('companias',$companias)->with('mensaje',$mensaje);
    }
    public function validarArchivo(Request $request)
    {
        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

        $compania = $request["compania"];
        if($request->file('file')) {
            $file = $request->file('file');
            if($file->getClientMimeType()=="text/plain"){
                $mensaje=array();
                $archivo = fopen($file->getRealPath(), "r");
                while(!feof($archivo)) {
                    $linea = fgets($archivo);
                    $fields = explode("\t", $linea);
                        if($fields[0]=="Factura"){
                            $factura = $fields[1];
                            $RUCproveedor = $fields[2];
                            $proveedor = $fields[3];

                            $facturaIFS = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                                ->where('COMPANY', $compania)
                                ->where('IDENTITY', $RUCproveedor)
                                ->where('INVOICE_NO', $factura)
                                ->select('INVOICE_NO','SERIES_ID')
                                ->get()->first();
                            if(!isset($facturaIFS->invoice_no)){
                             //   echo $proveedor.$factura;
//                                $mensaje[] = "La factura ".$factura. " del Proveedor ".$RUCproveedor."--".$this->limpiaCadena(strval($proveedor))." NO EXISTE EN EL SISTEMA";
                             $mensaje[] = "La factura ".$factura. " del Proveedor ".$RUCproveedor." NO EXISTE EN EL SISTEMA";
                            }
                        }
                }

                return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias);
            }else{
                $mensaje[] = "El archivo debe ser .txt";
                return view('facturas.validar')->with('mensaje',$mensaje)->with('companias',$companias);
            }
        }
    }
    public function limpiaCadena($cadena) {
        return (preg_replace('[^ A-Za-z0-9_-ñÑ]', '', $cadena));
    }
    public function subirXML(Request $request)
    {
        /*$prueba = \DB::connection('clon')->table('C_VOUCHER_RETENTION_LINE')
            ->where('TAX_CODE', '729A')
            ->select('RETENTION_VALUE')
            ->get()->first();
*/
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

                                        \DB::connection('oracle')->insert('insert into INVOICE_TAB (COMPANY,IDENTITY,PARTY_TYPE, INVOICE_ID, ROWVERSION, ROWSTATE, SERIES_ID, INVOICE_NO, CREATOR, INVOICE_DATE,DUE_DATE, CASH, COLLECT, INT_ALLOWED, INVOICE_TYPE, PAY_TERM_ID, AFF_BASE_LEDG_POST, AFF_LINE_POST, DELIVERY_DATE, ARRIVAL_DATE, CREATION_DATE, CURR_RATE, DIV_FACTOR, INVOICE_VERSION, GROSS_UP, PAY_TERM_BASE_DATE,C_AUTH_ID_SRI,NET_CURR_AMOUNT,VAT_CURR_AMOUNT)
                                        values (\''.$compania.'\', \'' . $ruc . '\',\'SUPPLIER\', \'' . $invoiceID->valor . '\', \'1\', \'Preliminary\', \'01\',\'' . $noFactura . '\', \'MAN_SUPP_INVOICE_API\',TO_DATE(\'' . $fechaEmision . '\', \'DD/MM/RRRR\')
                                        ,TO_DATE(\'' . $fecha . '\', \'DD/MM/RRRR\'), \'FALSE\', \'FALSE\', \'TRUE\', \'FAC_LOCAL\', \'5\', \'TRUE\', \'FALSE\',TO_DATE(\'' . $fecha . '\', \'DD/MM/RRRR\'),TO_DATE(\'' . $fecha . '\', \'DD/MM/RRRR\'),TO_DATE(\'' . $fecha . '\', \'DD/MM/RRRR\')
                                        , \'1\', \'1\', \'1\', \'FALSE\',TO_DATE(\'' . $fecha . '\', \'DD/MM/RRRR\'),\'' . $claveAcceso . '\','. $totalSinImpuestos. ','.$totalImpuestos.')');
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
}
