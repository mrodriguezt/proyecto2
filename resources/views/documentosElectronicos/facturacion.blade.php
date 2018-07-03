@extends('template.main')
@section('content')
    <div class="form-group col-md-3">
        {!! Form::select('compania',$companias,null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione la Compañia','required','id'=>'compania']) !!}
    </div>
    <div class="col-md-9">
        <span class="badge">Fecha Inicio</span>
        <input  id="dateInicio" name="dateInicio" placeholder="YYYY-MM-DD" type="text"/>
        <span class="badge">Fecha Fin</span>
        <input  id="dateFin" name="dateFin" placeholder="YYYY-MM-DD" type="text"/>

        <a href="#" onclick="mostrarDatos()"><span class="badge">Buscar</span></a>
    </div>
    <div  id="facturacion" style="width:100%;height:800px;">
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function(){
            var date_input1=$('input[name="dateInicio"]'); //our date input has the name "date"
            var date_input2=$('input[name="dateFin"]'); //our date input has the name "date"
            var container=$('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
            var options={
                format: 'yyyy-mm-dd',
                container: container,
                todayHighlight: true,
                autoclose: true,
            };
            date_input1.datepicker(options);
            date_input2.datepicker(options);
        })
    function mostrarDatos(){
        DisposeGrids();
        TreeGrid({Layout:{Url:"gridLayoutFacturacion"},Data:{Url:"gridDataFacturacion/"+$("#dateInicio").val()+"/"+$("#dateFin").val()+"/"+$("#compania").val()},Debug:0},"facturacion");
    }
    function enviarXML(G) {
        GridActual = G;
        if (GridActual != "undefined") {
            var R = GridActual.GetSelRows();
            if (R.length) {
                for (var i = 0; i < R.length; i++) {
                    if (Is(R[i], "Selected")) {
                        var NO_FACTURA = GridActual.GetValue(R[i], "no_factura");

                        $.ajax({
                            type: "POST",
                            url: '{{URL::route("enviar.factura")}}',
                            data: {INVOICE_ID: NO_FACTURA,
                                COMPANY: $("#compania").val(),
                                "_token": "{{ csrf_token() }}",},
                            success: function( msg ) {
                                alert("La factura ha sido enviada a Tandi")
                            }
                        });

                    }
                }
                G.Reload();
            }
        }
    }
    </script>
@endsection