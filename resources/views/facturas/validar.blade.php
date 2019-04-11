@extends('template.main')
@section('content')
    {!! Form::open(['route'=>'validarArchivo','method'=>'POST','id'=>'formularioATS','files'=>true])  !!}
    <div class="form-row">
        <div class="form-group col-md-3">
            {!! Form::label('compania','Compania') !!}
            {!! Form::select('compania',$companias,$compania,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione la Compañia','onchange'=>'buscarDatos()','required']) !!}
            <b>{{$mensaje}}</b>
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('anio','Año') !!}
            {!! Form::select('anio',["2017"=>"2017","2018"=>"2018","2019"=>"2019"],null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione el Año','required']) !!}

        </div>
        <div class="form-group col-md-2">
            {!! Form::label('mes','Mes') !!}
            {!! Form::select('mes',["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembte"],null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione el Mes','required']) !!}
        </div>
        <div class="form-group col-md-3">
            {!! Form::label('file','Subir Archivo .txt') !!}
            {!! Form::file('file') !!}
        </div>
        <div class="form-group col-md-2">
            <BR>
            {!! Form::submit('Subir Archivo',['class'=>'btn btn-primary']) !!}<br>
        </div>
    </div>
    {!! Form::close()  !!}
    <div  id="facturacion" style="width:100%;height:800px;">
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

        DisposeGrids();
        TreeGrid({Layout:{Url:"gridLayoutDocumentos"},Data:{Url:"gridDataDocumentos/"+$("#compania").val()},Debug:0},"facturacion");
        function buscarDatos(){
            DisposeGrids();
            TreeGrid({Layout:{Url:"gridLayoutDocumentos"},Data:{Url:"gridDataDocumentos/"+$("#compania").val()},Debug:0},"facturacion");
        }
        var enviando=0;
        function enviarIFS(G) {
            if(enviando==0) {
                enviando=1;
                GridActual = G;
                if (GridActual != "undefined") {
                    var R = GridActual.GetSelRows();
                    if (R.length) {
                        for (var i = 0; i < R.length; i++) {
                            if (Is(R[i], "Selected")) {
                                var mensaje = GridActual.GetValue(R[i], "mensaje");
                                if (mensaje == "NO") {
                                    if (GridActual.GetValue(R[i], "comprobante") == "Factura") {
                                       $.ajax({
                                            type: "POST",
                                            url: '{{URL::route("enviar.documentoIFS")}}',
                                            data: {
                                                id: GridActual.GetValue(R[i], "codigo"),
                                                "_token": "{{ csrf_token() }}",
                                            },
                                            success: function (msg) {
                                                if (msg == "ERROR") {
                                                    alert("LA FACTURA YA EXISTE")
                                                } else {
                                                    alert("La factura ha sido enviada al IFS")
                                                    enviando=0;
                                                }
                                            }
                                        });
                                        enviando=0;
                                    } else {

                                        alert("El Documento seleccionado debe ser una factura");
                                        enviando=0;
                                    }
                                } else {
                                    alert("El Documento seleccionado " + GridActual.GetValue(R[i], "serie_comprobante") + " SI existe en IFS");
                                    enviando=0;
                                }

                            }
                        }
                        G.Reload();
                    }
                }
            }else{
                alert("Espere mientras se termina el proceso");
            }
        }
    </script>
@endsection