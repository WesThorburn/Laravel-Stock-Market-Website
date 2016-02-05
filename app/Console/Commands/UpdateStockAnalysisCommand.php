<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Stock;
use App\Models\StockMetrics;
use App\Models\SectorHistoricals;
use App\Models\Historicals;

class UpdateStockAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocks:updateStockAnalysis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a brief summary of the performance of each stock.';

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
    public function handle()
    {
        $stocks = Stock::lists('stock_code');
        $numberOfStocks = count($stocks);

        foreach($stocks as $key => $stock){
            $this->info('Updating: '.$stock.' | '.round((100/$numberOfStocks)*$key, 2).'%');
            $stockMetrics = StockMetrics::where('stock_code', $stock)->first();
            if($stockMetrics->last_trade > 0){
                $stockMetrics->analysis = $this->generateAnalysis($stockMetrics);
                $stockMetrics->save();
            }
        }
    }

    private function generateAnalysis(StockMetrics $stockMetrics){
        return $stockMetrics->stock_code . ' ' .
            $this->getTrendDescription($stockMetrics).
            $this->getMovingAverageDescription($stockMetrics).
            $this->getMetricsDescription($stockMetrics);
    }

    private function getTrendDescription(StockMetrics $stockMetrics){
        $description = "";
        if($stockMetrics->trend_short_term == "None" && $stockMetrics->trend_medium_term == "None" && $stockMetrics->trend_long_term == "None" ||
           $stockMetrics->trend_short_term == null && $stockMetrics->trend_medium_term == null && $stockMetrics->trend_long_term == null){
            return "shows no trend over the last 250 days. ";
        }

        if($stockMetrics->trend_long_term == 'Up'){
            $description .= "shows an upward trend over the last 250 days. ";
        }
        elseif($stockMetrics->trend_long_term == 'Down'){
            $description .= "shows a downward trend over the last 250 days. ";
        }
        elseif($stockMetrics->trend_medium_term == 'Up'){
            $description .= "shows an upward trend over the last 150 days. ";
        }
        elseif($stockMetrics->trend_medium_term == 'Down'){
            $description .= "shows a downward trend over the last 150 days. ";
        }
        elseif($stockMetrics->trend_short_term == 'Up'){
            $description .= "shows an upward trend over the last 50 days. ";
        }
        elseif($stockMetrics->trend_short_term == 'Down'){
            $description .= "shows a downward trend over the last 50 days. ";
        }
        return $description;
    }

    private function getMovingAverageDescription(StockMetrics $stockMetrics){
        $description = "";
        $mostRecentHistoricalRecord = Historicals::where('stock_code', $stockMetrics->stock_code)->orderBy('date', 'desc')->first();
        if($mostRecentHistoricalRecord->close != 0){
            if($mostRecentHistoricalRecord->fifty_day_moving_average > $mostRecentHistoricalRecord->two_hundred_day_moving_average){
                $description .= $stockMetrics->stock_code.
                "'s 50 day moving average is above the 200 day moving average, this could indicate a current or recent upward trend. ";
            }
            elseif($mostRecentHistoricalRecord->fifty_day_moving_average < $mostRecentHistoricalRecord->two_hundred_day_moving_average){
                $description .= $stockMetrics->stock_code.
                "'s 50 day moving average is below the 200 day moving average, this could indicate a current or recent downward trend. ";
            }
        }
        return $description;
    }

    private function getMetricsDescription(StockMetrics $stockMetrics){
        $description = "";
        $sectorMetrics = SectorHistoricals::where('sector', $stockMetrics->stock->sector)->orderBy('date', 'desc')->first();

        //EBITDA
        if($stockMetrics->EBITDA != 0){
            if($stockMetrics->EBITDA > $sectorMetrics->EBITDA){
                $description .= "Earnings Before Interest, Taxes, Depreciation and Amortization (EBITDA) is ".$stockMetrics->EBITDA.
                " million, which is higher than the sector average of ".$sectorMetrics->EBITDA." million. ";
            }
            elseif($stockMetrics->EBITDA < $sectorMetrics->EBITDA){
                $description .= "Earnings Before Interest, Taxes, Depreciation and Amortization (EBITDA) is ".$stockMetrics->EBITDA.
                " million, which is lower than the sector average of ".$sectorMetrics->EBITDA." million. ";
            }
        }

        //EPS Current
        if($stockMetrics->earnings_per_share_current != 0){
            if($stockMetrics->earnings_per_share_current > $sectorMetrics->earnings_per_share_current){
                $description .= "Current earnings per share is $".$stockMetrics->earnings_per_share_current.
                " which is higher than the sector average of $".$sectorMetrics->earnings_per_share_current.". ";
            }
            elseif($stockMetrics->earnings_per_share_current < $sectorMetrics->earnings_per_share_current){
                $description .= "Current earnings per share is $".$stockMetrics->earnings_per_share_current.
                " which is lower than the sector average of $".$sectorMetrics->earnings_per_share_current.". ";
            }
        }

        //Price To Earnings
        if($stockMetrics->price_to_earnings != 0){
            if($stockMetrics->price_to_earnings > $sectorMetrics->price_to_earnings){
                $description .= "The Price to earnings ratio is ".$stockMetrics->price_to_earnings." which is higher than the sector average of "
                .$sectorMetrics->price_to_earnings.". This could mean the stock is overpriced. ";
            }
            elseif($stockMetrics->price_to_earnings < $sectorMetrics->price_to_earnings){
                $description .= "The Price to earnings ratio is ".$stockMetrics->price_to_earnings." which is lower than the sector average of "
                .$sectorMetrics->price_to_earnings.". This could mean the stock is underpriced. ";
            }
        }

        //Price To Book
        if($stockMetrics->price_to_book != 0){
            if($stockMetrics->price_to_book > $sectorMetrics->price_to_book){
                $description .= "The Price to Book ratio is ".$stockMetrics->price_to_book." which is higher than the sector average of "
                .$sectorMetrics->price_to_book.". This could mean the stock is overpriced. ";
            }
            elseif($stockMetrics->price_to_book < $sectorMetrics->price_to_book){
                $description .= "The Price to Book ratio is ".$stockMetrics->price_to_book." which is lower than the sector average of "
                .$sectorMetrics->price_to_book.". This could mean the stock is underpriced. ";
            }
        }

        //Market Cap
        if($stockMetrics->market_cap != 0){
            if($stockMetrics->market_cap > $sectorMetrics->average_sector_market_cap){
                $description .= $stockMetrics->stock_code."'s Market Cap is ".formatMoneyAmountToLetter($stockMetrics->market_cap, true).
                " which is higher than the average sector market cap of ".formatMoneyAmountToLetter($sectorMetrics->average_sector_market_cap, true).
                ". This could mean ".$stockMetrics->stock_code." is less volatile than other ".$stockMetrics->stock->sector.' stocks.';
            }
            elseif($stockMetrics->market_cap < $sectorMetrics->average_sector_market_cap){
                $description .= $stockMetrics->stock_code."'s Market Cap is ".formatMoneyAmountToLetter($stockMetrics->market_cap, true).
                " which is lower than the average sector market cap of ".formatMoneyAmountToLetter($sectorMetrics->average_sector_market_cap, true).
                ". This could mean ".$stockMetrics->stock_code." is more volatile than other ".$stockMetrics->stock->sector.' stocks.';
            }
        }
        return $description;
    }

}