@extends('layouts.master')

@section('nav')
	@include('layouts.partials.nav-buttons', ['page' => 'stocks'])
@stop

@section('title')
	Stocks
@stop

@section('body')
	<script>
		/*$(document).ready(
            function() {
                setInterval(function() {
                	if(window.location.search == ""){
                		$('#metrics').load('/search/%7Bsearch%7D?viewType=partial');
                	}
                	else{
						$('#metrics').load('/search/%7Bsearch%7D'+window.location.search+'&viewType=partial');
                	}
                }, 60000);
            });*/
	</script>
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<div id="metrics">
					@include('layouts.partials.stock-list-display')
				</div>
			</div>
		</div>
	</div>
@stop