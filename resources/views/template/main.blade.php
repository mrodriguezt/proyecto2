<!DOCTYPE html>
<head>
	<meta charset="UTF-8">
	<title>@yield('title','Default') | Panel de Administracion</title>
	<link rel="stylesheet"  type="text/css" href="{{ asset('plugins/bootstrap/css/bootstrap.css') }}">
	<link rel="stylesheet"  type="text/css" href="{{ asset('plugins/chosen/chosen.css') }}">


	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Contabilidad') }}</title>

   
    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>



</head>
<body>
	<div id="app">
	@include('template.partials.nav')
	<section class="section-admin">
			<div class="panel-body">
			@include('template.partials.errors')		
			@yield('content')	
			</div>

	</section>
	</div>
	<script src="{{ asset('js/app.js') }}"></script>
	<script src="{{ asset('plugins/chosen/chosen.jquery.js')}}"></script>
	<script src="{{ asset('plugins/grid-11/GridE.js')}}"></script>

	<script>
        window.Laravel = <?php echo json_encode([
            'csrfToken' => csrf_token(),
        ]); ?>
	</script>


	@yield('js')
</body>
</html>
