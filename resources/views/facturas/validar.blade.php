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
            @for($i=0;$i<count($mensaje);$i++)
                <div align="center" class="success"><b>{{ $mensaje[$i] }}</b></div>
            @endfor
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