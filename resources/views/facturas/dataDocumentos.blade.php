<Grid>
    <Body>
    <B>
        @foreach ($documentos as $documento)

            <I id="{{ $documento->id}}"
               codigo ="{{$documento->id}}"
               comprobante ="{{$documento->comprobante}}"
               serie_comprobante ="{{$documento->serie_comprobante}}"
               ruc_emisor ="{{$documento->ruc_emisor}}"
               razon_social_emisor ="{{$documento->razon_social_emisor}}"
               fecha_emision ="{{$documento->fecha_emision}}"
               fecha_autorizacion ="{{$documento->fecha_autorizacion}}"
               tipo_emision ="{{$documento->tipo_emision}}"
               documento_relacionado ="{{$documento->documento_relacionado}}"
               identificacion_receptor ="{{$documento->identificacion_receptor}}"
               clave_acceso ="{{$documento->clave_acceso}}"
               numero_autorizador ="{{$documento->numero_autorizador}}"
               voucher_no ="{{$documento->voucher_no}}"
               mensaje ="{{$documento->mensaje}}"
               importe_total ="{{$documento->importe_total}}"
               Existe ="{{$documento->factura_existe}}"
            />
        @endforeach
    </B>
    </Body>
</Grid>
