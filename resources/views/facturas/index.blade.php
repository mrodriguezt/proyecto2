@extends('template.main')
@section('content')
<div align="center" class="success"><b>{{$mensaje}}</b></div>
    {!! Form::open(['route'=>'subirXML','method'=>'POST','id'=>'formularioATS','files'=>true])  !!}
    <div class="form-row">
        <div class="form-group">
            {!! Form::label('image','Subir XML') !!}
            {!! Form::file('image') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::submit('Agregar Facturas',['class'=>'btn btn-primary']) !!}
        </div>
    </div>
    {!! Form::close()  !!}

@endsection

@section('js')
    <script>
        /*  $( "#formularioATS" ).submit(function( event ) {
              alert( "Handler for .submit() called." );
          });*/
    </script>
@endsection