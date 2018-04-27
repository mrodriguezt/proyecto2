@extends('template.main')
@section('content')

    {!! Form::open(['route'=>'validarArchivo','method'=>'POST','id'=>'formularioATS','files'=>true])  !!}

    <div class="form-row">

        <div class="form-group col-md-3">
            {!! Form::label('compania','Compania') !!}
            {!! Form::select('compania',$companias,null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione la Compañia','required']) !!}
        </div>
        <div class="form-group col-md-3">
            {!! Form::label('file','Subir Archivo .txt') !!}
            {!! Form::file('file') !!}
        </div>
        <div class="form-group col-md-2">
            <BR>
            {!! Form::submit('Validar Facturas',['class'=>'btn btn-primary']) !!}

        </div>
    </div>
    {!! Form::close()  !!}

    <br>
    <br>
    <br>
    <div class="form-row">
        <div class="form-group col-md-12">
            <table class="table table-bordered">
                <tr>
                    <td>No.</td>
                    <td>FACTURA</td>
                    <td>RUC</td>
                    <td>NOMBRE</td>
                    <td>VALOR</td>
                </tr>
            @for($i=0;$i<count($facturas);$i++)
                <tr>
                    <td>{{ $i+1  }}</td>
                    <td>{{ $facturas[$i]["FACTURA"] }}</td>
                    <td>{{ $facturas[$i]["RUC"] }}</td>
                    <td>{{ $facturas[$i]["PROVEEDOR"] }}</td>
                    <td>{{ $facturas[$i]["VALOR"] }}</td>
                </tr>
                </tr>
            @endfor
            </table>


         </div>
    </div>


@endsection

@section('js')
    <script>
        /*  $( "#formularioATS" ).submit(function( event ) {
              alert( "Handler for .submit() called." );
          });*/
    </script>
@endsection