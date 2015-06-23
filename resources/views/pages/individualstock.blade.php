@extends('layouts.master')

@section('title')
	{{$stock->stock->stock_code}}
@stop

@section('body')
	<div class="col-md-6 col-md-offset-3">
		<div class="center-block">
			{{ $linechart->render('LineChart', 'myFancyChart', 'temps_div', true) }}
		</div>
	</div>

	<script>
		
		
	</script>
@stop