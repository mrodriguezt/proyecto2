@extends('template.main')
@section('content')

    {!! Form::open(['route'=>'subirXML','method'=>'POST','id'=>'formularioATS','files'=>true])  !!}
    <div class="form-row">
        <div class="form-group">
            {!! Form::label('image','Images') !!}
            {!! Form::file('image') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::submit('Agregar Artículo',['class'=>'btn btn-primary']) !!}
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