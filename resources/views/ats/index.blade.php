@extends('template.main')
@section('content')

    {!! Form::open(['route'=>'getAts','method'=>'POST','id'=>'formularioATS'])  !!}
    <div class="form-row">
        <div class="form-group col-md-2">
            {!! Form::label('anio','Año') !!}
            {!! Form::select('anio',["2017"=>"2017","2018"=>"2018","2019"=>"2019"],null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione el Año','required']) !!}

        </div>
        <div class="form-group col-md-2">
            {!! Form::label('mes','Mes') !!}
            {!! Form::select('mes',["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembte"],null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione el Mes','required']) !!}
        </div>

        <div class="form-group col-md-2">
            {!! Form::label('compania','Companias') !!}
            {!! Form::select('compania',$companias,null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione la Compañia','required']) !!}
        </div>
<div class="form-group col-md-2">
    <BR>
    {!! Form::submit('Generar ATS',['class'=>'btn btn-primary']) !!}
</div>
</div>
{!! Form::close()  !!}

@endsection

@section('js')
    <script>
    function crearNuevo(){


    }

    </script>
@endsection