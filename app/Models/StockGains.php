<?php namespace App\Models;

use App\Models\StockGains;
use Illuminate\Database\Eloquent\Model;

class StockGains extends Model
{
    protected $table = 'stock_gains';

    protected $fillable = [
		"stock_code",
		'week_change',
        'two_week_change',
        'month_change',
        'two_month_change',
        'three_month_change',
        'six_month_change',
        'this_year_change',
        'year_change',
        'two_year_change',
        'three_year_change',
        'five_year_change',
        'ten_year_change',
        'all_time_change',
        'updated_at'
	];

    public function stock(){
        return $this->belongsTo('App\Models\Stock', 'stock_code', 'stock_code');
    }

    public static function getBestPerformingStocksThisWeek($limit = 10){
        $stockList = StockMetrics::omitOutliers()->lists('stock_code');
        return StockGains::whereIn('stock_code', $stockList)->orderBy('week_change', 'desc')->take($limit)->get();
    }

    public static function getWorstPerformingStocksThisWeek($limit = 10){
        $stockList = StockMetrics::omitOutliers()->lists('stock_code');
        return StockGains::whereIn('stock_code', $stockList)->orderBy('week_change', 'asc')->take($limit)->get();
    }

    public static function getBestPerformingStocksThisYear($limit = 18){
        $stockList = StockMetrics::omitOutliers()->where('market_cap', '>=', 100)->lists('stock_code');
        return StockGains::whereIn('stock_code', $stockList)->where('this_year_change', '<', 250)->orderBy('this_year_change', 'desc')->take($limit)->get();
    }
}