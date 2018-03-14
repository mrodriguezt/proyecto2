<?php

namespace App\Http\Controllers;

use App\Company_tab;
use App\Instant_invoice;
use Illuminate\Http\Request;
use DOMDocument;
class AtsController extends Controller
{
   public function ats(){

        $companias = Company_tab::select('COMPANY as value','NAME as label')->where('COUNTRY','EC')->whereNotNull('PERSON_TYPE')->get()->pluck('label','value');

       return view('ats.index')->with('companias',$companias);
   }
    public function limpiarcadena($cadena=""){
        $datos = explode(" ", $cadena);//separar palabras

        if(is_array($datos) && count($datos)>0){
            $aux="";
            for($i=0;$i<count($datos);$i++){
                $aux.= $this->limpiarString($datos[$i])." ";
            }
            $cadena = $aux;
        }else{
            $cadena = $this->limpiarString($razonsocial);
        }
        return $cadena;
    }
    public function limpiarString($texto)
    {
        $textoLimpio = preg_replace('([^A-Za-z0-9])', '', $texto);
        return $textoLimpio;
    }
   public function getAts(Request $request)
   {
       $anio = $request["anio"];
       $mes = $request["mes"];
       $compania = $request["compania"];
       $ruc = \DB::connection('oracle')->table('COMPANY_INVOICE_INFO')
           ->join('COMPANY', 'COMPANY.COMPANY', '=', 'COMPANY_INVOICE_INFO.COMPANY')
           ->where('COMPANY_INVOICE_INFO.COMPANY', $compania)
           ->select('COMPANY_INVOICE_INFO.VAT_NO','COMPANY.NAME')
           ->get()->first();

       if ($compania == "EC03") {
           $numEstabRuc = '002';
       } else {
            $numEstabRuc = '001';
        }

       $ruc->name = $this->limpiarcadena($ruc->name);
       $xml = new DomDocument('1.0', 'UTF-8');
       $raiz = $xml->createElement('iva');
       $raiz = $xml->appendChild($raiz);
       $nodo = $xml->createElement('TipoIDInformante','R');
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('IdInformante',$ruc->vat_no);
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('razonSocial',$ruc->name);
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('Anio',$anio);
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('Mes',$mes);
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('numEstabRuc',$numEstabRuc);
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('totalVentas',"0.00");
       $nodo = $raiz->appendChild($nodo);
       $nodo = $xml->createElement('codigoOperativo','IVA');
       $nodo = $raiz->appendChild($nodo);
       $compras = $xml->createElement('compras');
       $compras = $raiz->appendChild($compras);


       $fechaFinMes =  date("d",(mktime(0,0,0,intval($mes)+1,1,$anio)-1));
       //echo '2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes;


       $atsCompras = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
           ->join('IDENTITY_INVOICE_INFO', 'IDENTITY_INVOICE_INFO.IDENTITY', '=', 'MAN_SUPP_INVOICE.IDENTITY')
           ->join('SUPPLIER_INFO', 'SUPPLIER_INFO.SUPPLIER_ID', '=', 'MAN_SUPP_INVOICE.IDENTITY')
           ->where('IDENTITY_INVOICE_INFO.COMPANY', $compania)
           ->where('IDENTITY_INVOICE_INFO.PARTY_TYPE', 'Proveedor')
           ->where('MAN_SUPP_INVOICE.COMPANY', $compania)
           ->whereIn('MAN_SUPP_INVOICE.SERIES_ID',['01','02','03','04','05','06','07','08','09','10','11','12','15','16','18','19','20','21','22','23','24','41','42','43','44','45','47','48','49','50','51','52','294','344'])
           ->whereNotIn('MAN_SUPP_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
           ->whereRaw('MAN_SUPP_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select('SUPPLIER_INFO.NAME','SUPPLIER_INFO.PERSON_TYPE','MAN_SUPP_INVOICE.C_INVOICE_NO','MAN_SUPP_INVOICE.C_SERIES_ID','MAN_SUPP_INVOICE.C_SUBJECT_RETENTION','MAN_SUPP_INVOICE.C_DOUBLE_TRIBUTATION_DB','MAN_SUPP_INVOICE.C_TAX_REGIME_TEXT','MAN_SUPP_INVOICE.C_TAX_HAVEN_ID','MAN_SUPP_INVOICE.COUNTRY_CODE_SRI','MAN_SUPP_INVOICE.C_REG_TYPE_ID','MAN_SUPP_INVOICE.ID_PAYMENT_TYPE','MAN_SUPP_INVOICE.VAT_CURR_AMOUNT','MAN_SUPP_INVOICE.INVOICE_ID','MAN_SUPP_INVOICE.C_AUTH_ID_SRI','MAN_SUPP_INVOICE.VOUCHER_DATE_REF','MAN_SUPP_INVOICE.INVOICE_DATE','MAN_SUPP_INVOICE.SERIES_ID','MAN_SUPP_INVOICE.C_SUSTENANCE_ID','MAN_SUPP_INVOICE.IDENTITY','IDENTITY_INVOICE_INFO.TAX_ID_TYPE','IDENTITY_INVOICE_INFO.C_SUPP_REL_PARTY','MAN_SUPP_INVOICE.INVOICE_NO')
           ->get();
       //dd($atsCompras);

       foreach ($atsCompras as $atsCompra) {
           $detalleCompras = $xml->createElement('detalleCompras');
           $detalleCompras = $compras->appendChild($detalleCompras);
           $nodo = $xml->createElement('codSustento', $atsCompra->c_sustenance_id);
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('tpIdProv', $atsCompra->tax_id_type);
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('idProv', $atsCompra->identity);
           $detalleCompras->appendChild($nodo);
           $invoice = explode("-", $atsCompra->invoice_no);
           $nodo = $xml->createElement('tipoComprobante', $atsCompra->series_id);
           $detalleCompras->appendChild($nodo);
           if ($atsCompra->c_supp_rel_party == null || $atsCompra->c_supp_rel_party == "") {
               $atsCompra->c_supp_rel_party = "NO";
           }
           $nodo = $xml->createElement('parteRel', $atsCompra->c_supp_rel_party);
           $detalleCompras->appendChild($nodo);
           if ($atsCompra->tax_id_type == "03") {
               if ($atsCompra->person_type == "Physical") {
                   $atsCompra->person_type = "01";
               } else {
                   if ($atsCompra->person_type == "Juridical") {
                       $atsCompra->person_type = "02";
                   }
               }
               $nodo = $xml->createElement('tipoProv', $atsCompra->person_type);
               $detalleCompras->appendChild($nodo);
               $atsCompra->name = $this->limpiarcadena( $atsCompra->name);
               $nodo = $xml->createElement('denopr',  $atsCompra->name);
               $detalleCompras->appendChild($nodo);

           }
           $nodo = $xml->createElement('fechaRegistro', date('d/m/Y', strtotime($atsCompra->voucher_date_ref)));
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('establecimiento', $invoice[0]);
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('puntoEmision', $invoice[1]);
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('secuencial', intval($invoice[2]));
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('fechaEmision', date('d/m/Y', strtotime($atsCompra->invoice_date)));
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('autorizacion', $atsCompra->c_auth_id_sri);
           $detalleCompras->appendChild($nodo);
           $gngiva = \DB::connection('oracle')->table('MAN_SUPP_INVOICE_ITEM')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('VAT_CODE', 'IVA_COM_0%_NO_OBJETO')
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) as basenograiva'))
               ->get()->first();
           if (isset($gngiva->basenograiva)) {

               $nodo = $xml->createElement('baseNoGraIva', number_format(abs(floatval($gngiva->basenograiva)),2,".",""));
           } else {
               $nodo = $xml->createElement('baseNoGraIva', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $gngiva = \DB::connection('oracle')->table('MAN_SUPP_INVOICE_ITEM')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->whereIn('VAT_CODE', ['IVA_COM_0%_BS', 'IVA_COM_0%_RISE', 'IVA_COM_0%_RI', 'IVA_COM_0%_SCAMP'])
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) as base0iva'))
               ->get()->first();
           if (isset($gngiva->base0iva)) {

               $nodo = $xml->createElement('baseImponible', number_format(abs(floatval($gngiva->base0iva)),2,".",""));
           } else {
               $nodo = $xml->createElement('baseImponible', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $gngiva = \DB::connection('oracle')->table('MAN_SUPP_INVOICE_ITEM')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->whereIn('VAT_CODE', ['IVA_COM_12%_AF_CT', 'IVA_COM_12%_BS_CT', 'IVA_COM_12%_BS_SCT', 'IVA_COM_12%_RI'])
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) as base12iva'))
               ->get()->first();

           if (isset($gngiva->base12iva)) {
               $nodo = $xml->createElement('baseImpGrav', number_format(abs(floatval($gngiva->base12iva)),2,".",""));
           } else {
               $nodo = $xml->createElement('baseImpGrav', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('baseImpExe', "0.00");
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('montoICE', "0.00");
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('montoIva', number_format(abs(floatval($atsCompra->vat_curr_amount)),2,".",""));

           $detalleCompras->appendChild($nodo);
           // echo $atsCompra->invoice_id."---";
           $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('TAX_CODE', '721A')
               ->select(\DB::connection('oracle')->raw('SUM(to_number(RETENTION_VALUE, \'99999D99\', \'NLS_NUMERIC_CHARACTERS=\'\'.,\'\'\')) valorretencion'))
               ->get()->first();
           if (isset($retencion->valorretencion)) {
               $nodo = $xml->createElement('valRetBien10', number_format(abs(floatval($retencion->valorretencion)),2,".",""));

           } else {
               $nodo = $xml->createElement('valRetBien10', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('TAX_CODE', '723A')
               ->select(\DB::connection('oracle')->raw('SUM(to_number(RETENTION_VALUE, \'99999D99\', \'NLS_NUMERIC_CHARACTERS=\'\'.,\'\'\')) valorretencion'))
               ->get()->first();
           if (isset($retencion->valorretencion)) {
               $nodo = $xml->createElement('valRetServ20', number_format(abs(floatval($retencion->valorretencion)),2,".",""));
           } else {
               $nodo = $xml->createElement('valRetServ20', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('TAX_CODE', '725A')
               ->select(\DB::connection('oracle')->raw('SUM(to_number(RETENTION_VALUE, \'99999D99\', \'NLS_NUMERIC_CHARACTERS=\'\'.,\'\'\')) valorretencion'))
               ->get()->first();
           if (isset($retencion->valorretencion)) {
               $nodo = $xml->createElement('valorRetBienes', number_format(abs(floatval($retencion->valorretencion)),2,".",""));
           } else {
               $nodo = $xml->createElement('valorRetBienes', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $nodo = $xml->createElement('valorRetServ50', "0.00");
           $detalleCompras->appendChild($nodo);

           $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('TAX_CODE', '727A')
               ->select(\DB::connection('oracle')->raw('SUM(to_number(RETENTION_VALUE, \'99999D99\', \'NLS_NUMERIC_CHARACTERS=\'\'.,\'\'\')) valorretencion'))
               ->get()->first();
           if (isset($retencion->valorretencion)) {
               $nodo = $xml->createElement('valorRetServicios', number_format(abs(floatval($retencion->valorretencion)),2,".",""));
           } else {
               $nodo = $xml->createElement('valorRetServicios', "0.00");
           }
           $detalleCompras->appendChild($nodo);
           $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
               ->where('INVOICE_ID', $atsCompra->invoice_id)
               ->where('TAX_CODE', '729A')
               ->select(\DB::connection('oracle')->raw('SUM(to_number(RETENTION_VALUE, \'99999D99\', \'NLS_NUMERIC_CHARACTERS=\'\'.,\'\'\')) valorretencion'))
               ->get()->first();
           if (isset($retencion->valorretencion)) {
               $nodo = $xml->createElement('valRetServ100', number_format(abs(floatval($retencion->valorretencion)),2,".",""));
           } else {
               $nodo = $xml->createElement('valRetServ100', "0.00");
           }
           $detalleCompras->appendChild($nodo);


           $pagoExterior = $xml->createElement('pagoExterior');
           $pagoExterior = $detalleCompras->appendChild($pagoExterior);

           $nodo = $xml->createElement('pagoLocExt', $atsCompra->id_payment_type);
           $pagoExterior->appendChild($nodo);

           //C_REG_TYPE_ID
           if ($atsCompra->id_payment_type != '01') {
               $nodo = $xml->createElement('tipoRegi', $atsCompra->c_reg_type_id);
               $pagoExterior->appendChild($nodo);
               //COUNTRY_CODE_SRI
               $nodo = $xml->createElement('paisEfecPagoGen', $atsCompra->country_code_sri);
               $pagoExterior->appendChild($nodo);
               //C_TAX_HAVEN_ID
               $nodo = $xml->createElement('paisEfecPagoParFis', $atsCompra->c_tax_haven_id);
               $pagoExterior->appendChild($nodo);
               //C_TAX_REGIME_TEXT
               $nodo = $xml->createElement('denopago', $atsCompra->c_tax_regime_text);
               $pagoExterior->appendChild($nodo);
               //country_code_sri
               $nodo = $xml->createElement('paisEfecPago', $atsCompra->country_code_sri);
               $pagoExterior->appendChild($nodo);
           }
           //C_DOUBLE_TRIBUTATION_DB
           if ($atsCompra->c_double_tributation_db == "") {
               $atsCompra->c_double_tributation_db = "NO";
           }
           $nodo = $xml->createElement('aplicConvDobTrib', $atsCompra->c_double_tributation_db);
           $pagoExterior->appendChild($nodo);
           //C_SUBJECT_RETENTION
           if ($atsCompra->c_subject_retention == "") {
               $atsCompra->c_subject_retention = "NO";
           }
           $nodo = $xml->createElement('pagExtSujRetNorLeg', $atsCompra->c_subject_retention);
           $pagoExterior->appendChild($nodo);
           //C_SUBJECT_RETENTION
           if ($atsCompra->c_subject_retention == "") {
               $atsCompra->c_subject_retention = "NO";
           }
           $pagoRegFis = "";
           if ($atsCompra->id_payment_type == '03') {
               $pagoRegFis = "SI";
           } else {
               $pagoRegFis = "NO";
           }
           $nodo = $xml->createElement('pagoRegFis', $pagoRegFis);
           $pagoExterior->appendChild($nodo);
           $nodo = $xml->createElement('formaPago', '20');
           $pagoExterior->appendChild($nodo);

           if ($atsCompra->series_id != "41" && $atsCompra->series_id != "04" && $atsCompra->series_id != "05" ) {
               $air = $xml->createElement('air');
               $air = $detalleCompras->appendChild($air);

               $retenciones = \DB::connection('oracle')->table('C_VOUCHER_RETENTION_LINE')
                   ->where('INVOICE_ID', $atsCompra->invoice_id)
                   ->whereRaw('TAX_CODE NOT LIKE \'7%\'', [])
                   ->select('TAX_CODE', 'BASE_VALUE', 'TAX_CODE_PERC', 'RETENTION_VALUE')
                   ->get();

               foreach ($retenciones as $ret) {

                   $detalleAir = $xml->createElement('detalleAir');
                   $detalleAir = $air->appendChild($detalleAir);
                   $nodo = $xml->createElement('codRetAir', $ret->tax_code);
                   $detalleAir->appendChild($nodo);
                   $nodo = $xml->createElement('baseImpAir', floatval($ret->base_value));
                   $detalleAir->appendChild($nodo);
                   $nodo = $xml->createElement('porcentajeAir', floatval($ret->tax_code_perc));
                   $detalleAir->appendChild($nodo);

                   $nodo = $xml->createElement('valRetAir', number_format(abs(floatval($ret->retention_value)),2,".",""));
                   $detalleAir->appendChild($nodo);
               }
               $retencion = \DB::connection('oracle')->table('C_VOUCHER_RETENTION')
                   ->where('INVOICE_ID', $atsCompra->invoice_id)
                   ->select('RETENTION_NO', 'RETENTION_DATE', \DB::connection('oracle')->raw('C_ELECTRONIC_INVOICE_AUTH_API.Get_C_Auth_Id_Sri(COMPANY,C_INVOICE_ID) as AUTH_SRI'))
                   ->get()->first();
               if (isset($retencion->retention_no)) {
                   $RETENTION_NO = $retencion->retention_no;
                   $aRetention = explode("-", $RETENTION_NO);
                   $nodo = $xml->createElement('estabRetencion1', $aRetention[0]);
                   $detalleCompras->appendChild($nodo);
                   $nodo = $xml->createElement('ptoEmiRetencion1', $aRetention[1]);
                   $detalleCompras->appendChild($nodo);
                   $nodo = $xml->createElement('secRetencion1', intval($aRetention[2]));
                   $detalleCompras->appendChild($nodo);
                   $nodo = $xml->createElement('autRetencion1', $retencion->auth_sri);
                   $detalleCompras->appendChild($nodo);
                   $nodo = $xml->createElement('fechaEmiRet1', date('d/m/Y', strtotime($retencion->retention_date)));
                   $detalleCompras->appendChild($nodo);
               }
               //'MAN_SUPP_INVOICE.,INVOICE_NO','MAN_SUPP_INVOICE.SERIES_ID'
           }
           if (isset($atsCompra->c_invoice_no) && $atsCompra->c_invoice_no != null) {
               //select C_AUTH_ID_SRI from MAN_SUPP_INVOICE where INVOICE_NO = '001-007-000009824' AND COMPANY;
               $authSri = \DB::connection('oracle')->table('MAN_SUPP_INVOICE')
                   ->where('INVOICE_NO', $atsCompra->c_invoice_no)
                   ->where('COMPANY', $compania)
                   ->select('C_AUTH_ID_SRI')
                   ->get()->first();
               $nodo = $xml->createElement('docModificado', $atsCompra->c_series_id);
               $detalleCompras->appendChild($nodo);
               $INVOICE_NO = explode("-", $atsCompra->c_invoice_no);
               $nodo = $xml->createElement('estabModificado', $INVOICE_NO[0]);
               $detalleCompras->appendChild($nodo);
               $nodo = $xml->createElement('ptoEmiModificado', $INVOICE_NO[1]);
               $detalleCompras->appendChild($nodo);
               $nodo = $xml->createElement('secModificado', intval($INVOICE_NO[2]));
               $detalleCompras->appendChild($nodo);
               $nodo = $xml->createElement('autModificado', $authSri->c_auth_id_sri);
               $detalleCompras->appendChild($nodo);
           }
           if ($atsCompra->series_id == "41") {
               $reembolsos = $xml->createElement('reembolsos');
               $reembolsos = $detalleCompras->appendChild($reembolsos);
               $reembolsosDB = \DB::connection('oracle')->table('C_REFUND_INVOICE_LINE')
                   ->where('INVOICE_ID', $atsCompra->invoice_id)
                   ->where('COMPANY', $compania)
                   ->select('SUPPLIER_ID', 'SERIES_ID', 'INVOICE_NO', 'INVOICE_DATE', 'C_AUTH_ID_SRI', 'BASE_AMOUNT0_VAT', 'BASE_AMOUNT_N_VAT', 'BASE_AMOUNT_NO_VAT', 'VAT_AMOUNT', 'ICE_AMOUNT', 'TAX_ID_TYPE')
                   ->get();
               $totbasesImpReemb = 0;
               foreach ($reembolsosDB as $rem) {
                   $reembolso = $xml->createElement('reembolso');
                   $reembolso = $reembolsos->appendChild($reembolso);
                   $nodo = $xml->createElement('tipoComprobanteReemb', $rem->series_id);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('tpIdProvReemb', $rem->tax_id_type);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('idProvReemb', $rem->supplier_id);
                   $reembolso->appendChild($nodo);
                   $INVOICE_NO = explode("-", $rem->invoice_no);
                   $nodo = $xml->createElement('establecimientoReemb', $INVOICE_NO[0]);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('puntoEmisionReemb', $INVOICE_NO[1]);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('secuencialReemb', $INVOICE_NO[2]);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('autorizacionReemb', $rem->c_auth_id_sri);
                   $reembolso->appendChild($nodo);

                   $nodo = $xml->createElement('baseImponibleReemb',  number_format(abs(floatval($rem->base_amount0_vat)),2,".",""));
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('baseImpGravReemb', number_format(abs(floatval($rem->base_amount_n_vat)),2,".",""));

                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('baseNoGraIvaReemb',  number_format(abs(floatval($rem->base_amount_no_vat)),2,".",""));

                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('baseImpExeReemb', "0.00");
                   $reembolso->appendChild($nodo);
                   $totbasesImpReemb += floatval($rem->base_amount0_vat) + floatval($rem->base_amount_n_vat) + floatval($rem->base_amount_no_vat);

                   $nodo = $xml->createElement('montoIceReemb', $rem->ice_amount);
                   $reembolso->appendChild($nodo);
                   $nodo = $xml->createElement('montoIvaRemb', $rem->vat_amount);
                   $reembolso->appendChild($nodo);
               }

                   $nodo = $xml->createElement('totbasesImpReemb',   number_format(abs(floatval($totbasesImpReemb)),2,".",""));

                   $detalleCompras->appendChild($nodo);

           }
       }
       $ventasNodo = $xml->createElement('ventas');
       $ventasNodo = $raiz->appendChild($ventasNodo);
       $atsVentas = \DB::connection('oracle')->table('INSTANT_INVOICE')
           ->join('CUSTOMER_INFO', 'INSTANT_INVOICE.IDENTITY', '=', 'customer_info.customer_id')
           ->join('CUSTOMER_INFO_VAT', 'customer_info.customer_id', '=', 'customer_info_vat.customer_id')
           ->whereIn('INSTANT_INVOICE.SERIES_ID', ['18','04','05'])
           ->where('INSTANT_INVOICE.COMPANY', $compania)
           ->where('CUSTOMER_INFO_VAT.COMPANY', $compania)
           ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
           ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select(\DB::connection('oracle')->raw('COUNT(instant_invoice.invoice_no) as NUMERO_COMPROBANTES'),'CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','INSTANT_INVOICE.SERIES_ID')
           ->groupBy('CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','INSTANT_INVOICE.SERIES_ID')
           ->get();
       $aClientes = array();
       foreach ($atsVentas as $atsVenta) {
           $aClientes[] = $atsVenta->customer_id;
           $vtasTerceros = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
               ->join('CUSTOMER_INFO', 'CUSTOMER_ORDER_INV_HEAD.IDENTITY', '=', 'customer_info.customer_id')
               ->join('CUSTOMER_INFO_VAT', 'customer_info.customer_id', '=', 'customer_info_vat.customer_id')
               ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
               ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
               ->where('CUSTOMER_INFO_VAT.COMPANY', $compania)
               ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY',$atsVenta->customer_id)
               ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('COUNT(CUSTOMER_ORDER_INV_HEAD.INVOICE_ID) as NUMERO_COMPROBANTES'),'CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','CUSTOMER_ORDER_INV_HEAD.SERIES_ID')
               ->groupBy('CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','CUSTOMER_ORDER_INV_HEAD.SERIES_ID')
               ->get()->first();
           if($vtasTerceros!=null){
               $atsVenta->numero_comprobantes = floatval($atsVenta->numero_comprobantes)+floatval($vtasTerceros->numero_comprobantes);
           }

           $detalleVentas = $xml->createElement('detalleVentas');
           $detalleVentas = $ventasNodo->appendChild($detalleVentas);
           $nodo = $xml->createElement('tpIdCliente',$atsVenta->tax_id_type);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('idCliente',$atsVenta->customer_id);
           $detalleVentas->appendChild($nodo);
           if($atsVenta->c_related_party==null || $atsVenta->c_related_party==""){
               $atsVenta->c_related_party="NO";
           }
           $nodo = $xml->createElement('parteRel',$atsVenta->c_related_party);
           $detalleVentas->appendChild($nodo);
           if($atsVenta->person_type=="Physical"){
               $atsVenta->person_type="01";
           }else{
               if($atsVenta->person_type=="Juridical"){
                   $atsVenta->person_type="02";
               }
           }
           $nodo = $xml->createElement('tipoCliente',$atsVenta->person_type);
           $detalleVentas->appendChild($nodo);

           $atsVenta->name = $this->limpiarcadena( $atsVenta->name);
           $nodo = $xml->createElement('DenoCli',$atsVenta->name);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('tipoComprobante',$atsVenta->series_id);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('tipoEm',"E");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('numeroComprobantes',$atsVenta->numero_comprobantes);
           $detalleVentas->appendChild($nodo);
           $noObjetoIva = \DB::connection('oracle')->table('INSTANT_INVOICE')
               ->join('INSTANT_INVOICE_ITEM', 'INSTANT_INVOICE_ITEM.INVOICE_ID', '=', 'INSTANT_INVOICE.INVOICE_ID')
               ->whereIn('INSTANT_INVOICE.SERIES_ID', ['18','04','05'])
               ->where('INSTANT_INVOICE.COMPANY', $compania)
               ->where('INSTANT_INVOICE.IDENTITY', $atsVenta->customer_id)
               ->whereIn('INSTANT_INVOICE_ITEM.VAT_CODE', ['IVA_VEN_00%_NO_OBJET'])
               ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) AS baseNoGraIva'))
               ->get()->first();

           if($vtasTerceros!=null) {
               $ventas = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
                   ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
                   ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
                   ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
                   ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
                   ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE', ['IVA_VEN_00%_NO_OBJET'])
                   ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
                   ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
                   ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS baseNoGraIva'))
                   ->get()->first();
               if($ventas!=null){
                   $noObjetoIva->basenograiva = floatval($noObjetoIva->basenograiva)+floatval($ventas->basenograiva);
               }
           }
           if(isset($noObjetoIva->basenograiva)){
               $nodo = $xml->createElement('baseNoGraIva',number_format(abs(floatval($noObjetoIva->basenograiva)),2,".",""));

               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseNoGraIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $noObjetoIva = \DB::connection('oracle')->table('INSTANT_INVOICE')
               ->join('INSTANT_INVOICE_ITEM', 'INSTANT_INVOICE_ITEM.INVOICE_ID', '=', 'INSTANT_INVOICE.INVOICE_ID')
               ->whereIn('INSTANT_INVOICE.SERIES_ID', ['18','04','05'])
               ->where('INSTANT_INVOICE.COMPANY', $compania)
               ->where('INSTANT_INVOICE.IDENTITY', $atsVenta->customer_id)
               ->whereIn('INSTANT_INVOICE_ITEM.VAT_CODE', ['IVA_VEN_00%_LO_CRE','IVA_VEN_00%_RE_GA'])
               ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) AS baseImponible'))
               ->get()->first();
           if($vtasTerceros!=null) {
               $ventas = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
                   ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
                   ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
                   ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
                   ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
                   ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE', ['IVA_VEN_00%_LO_CRE','IVA_VEN_00%_RE_GA'])
                   ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
                   ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
                   ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS baseImponible'))
                   ->get()->first();
               if($ventas!=null){
                   $noObjetoIva->baseImponible = floatval($noObjetoIva->baseImponible)+floatval($ventas->baseImponible);
               }
           }
           if(isset($noObjetoIva->baseimponible)){
               $nodo = $xml->createElement('baseImponible',number_format(abs(floatval($noObjetoIva->baseimponible)),2,".",""));

               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseImponible',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $noObjetoIva = \DB::connection('oracle')->table('INSTANT_INVOICE')
               ->join('INSTANT_INVOICE_ITEM', 'INSTANT_INVOICE_ITEM.INVOICE_ID', '=', 'INSTANT_INVOICE.INVOICE_ID')
               ->whereIn('INSTANT_INVOICE.SERIES_ID', ['18','04','05'])
               ->where('INSTANT_INVOICE.COMPANY', $compania)
               ->where('INSTANT_INVOICE.IDENTITY', $atsVenta->customer_id)
               ->whereIn('INSTANT_INVOICE_ITEM.VAT_CODE', ['IVA_VEN_12%_AF_CON','IVA_VEN_12%_AF_CRE','IVA_VEN_12%_LO_CON','IVA_VEN_12%_LO_CRE'])
               ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(NET_CURR_AMOUNT) AS base'))
               ->get()->first();
           if($vtasTerceros!=null) {
               $ventas = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
                   ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
                   ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
                   ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
                   ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
                   ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE',['IVA_VEN_12%_AF_CON','IVA_VEN_12%_AF_CRE','IVA_VEN_12%_LO_CON','IVA_VEN_12%_LO_CRE'])
                   ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
                   ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
                   ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS base'))
                   ->get()->first();
               if($ventas!=null){
                   $noObjetoIva->base = floatval($noObjetoIva->base)+floatval($ventas->base);
               }
           }
           if(isset($noObjetoIva->base)){
               $nodo = $xml->createElement('baseImpGrav',number_format(abs(floatval($noObjetoIva->base)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseImpGrav',"0.00");
               $detalleVentas->appendChild($nodo);
           }


           $montoIva = \DB::connection('oracle')->table('INSTANT_INVOICE')
               ->join('INSTANT_INVOICE_ITEM', 'INSTANT_INVOICE_ITEM.INVOICE_ID', '=', 'INSTANT_INVOICE.INVOICE_ID')
               ->whereIn('INSTANT_INVOICE.SERIES_ID', ['18','04','05'])
               ->where('INSTANT_INVOICE.COMPANY', $compania)
               ->where('INSTANT_INVOICE.IDENTITY', $atsVenta->customer_id)
               ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(VAT_CURR_AMOUNT) AS iva'))
               ->get()->first();
           if($vtasTerceros!=null) {
               $ventas = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
                   ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
                   ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
                   ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
                   ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
                   ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE',['IVA_VEN_12%_AF_CON','IVA_VEN_12%_AF_CRE','IVA_VEN_12%_LO_CON','IVA_VEN_12%_LO_CRE'])
                   ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
                   ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
                   ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.VAT_CURR_AMOUNT) AS iva'))
                   ->get()->first();
               if($ventas!=null){
                   $montoIva->iva = floatval($montoIva->iva)+floatval($ventas->iva);
               }
           }
           if(isset($montoIva->iva)){
               $nodo = $xml->createElement('montoIva',number_format(abs($montoIva->iva),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('montoIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $nodo = $xml->createElement('montoICE',"0.00");
           $detalleVentas->appendChild($nodo);
           $retenciones = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
               ->where('IDENTITY', $atsVenta->customer_id)
               ->where('BILL_TYPE', 'RIVA')
               ->whereRaw('VOUCHER_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(FULL_CURR_AMOUNT) AS ret'))
               ->get()->first();
           if(isset($retenciones->ret)){
               $nodo = $xml->createElement('valorRetIva',number_format(abs($retenciones->ret),2,".",""));

               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('valorRetIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }

           $retenciones = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
               ->where('IDENTITY', $atsVenta->customer_id)
               ->where('BILL_TYPE', 'RFTE')
               ->whereRaw('VOUCHER_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(FULL_CURR_AMOUNT) AS ret'))
               // ->select('FULL_CURR_AMOUNT')
               ->get()->first();

           if(isset($retenciones->ret)){
               $nodo = $xml->createElement('valorRetRenta',number_format(abs($retenciones->ret),2,".",""));

               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('valorRetRenta',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $nodo = $xml->createElement('formaPago',"20");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('codEstab',$numEstabRuc);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('ventasEstab',"0.00");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('ivaComp',"0.00");
           $detalleVentas->appendChild($nodo);


       }

       $atsVentas = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
           ->join('CUSTOMER_INFO', 'CUSTOMER_ORDER_INV_HEAD.IDENTITY', '=', 'customer_info.customer_id')
           ->join('CUSTOMER_INFO_VAT', 'customer_info.customer_id', '=', 'customer_info_vat.customer_id')
           ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
           ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
           ->where('CUSTOMER_INFO_VAT.COMPANY', $compania)
           ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.IDENTITY',$aClientes)
           ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
           ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select(\DB::connection('oracle')->raw('COUNT(CUSTOMER_ORDER_INV_HEAD.INVOICE_ID) as NUMERO_COMPROBANTES'),'CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','CUSTOMER_ORDER_INV_HEAD.SERIES_ID')
           ->groupBy('CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','CUSTOMER_ORDER_INV_HEAD.SERIES_ID')
           ->get();
       foreach ($atsVentas as $atsVenta) {

           $detalleVentas = $xml->createElement('detalleVentas');
           $detalleVentas = $ventasNodo->appendChild($detalleVentas);
           $nodo = $xml->createElement('tpIdCliente',$atsVenta->tax_id_type);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('idCliente',$atsVenta->customer_id);
           $detalleVentas->appendChild($nodo);

           if($atsVenta->c_related_party==null || $atsVenta->c_related_party==""){
               $atsVenta->c_related_party="NO";
           }
           $nodo = $xml->createElement('parteRel',$atsVenta->c_related_party);
           $detalleVentas->appendChild($nodo);
           if($atsVenta->person_type=="Physical"){
               $atsVenta->person_type="01";
           }else{
               if($atsVenta->person_type=="Juridical"){
                   $atsVenta->person_type="02";
               }
           }
           $nodo = $xml->createElement('tipoCliente',$atsVenta->person_type);
           $detalleVentas->appendChild($nodo);
           $atsVenta->name = $this->limpiarcadena( $atsVenta->name);
           $nodo = $xml->createElement('DenoCli',$atsVenta->name);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('tipoComprobante',$atsVenta->series_id);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('tipoEm',"E");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('numeroComprobantes',$atsVenta->numero_comprobantes);
           $detalleVentas->appendChild($nodo);

           $noObjetoIva = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
               ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
               ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
               ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
               ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
               ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE', ['IVA_VEN_00%_NO_OBJET'])
               ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS baseNoGraIva'))
               ->get()->first();

           if(isset($noObjetoIva->basenograiva)){
               $nodo = $xml->createElement('baseNoGraIva',number_format(abs(floatval($noObjetoIva->basenograiva)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseNoGraIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $noObjetoIva = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
               ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
               ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
               ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
               ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
               ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE', ['IVA_VEN_00%_LO_CRE','IVA_VEN_00%_RE_GA'])
               ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS baseImponible'))
               ->get()->first();
           if(isset($noObjetoIva->baseimponible)){
               $nodo = $xml->createElement('baseImponible',number_format(abs(floatval($noObjetoIva->baseimponible)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseImponible',"0.00");
               $detalleVentas->appendChild($nodo);
           }

           $noObjetoIva = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
               ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
               ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
               ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
               ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
               ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE',['IVA_VEN_12%_AF_CON','IVA_VEN_12%_AF_CRE','IVA_VEN_12%_LO_CON','IVA_VEN_12%_LO_CRE'])
               ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.NET_CURR_AMOUNT) AS base'))
               ->get()->first();

           if(isset($noObjetoIva->base)){
               $nodo = $xml->createElement('baseImpGrav',number_format(abs(floatval($noObjetoIva->base)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('baseImpGrav',"0.00");
               $detalleVentas->appendChild($nodo);
           }

           $montoIva = \DB::connection('oracle')->table('CUSTOMER_ORDER_INV_HEAD')
               ->join('CUSTOMER_ORDER_INV_ITEM', 'CUSTOMER_ORDER_INV_ITEM.INVOICE_ID', '=', 'CUSTOMER_ORDER_INV_HEAD.INVOICE_ID')
               ->whereIn('CUSTOMER_ORDER_INV_HEAD.SERIES_ID', ['18','04','05'])
               ->where('CUSTOMER_ORDER_INV_HEAD.COMPANY', $compania)
               ->where('CUSTOMER_ORDER_INV_HEAD.IDENTITY', $atsVenta->customer_id)
               ->whereIn('CUSTOMER_ORDER_INV_ITEM.VAT_CODE',['IVA_VEN_12%_AF_CON','IVA_VEN_12%_AF_CRE','IVA_VEN_12%_LO_CON','IVA_VEN_12%_LO_CRE'])
               ->whereNotIn('CUSTOMER_ORDER_INV_HEAD.OBJSTATE',['Preliminary','Cancelled'])
               ->whereRaw('CUSTOMER_ORDER_INV_HEAD.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(CUSTOMER_ORDER_INV_ITEM.VAT_CURR_AMOUNT) AS iva'))
               ->get()->first();

           if(isset($montoIva->iva)){
               $nodo = $xml->createElement('montoIva',number_format(abs(floatval($montoIva->iva)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('montoIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $nodo = $xml->createElement('montoICE',"0.00");
           $detalleVentas->appendChild($nodo);
           $retenciones = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
               ->where('IDENTITY', $atsVenta->customer_id)
               ->where('BILL_TYPE', 'RIVA')
               ->whereRaw('VOUCHER_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(FULL_CURR_AMOUNT) AS ret'))
               ->get()->first();
           if(isset($retenciones->ret)){
               $nodo = $xml->createElement('valorRetIva',number_format(abs(floatval($retenciones->iva)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('valorRetIva',"0.00");
               $detalleVentas->appendChild($nodo);
           }

           $retenciones = \DB::connection('oracle')->table('BILL_OF_EXCHANGE')
               ->where('IDENTITY', $atsVenta->customer_id)
               ->where('BILL_TYPE', 'RFTE')
               ->whereRaw('VOUCHER_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
               ->select(\DB::connection('oracle')->raw('SUM(FULL_CURR_AMOUNT) AS ret'))
               // ->select('FULL_CURR_AMOUNT')
               ->get()->first();

           if(isset($retenciones->ret)){
               $nodo = $xml->createElement('valorRetRenta',number_format(abs(floatval($retenciones->ret)),2,".",""));
               $detalleVentas->appendChild($nodo);
           }else{
               $nodo = $xml->createElement('valorRetRenta',"0.00");
               $detalleVentas->appendChild($nodo);
           }
           $nodo = $xml->createElement('formaPago',"20");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('codEstab',$numEstabRuc);
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('ventasEstab',"0.00");
           $detalleVentas->appendChild($nodo);
           $nodo = $xml->createElement('ivaComp',"0.00");
           $detalleVentas->appendChild($nodo);


       }
       //ID_PAYMENT_TYPE
       $anuladosNodo = $xml->createElement('anulados');
       $anuladosNodo = $raiz->appendChild($anuladosNodo);

       $anulados = \DB::connection('oracle')->table('INSTANT_INVOICE')
           ->where('COMPANY', $compania)
           ->where('OBJSTATE','Cancelled')
           ->whereRaw('INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select(\DB::connection('oracle')->raw('(C_ELECTRONIC_INVOICE_AUTH_API.Get_C_Auth_Id_Sri(COMPANY,INVOICE_ID)) as SRI_AUTH'),\DB::connection('oracle')->raw('(C_ELECTRONIC_INVOICE_AUTH_API.Get_Obj_State(COMPANY,INVOICE_ID)) as ESTADO_SRI_AUTH'),'INVOICE_NO','SERIES_ID')
           ->get();

       foreach ($anulados as $anulado){
           if($anulado->sri_auth!="" && $anulado->sri_auth!=null){
               $detalleAnulados = $xml->createElement('detalleAnulados');
               $detalleAnulados = $anuladosNodo->appendChild($detalleAnulados);
               if($anulado->series_id=="PR"){
                   $anulado->series_id="18";
               }
               $a_invoiceId = explode("-",$anulado->invoice_no);
               $nodo = $xml->createElement('tipoComprobante',$anulado->series_id);
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('establecimiento',$a_invoiceId[0]);
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('puntoEmision',$a_invoiceId[1]);
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('secuencialInicio',intval($a_invoiceId[2]));
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('secuencialFin',intval($a_invoiceId[2]));
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('autorizacion',$anulado->sri_auth);
               $detalleAnulados->appendChild($nodo);
           }

       }
       $anulados = \DB::connection('oracle')->table('C_VOUCHER_RETENTION')
           ->where('COMPANY', $compania)
           ->where('OBJSTATE','Cancelled')
           ->whereRaw('RETENTION_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select(\DB::connection('oracle')->raw('(C_ELECTRONIC_INVOICE_AUTH_API.Get_C_Auth_Id_Sri(COMPANY,C_INVOICE_ID)) as SRI_AUTH'),\DB::connection('oracle')->raw('(C_ELECTRONIC_INVOICE_AUTH_API.Get_Obj_State(COMPANY,C_INVOICE_ID)) as ESTADO_SRI_AUTH'),'RETENTION_NO')
           ->get();

       foreach ($anulados as $anulado){
           if($anulado->sri_auth!="" && $anulado->sri_auth!=null){
               $detalleAnulados = $xml->createElement('detalleAnulados');
               $detalleAnulados = $anuladosNodo->appendChild($detalleAnulados);
               $a_invoiceId = explode("-",$anulado->retention_no);
               $nodo = $xml->createElement('tipoComprobante','07');
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('establecimiento',$a_invoiceId[0]);
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('puntoEmision',$a_invoiceId[1]);
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('secuencialInicio',intval($a_invoiceId[2]));
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('secuencialFin',intval($a_invoiceId[2]));
               $detalleAnulados->appendChild($nodo);
               $nodo = $xml->createElement('autorizacion',$anulado->sri_auth);
               $detalleAnulados->appendChild($nodo);
           }
       }

/*
       $exportacionesNodo = $xml->createElement('exportaciones');
       $exportacionesNodo = $raiz->appendChild($exportacionesNodo);

       $exportaciones= \DB::connection('oracle')->table('INSTANT_INVOICE')
           ->join('CUSTOMER_INFO', 'INSTANT_INVOICE.IDENTITY', '=', 'customer_info.customer_id')
           ->join('C_INVOIC_EXPORTATION_DATA', 'C_INVOIC_EXPORTATION_DATA.INVOICE_ID', '=', 'INSTANT_INVOICE.INVOICE_ID')
           ->join('CUSTOMER_INFO_VAT', 'customer_info.customer_id', '=', 'customer_info_vat.customer_id')
           ->where('INSTANT_INVOICE.invoice_type','Like','EXPORTACIO%')
           ->where('INSTANT_INVOICE.COMPANY', $compania)
           ->whereNotIn('INSTANT_INVOICE.OBJSTATE',['Preliminary','Cancelled'])
           ->whereRaw('INSTANT_INVOICE.INVOICE_DATE BETWEEN ? and ? ', ['2018-'.$mes.'-01',"2018-".$mes."-".$fechaFinMes])
           ->select('CUSTOMER_INFO_VAT.TAX_ID_TYPE','customer_info.customer_id','customer_info_vat.c_related_party','customer_info.person_type','customer_info.name','INSTANT_INVOICE.SERIES_ID')
           ->get();
       foreach ($exportaciones as $exportacion) {
           $detalleExportaciones = $xml->createElement('detalleExportaciones');
           $detalleExportaciones = $exportacionesNodo->appendChild($detalleExportaciones);
           $nodo = $xml->createElement('tipoComprobante', str_replace("_BO","",$exportacion->tax_id_type));
           $detalleExportaciones->appendChild($nodo);
           $nodo = $xml->createElement('idClienteEx', $exportacion->customer_id);
           $detalleExportaciones->appendChild($nodo);
       }
*/
       $xml->formatOutput = true;
       $el_xml = $xml->saveXML();

       echo htmlentities($el_xml);
      // return response()->view("ats.generacion_ats",["xml"=>htmlentities($el_xml)])->header('Content-Type', 'text/xml');

   }
}
