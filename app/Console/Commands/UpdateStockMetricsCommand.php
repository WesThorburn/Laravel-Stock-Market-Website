<?php namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\StockMetrics;
use App\Models\Stock;
use App\Models\Historicals;
use Carbon\Carbon;

class UpdateStockMetricsCommand extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stocks:updateStockMetrics';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Updates stock metrics from Yahoo finance data.";
	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->info('Updating stock metrics... This may take several minutes...');
		$iterationNumber = 1;
		$maxIterations = ceil(Stock::count()/100);
		if($this->option('testMode')){
			$maxIterations = 1;
			$this->info("[Test Mode]");
		}

		UpdateStockMetricsCommand::insertMetricRowsForNewStocks();

		while($iterationNumber <= $maxIterations){
			$stockCodeParameter = UpdateStockMetricsCommand::getStockCodeParameter($this->option('testMode'));
			$stockURL = "http://finance.yahoo.com/d/quotes.csv?s=".$stockCodeParameter."&f=sl1p2c1ohgvj4ee8p5rp6kjj1y";
			$metrics = explode("\n", file_get_contents($stockURL));
			foreach($metrics as $metric){
				if($metric != null){
					$individualMetric = explode(',', $metric);
					$stockCode = substr(explode('.', $individualMetric[0])[0], 1);
					StockMetrics::updateOrCreate(['stock_code' => $stockCode], [
						"stock_code" => $stockCode,
						"last_trade" => $individualMetric[1],
						"percent_change" => UpdateStockMetricsCommand::correctPercentChange($individualMetric[1], substr($individualMetric[2], 1, -2), $stockCode),
						'day_change' => $individualMetric[3],
						"open" => $individualMetric[4],
						"high" => $individualMetric[5],
						"low" => $individualMetric[6],
						"close" => $individualMetric[1], //Last Trade after closing time
						"adj_close" => 0.000, //No Data Available
						"volume" => $individualMetric[7],
						"EBITDA" => UpdateStockMetricsCommand::formatEBITDA($individualMetric[8]),
						"earnings_per_share_current" => $individualMetric[9],
						"earnings_per_share_next_year" => $individualMetric[10],
						"price_to_sales" => $individualMetric[11],
						"price_to_earnings" => $individualMetric[12],
						"price_to_book" => $individualMetric[13],
						"year_high" => $individualMetric[14],
						"year_low" => $individualMetric[15],
						"market_cap" => UpdateStockMetricsCommand::correctMarketCap($stockCode, formatMoneyAmountToNumber($individualMetric[16])),
						"dividend_yield" => $individualMetric[17],
						"updated_at" => date("Y-m-d H:i:s")
					]);
				}
			}
			$this->info("Updating... ".round(($iterationNumber)*(100/$maxIterations), 2)."%");
			$iterationNumber++;
		}
		$this->info('All stock metrics were updated successfully!');
	}
	//Creates rows for stocks that aren't yet in the metrics table (Required because getStockCodeParameter gets the 100 oldest metrics)
	private static function insertMetricRowsForNewStocks(){
		$stockCodes = Stock::lists('stock_code');
		foreach($stockCodes as $stockCode){
			StockMetrics::updateOrCreate(['stock_code' => $stockCode], []);
		}
	}
	//Gets list of stock codes separated by addition symbols, only TLS and CBA in test mode
	private static function getStockCodeParameter($testMode = false){
		if(!$testMode){
			//Limit of 100 at a time due to yahoo's url length limit
			$stockCodeList = StockMetrics::where('updated_at', '<', Carbon::now()->subSeconds(60))->orderBy('updated_at')->take(100)->lists('stock_code');
			$stockCodeParameter = "";
			foreach($stockCodeList as $stockCode){
				$stockCodeParameter .= "+".$stockCode.".AX";
			}
			return substr($stockCodeParameter, 1);
		}
		else{
			return "TLS.AX+CBA.AX";
		}
	}
	//Checks EBITDA value and calls appropriate helper function to format it
	private static function formatEBITDA($ebitda){
		if(substr($ebitda, -1) == 'B' || substr($ebitda, -1) == 'M'){
			return formatMoneyAmountToNumber($ebitda);
		}
		elseif($ebitda > 10000 || $ebitda < -10000){
			return formatHundredThousandToMillion($ebitda);
		}
		return $ebitda;
	}
	//Temporary Function to manually correct market caps provided by Yahoo Finance API
	private static function correctMarketCap($stockCode, $marketCap){
		$stocksWithIncorrectMarketCaps = ["URF","MOV","TIX","NSR","PGF","FGG","PAI","CQA","BPA","IDR","CMA","WAX","FGX","TOF","EMF","USG","BAF","UPD","KLO","SAO","EAI","USF","WDE","WMK","GDF","BIQ","AYZ","ENC","AHJ","BWR","AYK","AIK","APW","AYD","AWQ","PAF","RYD","UPG","TOT","IIL","AYH","FSI","8EC","VGI","TML","SCG","GC1","AOD","KLR","MKE","AAI","KFG","AIQ","AUP","FDC","PTX","DTX","USR","AKY","EOR","BOP","AIB","SXI","SLE","NTL","EGP","MFE","MUB","OGH","ELR","OEG","DAF","EQU","ASN","SXS","SZG","RCF","AQJ","PRH","OOK","AYJ","POW", "IVQ", "CR8", "CGW", "LVT", "TV2", "HML", "WNR"];
		if(in_array($stockCode, $stocksWithIncorrectMarketCaps)){
			return $marketCap/1000;
		}
		return $marketCap;
	}

	//Nulls current day's percentage change if it's the exact same as yesterday's
	private static function correctPercentChange($lastTrade, $percentChange, $stockCode){
		$mostRecentHistoricalDate = Historicals::getMostRecentHistoricalDate($stockCode);
		$yesterdaysClose = Historicals::where(['stock_code' => $stockCode, 'date' => $mostRecentHistoricalDate])->pluck('close');
		if($yesterdaysClose && $percentChange != 0 && $lastTrade == $yesterdaysClose){
			return 0;
		}
		return $percentChange;
	}
	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['example', InputArgument::OPTIONAL, 'An example argument.'],
		];
	}
	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['testMode', null, InputOption::VALUE_OPTIONAL, 'Runs the command in Test Mode.', false],
		];
	}
}