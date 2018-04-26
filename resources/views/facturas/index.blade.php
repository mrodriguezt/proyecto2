@extends('template.main')
@section('content')

    {!! Form::open(['route'=>'subirXML','method'=>'POST','id'=>'formularioATS','files'=>true])  !!}
    <div class="form-row">
        <div class="form-group col-md-3">
            {!! Form::label('compania','Compania') !!}
            {!! Form::select('compania',$companias,null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione la Compañia','required']) !!}
        </div>
        <div class="form-group col-md-3">
            {!! Form::label('image','Subir XML') !!}
            {!! Form::file('image') !!}
        </div>
        <div class="form-group col-md-4">
            {!! Form::submit('Subir Facturas IFS',['class'=>'btn btn-primary']) !!}
            {!! Form::button('Reporte XML',['class'=>'btn btn-primary','onclick'=>'verXML()']) !!}
        </div>
    </div>
    {!! Form::close()  !!}
    <br>
    <br>
    <br>
    <div class="form-row">
        <div class="form-group col-md-12">
            <div align="center" class="success"><b>{{$mensaje}}</b></div>
        </div>
    </div>
    <div  id="reporteXML" style="width:100%;height:800px;">
    </div>
@endsection

@section('js')
    <script>
        /*  $( "#formularioATS" ).submit(function( event ) {
              alert( "Handler for .submit() called." );
          });*/
        function verXML(){
            DisposeGrids();
            TreeGrid({Layout:{Url:"gridLayoutXML/"},Data:{Url:"gridDataXML/"+$("#compania").val()},Debug:0},"reporteXML");
        }
    </script>
@endsection