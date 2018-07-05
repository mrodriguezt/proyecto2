<Grid>
    <Body>
    <B>

        @foreach ($facturas as $factura)

            <I id="{{ $factura->invoice_no }}"
               fecha_emision ="{{$factura->invoice_date}}"
               serie ="{{$factura->series_id}}"
               no_factura ="{{$factura->invoice_no}}"
               cliente ="{{$factura->name}}"
               valor_sin_imp ="{{$factura->net_amount}}"
               valor_inc_imp ="{{$factura->gross_amount}}"
               fecha_envio ="{{$factura->fecha_envio}}"
               envio ="{{$factura->usuario_envio}}"
               status ="{{$factura->objstate}}"
            />
        @endforeach
    </B>
    </Body>
</Grid>