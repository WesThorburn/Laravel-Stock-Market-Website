@extends('layouts.master')

@section('title')
	{{$stock->stock->stock_code}}
@stop

@section('body')
	<script type="text/javascript">
		$(document).ready(
            function() {
                setInterval(function() {
                		$('#metrics').load('/relatedstocks/{{$stock->stock->stock_code}}');
                }, 10000);
        	});

		function getGraphData(timeFrame, dataType){
			$.getJSON('/graph/'+ '{{ $stock->stock_code }}/' + timeFrame + '/' + dataType, function (dataTableJson) {
				lava.loadData('StockPrice', dataTableJson, function (chart) {
				});
			});
			var timeFrameButtonIds = [
				"last_month", 
				"last_3_months", 
				"last_6_months", 
				"last_year", 
				"last_2_years", 
				"last_5_years", 
				"last_10_years", 
				"all_time"
			];

			timeFrameButtonIds.forEach(function(buttonId){
				document.getElementById(buttonId).className = "btn btn-default";
			});
			document.getElementById(timeFrame).className = "btn btn-default active";
		}
	</script>
	<div class="container">
		<div class="row">
			<div class="col-lg-11 col-lg-offset-1">
				<h1>{{ $stock->company_name }}</h1>
				<h2>{{ $stock->sector }}</h2>
				<h3>(ASX: {{ $stock->stock_code }})</h3>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<div class="col-lg-11 col-lg-offset-1">
				<h2>${{ $metrics->last_trade }}
					<small @if($metrics->day_change < 0) class="color-red" @elseif($metrics->day_change > 0) class="color-green" @endif>
						{{ $metrics->day_change }}%
					</small>
				</h2>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-lg-offset-1">
				<div class="panel panel-default">
					<div class="panel-heading">
						<div class="btn-group btn-group-sm pull-center" role="group">
							<button class="btn btn-default active" onclick="getGraphData('last_month', 'Price')" id="last_month">30 Days</button>
							<button class="btn btn-default" onclick="getGraphData('last_3_months', 'Price')" id="last_3_months">3 Months</button>
							<button class="btn btn-default" onclick="getGraphData('last_6_months', 'Price')" id="last_6_months">6 Months</button>
							<button class="btn btn-default" onclick="getGraphData('last_year', 'Price')" id="last_year">12 Months</button>
							<button class="btn btn-default" onclick="getGraphData('last_2_years', 'Price')" id="last_2_years">2 Years</button>
							<button class="btn btn-default" onclick="getGraphData('last_5_years', 'Price')" id="last_5_years">5 Years</button>
							<button class="btn btn-default" onclick="getGraphData('last_10_years', 'Price')" id="last_10_years">10 Years</button>
							<button class="btn btn-default" onclick="getGraphData('all_time', 'Price')" id="all_time">All</button>
						</div>
					</div>
					<div class="panel-body">
						<div id="stock_price_div" class="pull-left">
							@areachart('StockPrice', 'stock_price_div')
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading"><b>Key Metrics</b></div>
					<table class="table table-bordered">
						<tbody>
							<tr>
								<td>EBITDA</td>
								<td>{{ $metrics->EBITDA }}</td>
							</tr>
							<tr>
								<td>EPS (This Year)</td>
								<td>{{ $metrics->earnings_per_share_current }}</td>
							</tr>
							<tr>
								<td>EPS (Next Year)</td>
								<td>{{ $metrics->earnings_per_share_next_year }}</td>
							</tr>
							<tr>
								<td>Price/Earnings</td>
								<td>{{ $metrics->price_to_earnings }}</td>
							</tr>
							<tr>
								<td>Price/Book</td>
								<td>{{ $metrics->price_to_book }}</td>
							</tr>
							<tr>
								<td>52 Week High</td>
								<td>{{ $metrics->year_high }}</td>
							</tr>
							<tr>
								<td>52 Week Low</td>
								<td>{{ $metrics->year_low }}</td>
							</tr>
							<tr>
								<td>50 Day Moving Average</td>
								<td>{{ $metrics->fifty_day_moving_average }}</td>
							</tr>
							<tr>
								<td>200 Day Moving Average</td>
								<td>{{ $metrics->two_hundred_day_moving_average }}</td>
							</tr>
							<tr>
								<td>Market Cap (M)</td>
								<td>{{ $metrics->market_cap }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		@if($relatedStocks->first())
			<div class="row">
				<div class="col-lg-9 col-lg-offset-1">
					<div id="metrics">
						@include('layouts.partials.related-stock-list-display')
					</div>	
				</div>		
			</div>
		@endif
	</div>
@stop

@section('footer')
	
@stop