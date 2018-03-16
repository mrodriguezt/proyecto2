@extends('template.main')
@section('content')
<style>
    .spinner {
        position: absolute;
        left: 50%;
        top: 50%;
        height:60px;
        width:60px;
        margin:0px auto;
        -webkit-animation: rotation .6s infinite linear;
        -moz-animation: rotation .6s infinite linear;
        -o-animation: rotation .6s infinite linear;
        animation: rotation .6s infinite linear;
        border-left:6px solid rgba(0,174,239,.15);
        border-right:6px solid rgba(0,174,239,.15);
        border-bottom:6px solid rgba(0,174,239,.15);
        border-top:6px solid rgba(0,174,239,.8);
        border-radius:100%;
    }

    @-webkit-keyframes rotation {
        from {-webkit-transform: rotate(0deg);}
        to {-webkit-transform: rotate(359deg);}
    }
    @-moz-keyframes rotation {
        from {-moz-transform: rotate(0deg);}
        to {-moz-transform: rotate(359deg);}
    }
    @-o-keyframes rotation {
        from {-o-transform: rotate(0deg);}
        to {-o-transform: rotate(359deg);}
    }
    @keyframes rotation {
        from {transform: rotate(0deg);}
        to {transform: rotate(359deg);}
    }

</style>
<div class="spinner"></div>
    {!! Form::open(['route'=>'getAts','method'=>'POST','id'=>'formularioATS'])  !!}
    <div class="form-row">
        <div class="form-group col-md-2">
            {!! Form::label('anio','Año') !!}
            {!! Form::select('anio',["2017"=>"2017","2018"=>"2018"],null,['class'=>'form-control select-proyecto','placeholder'=>'Seleccione el Año','required']) !!}

        </div>
        <div class="form-group col-md-2">
            {!! Form::label('mes','Mes') !!}after
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
      /*  $( "#formularioATS" ).submit(function( event ) {
            alert( "Handler for .submit() called." );
        });*/
    </script>
@endsection