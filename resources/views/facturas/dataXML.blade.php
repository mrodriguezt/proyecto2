<Grid>
    <Body>
    <B>
        @foreach ($datos as $dato)

            <I id="{{ $dato->id}}"
               razonSocial ="{{$dato->razonSocial}}"
               ruc ="{{$dato->ruc}}"
               claveAcceso ="{{$dato->claveAcceso}}"
               codDoc ="{{$dato->codDoc}}"
               no_factura ="{{$dato->estab}}-{{$dato->ptoEmi}}-{{$dato->secuencial}}"
               enviado_ifs ="{{$dato->enviado_ifs}}"
               fecha_envio ="{{$dato->fecha_envio}}"

               path ="|{{ route('archivo',[$dato->path])}}|Descargar|new_window"
            />
        @endforeach
    </B>
    </Body>
</Grid>
