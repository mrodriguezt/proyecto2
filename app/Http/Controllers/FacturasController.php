<?php

namespace App\Http\Controllers;

use App\Xml;
use Illuminate\Http\Request;

class FacturasController extends Controller
{
    public function subir()
    {
        $mensaje="";
        return view('facturas.index')->with('mensaje',$mensaje);
    }
    public function subirXML(Request $request)
    {
        /*$prueba = \DB::connection('clon')->table('C_VOUCHER_RETENTION_LINE')
            ->where('TAX_CODE', '729A')
            ->select('RETENTION_VALUE')
            ->get()->first();
*/
        if($request->file('image')){
            $file = $request->file('image');
            $carpeta = public_path()."/".time();
            mkdir($carpeta, 0700);
            if($file->getClientMimeType()=="application/x-zip-compressed"){
                $zip = new \ZipArchive;
                if ($zip->open($file) === TRUE) {
                    $zip->extractTo($carpeta);
                    $zip->close();
                    if ($gestor = opendir($carpeta)) {
                        while (false !== ($archivo = readdir($gestor))) {
                            if($archivo!="." && $archivo!=".." && $archivo!="--" ){
                                $file = $archivo;
                                $archivo = $carpeta."/".$archivo;
                                $xml = file_get_contents($archivo);
                                $xml = simplexml_load_string(utf8_decode($xml));
                                $ruc = $xml->infoTributaria->ruc;

                                $claveAcceso = $xml->infoTributaria->claveAcceso;
                                $estab= $xml->infoTributaria->estab;
                                $ptoEmi= $xml->infoTributaria->ptoEmi;
                                $secuencial= $xml->infoTributaria->secuencial;
                                $fechaEmision= $xml->infoFactura->fechaEmision;
                                $invoiceID = \DB::connection('clon')->table('dual')
                                    ->select(\DB::connection('clon')->raw('INVOICE_ID_SEQ.nextval AS VALOR'))
                                    ->get()->first();

                                $noFactura = $estab."-".$ptoEmi."-".$secuencial;
                                $fecha = date("d/m/Y");
                                $invoices = \DB::connection('clon')->table('INVOICE_TAB')
                                    ->where('IDENTITY',strval($ruc))
                                    ->where('INVOICE_NO',strval($noFactura))
                                    ->where('ROWSTATE','!=','Cancelled')
                                    ->select('INVOICE_TAB.COMPANY')
                                    ->get();



                                $destino = public_path()."/atsProveedores/".$file;
                                copy($archivo,$destino);

                                if($invoices->count()>0){
                                    //$xml = Xml::where('')
                                    $xmlTable = Xml::where('claveAcceso',$xml->infoTributaria->claveAcceso)->get()->first();
                                    if(!isset($xmlTable->id)) {
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
                                                'path' => $file
                                            ]

                                        );
                                    }else{
                                        Xml::where('claveAcceso',$xml->infoTributaria->claveAcceso)->update(
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
                                                'path' => $file
                                            ]

                                        );

                                    }

                                }else{
                                    \DB::connection('clon')->insert('insert into INVOICE_TAB (COMPANY,IDENTITY,PARTY_TYPE, INVOICE_ID, ROWVERSION, ROWSTATE, SERIES_ID, INVOICE_NO, CREATOR, INVOICE_DATE,DUE_DATE, CASH, COLLECT, INT_ALLOWED, INVOICE_TYPE, PAY_TERM_ID, AFF_BASE_LEDG_POST, AFF_LINE_POST, DELIVERY_DATE, ARRIVAL_DATE, CREATION_DATE, CURR_RATE, DIV_FACTOR, INVOICE_VERSION, GROSS_UP, PAY_TERM_BASE_DATE,C_AUTH_ID_SRI)
                                        values (\'EC01\', \''.$ruc.'\',\'SUPPLIER\', \''.$invoiceID->valor.'\', \'1\', \'Preliminary\', \'01\',\''.$noFactura.'\', \'MAN_SUPP_INVOICE_API\',TO_DATE(\''.$fechaEmision.'\', \'DD/MM/RRRR\')
                                        ,TO_DATE(\''.$fecha.'\', \'DD/MM/RRRR\'), \'FALSE\', \'FALSE\', \'TRUE\', \'FAC_LOCAL\', \'5\', \'TRUE\', \'FALSE\',TO_DATE(\''.$fecha.'\', \'DD/MM/RRRR\'),TO_DATE(\''.$fecha.'\', \'DD/MM/RRRR\'),TO_DATE(\''.$fecha.'\', \'DD/MM/RRRR\')
                                        , \'1\', \'1\', \'1\', \'FALSE\',TO_DATE(\''.$fecha.'\', \'DD/MM/RRRR\'),\''.$claveAcceso.'\')');
                                    $xmlTable = Xml::where('claveAcceso',$xml->infoTributaria->claveAcceso)->get()->first();
                                    if(!isset($xmlTable->id)) {
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
                                            'path' => $file

                                        ]);
                                    }else{
                                        Xml::where('claveAcceso',$xml->infoTributaria->claveAcceso)->update([
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
                                            'path' => $file
                                        ]);
                                    }
                                }
                                unlink($archivo);
                            }
                        }
                        closedir($gestor);
                    }
                    return view('facturas.index')->with('mensaje',"El archivo ha sido subido exitosamente");
                }else{
                    return view('facturas.index')->with('mensaje',"El archivo debe ser un .zip, No es posible subir");
                }
            }
            rmdir($carpeta);
        }
    }
}
