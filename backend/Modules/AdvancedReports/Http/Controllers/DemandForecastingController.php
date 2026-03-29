<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\BusinessLocation;
use App\Contact;
use App\Category;
use App\Product;
use Carbon\Carbon;

class DemandForecastingController extends Controller
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display demand forecasting report index page
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $business_locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->pluck('name', 'id');
        $business_locations->prepend(__('advancedreports::lang.all_locations'), 'all');

        // Get categories
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $categories->prepend(__('advancedreports::lang.all_categories'), 'all');

        // Get brands 
        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->pluck('name', 'id');
        $brands->prepend(__('advancedreports::lang.all_brands'), 'all');

        return view('advancedreports::demand-forecasting.index', compact(
            'business_locations',
            'categories', 
            'brands'
        ));
    }

    /**
     * Get demand forecasting analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $category_id = $request->get('category_id');
        $supplier_id = $request->get('supplier_id');
        $forecast_period = $request->get('forecast_period', 3); // Default 3 months

        // Set default date range if not provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subYear()->format('Y-m-d');
        }

        return response()->json([
            'sales_predictions' => $this->getSalesPredictions($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period),
            'seasonal_patterns' => $this->getSeasonalPatterns($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'reorder_optimization' => $this->getReorderOptimization($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period),
            'stockout_alerts' => $this->getStockoutAlerts($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period),
            'summary_cards' => $this->getForecastingSummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'forecast_period' => $forecast_period
        ]);
    }

    /**
     * Get sales predictions by product using various forecasting methods
     */
    private function getSalesPredictions($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null, $forecast_period = 3)
    {
        // Get historical sales data for forecasting
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        // Get monthly sales data for trend analysis
        $monthlySalesData = $query->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                COALESCE(c.name, "Uncategorized") as category_name,
                COALESCE(b.name, "No Brand") as brand_name,
                DATE_FORMAT(t.transaction_date, "%Y-%m") as month,
                SUM(tsl.quantity) as monthly_sales,
                AVG(tsl.unit_price_inc_tax) as avg_price,
                COUNT(DISTINCT t.id) as transaction_count
            ')
            ->groupBy('p.id', 'p.name', 'p.sku', 'c.name', 'b.name', DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m")'))
            ->orderBy('p.id')
            ->orderBy('month')
            ->get();

        // Group by product for forecasting
        $productSales = $monthlySalesData->groupBy('product_id');
        
        $predictions = $productSales->map(function($sales, $productId) use ($forecast_period) {
            $product = $sales->first();
            $monthlyData = $sales->pluck('monthly_sales', 'month')->toArray();
            
            // Simple Moving Average (SMA) method
            $smaForecast = $this->calculateMovingAverageForecast($monthlyData, $forecast_period);
            
            // Linear Trend method
            $trendForecast = $this->calculateTrendForecast($monthlyData, $forecast_period);
            
            // Exponential Smoothing method
            $exponentialForecast = $this->calculateExponentialSmoothing($monthlyData, $forecast_period);
            
            // Seasonal decomposition
            $seasonalForecast = $this->calculateSeasonalForecast($monthlyData, $forecast_period);
            
            // Combined forecast (weighted average of methods)
            $combinedForecast = $this->calculateCombinedForecast([
                'sma' => $smaForecast,
                'trend' => $trendForecast,
                'exponential' => $exponentialForecast,
                'seasonal' => $seasonalForecast
            ]);
            
            // Calculate confidence intervals and accuracy metrics
            $accuracy = $this->calculateForecastAccuracy($monthlyData);
            
            // Generate future periods
            $futureMonths = $this->generateFutureMonths($forecast_period);
            
            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_sku' => $product->product_sku,
                'category_name' => $product->category_name,
                'brand_name' => $product->brand_name,
                'historical_data' => array_values($monthlyData),
                'avg_monthly_sales' => round(array_sum($monthlyData) / max(count($monthlyData), 1), 2),
                'total_historical_sales' => array_sum($monthlyData),
                'forecasts' => [
                    'combined' => $combinedForecast,
                    'sma' => $smaForecast,
                    'trend' => $trendForecast,
                    'exponential' => $exponentialForecast,
                    'seasonal' => $seasonalForecast
                ],
                'future_periods' => $futureMonths,
                'confidence_level' => $accuracy['confidence'],
                'forecast_accuracy' => $accuracy['accuracy_score'],
                'demand_volatility' => $accuracy['volatility'],
                'growth_trend' => $accuracy['trend']
            ];
        })->values();

        // Sort by forecast demand (highest first)
        return $predictions->sortByDesc(function($product) {
            return array_sum($product['forecasts']['combined']);
        })->values()->toArray();
    }

    /**
     * Calculate Simple Moving Average forecast
     */
    private function calculateMovingAverageForecast($data, $periods, $window = 3)
    {
        if (count($data) < $window) {
            $average = array_sum($data) / max(count($data), 1);
            return array_fill(0, $periods, round($average, 2));
        }
        
        $lastValues = array_slice($data, -$window);
        $movingAverage = array_sum($lastValues) / $window;
        
        return array_fill(0, $periods, round($movingAverage, 2));
    }

    /**
     * Calculate Linear Trend forecast
     */
    private function calculateTrendForecast($data, $periods)
    {
        if (count($data) < 2) {
            $average = array_sum($data) / max(count($data), 1);
            return array_fill(0, $periods, round($average, 2));
        }
        
        $n = count($data);
        $x = range(1, $n);
        $y = array_values($data);
        
        // Calculate linear regression
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function($xi, $yi) { return $xi * $yi; }, $x, $y));
        $sumX2 = array_sum(array_map(function($xi) { return $xi * $xi; }, $x));
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Generate forecasts
        $forecasts = [];
        for ($i = 1; $i <= $periods; $i++) {
            $forecast = $intercept + $slope * ($n + $i);
            $forecasts[] = round(max(0, $forecast), 2); // Ensure non-negative
        }
        
        return $forecasts;
    }

    /**
     * Calculate Exponential Smoothing forecast
     */
    private function calculateExponentialSmoothing($data, $periods, $alpha = 0.3)
    {
        if (empty($data)) {
            return array_fill(0, $periods, 0);
        }
        
        $smoothed = [array_values($data)[0]];
        
        for ($i = 1; $i < count($data); $i++) {
            $smoothed[$i] = $alpha * array_values($data)[$i] + (1 - $alpha) * $smoothed[$i - 1];
        }
        
        $lastSmoothed = end($smoothed);
        return array_fill(0, $periods, round(max(0, $lastSmoothed), 2));
    }

    /**
     * Calculate Seasonal forecast (basic seasonal decomposition)
     */
    private function calculateSeasonalForecast($data, $periods)
    {
        if (count($data) < 12) {
            // Not enough data for seasonal analysis, fall back to average
            $average = array_sum($data) / max(count($data), 1);
            return array_fill(0, $periods, round($average, 2));
        }
        
        // Calculate seasonal indices (assuming 12-month cycle)
        $seasonal = [];
        $monthlyAverages = [];
        
        // Group data by month position in year
        for ($month = 1; $month <= 12; $month++) {
            $monthValues = [];
            for ($i = $month - 1; $i < count($data); $i += 12) {
                if (isset(array_values($data)[$i])) {
                    $monthValues[] = array_values($data)[$i];
                }
            }
            $monthlyAverages[$month] = count($monthValues) > 0 ? array_sum($monthValues) / count($monthValues) : 0;
        }
        
        $overallAverage = array_sum($monthlyAverages) / max(count($monthlyAverages), 1);
        
        // Calculate seasonal indices
        for ($month = 1; $month <= 12; $month++) {
            $seasonal[$month] = $overallAverage > 0 ? $monthlyAverages[$month] / $overallAverage : 1;
        }
        
        // Generate seasonal forecasts
        $forecasts = [];
        $baseLevel = $overallAverage;
        
        for ($i = 0; $i < $periods; $i++) {
            $monthIndex = (($i + 1) % 12) ?: 12;
            $seasonalFactor = $seasonal[$monthIndex] ?? 1;
            $forecasts[] = round(max(0, $baseLevel * $seasonalFactor), 2);
        }
        
        return $forecasts;
    }

    /**
     * Calculate combined forecast using weighted average
     */
    private function calculateCombinedForecast($forecasts)
    {
        $weights = [
            'sma' => 0.2,
            'trend' => 0.3,
            'exponential' => 0.2,
            'seasonal' => 0.3
        ];
        
        $combined = [];
        $periods = count($forecasts['sma']);
        
        for ($i = 0; $i < $periods; $i++) {
            $weightedSum = 0;
            $totalWeight = 0;
            
            foreach ($weights as $method => $weight) {
                if (isset($forecasts[$method][$i])) {
                    $weightedSum += $forecasts[$method][$i] * $weight;
                    $totalWeight += $weight;
                }
            }
            
            $combined[] = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
        }
        
        return $combined;
    }

    /**
     * Calculate forecast accuracy metrics
     */
    private function calculateForecastAccuracy($data)
    {
        if (count($data) < 3) {
            return [
                'confidence' => 'Low',
                'accuracy_score' => 50,
                'volatility' => 'High',
                'trend' => 'Stable'
            ];
        }
        
        $values = array_values($data);
        $mean = array_sum($values) / count($values);
        
        // Calculate coefficient of variation (volatility)
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        $stdDev = sqrt($variance);
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 100;
        
        // Determine volatility level
        if ($cv < 20) {
            $volatility = 'Low';
            $confidence = 'High';
            $accuracy = 85;
        } elseif ($cv < 50) {
            $volatility = 'Medium';
            $confidence = 'Medium';
            $accuracy = 65;
        } else {
            $volatility = 'High';
            $confidence = 'Low';
            $accuracy = 45;
        }
        
        // Calculate trend
        if (count($values) >= 2) {
            $firstHalf = array_slice($values, 0, floor(count($values) / 2));
            $secondHalf = array_slice($values, floor(count($values) / 2));
            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);
            
            if ($secondAvg > $firstAvg * 1.1) {
                $trend = 'Growing';
            } elseif ($secondAvg < $firstAvg * 0.9) {
                $trend = 'Declining';
            } else {
                $trend = 'Stable';
            }
        } else {
            $trend = 'Stable';
        }
        
        return [
            'confidence' => $confidence,
            'accuracy_score' => $accuracy,
            'volatility' => $volatility,
            'trend' => $trend
        ];
    }

    /**
     * Generate future month labels
     */
    private function generateFutureMonths($periods)
    {
        $months = [];
        $current = Carbon::now();
        
        for ($i = 1; $i <= $periods; $i++) {
            $months[] = $current->copy()->addMonths($i)->format('M Y');
        }
        
        return $months;
    }

    /**
     * Get seasonal demand patterns analysis
     */
    private function getSeasonalPatterns($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        // Monthly patterns
        $monthlyPatterns = $query->clone()->selectRaw('
                MONTH(t.transaction_date) as month,
                MONTHNAME(t.transaction_date) as month_name,
                SUM(tsl.quantity) as total_demand,
                COUNT(DISTINCT p.id) as products_sold,
                AVG(tsl.quantity) as avg_demand_per_product,
                COUNT(DISTINCT t.id) as transaction_count
            ')
            ->groupBy(DB::raw('MONTH(t.transaction_date)'), DB::raw('MONTHNAME(t.transaction_date)'))
            ->orderBy('month')
            ->get();

        // Seasonal patterns (quarterly)
        $seasonalPatterns = $query->clone()->selectRaw('
                CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring"
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END as season,
                SUM(tsl.quantity) as total_demand,
                COUNT(DISTINCT p.id) as products_sold,
                AVG(tsl.quantity) as avg_demand,
                COUNT(DISTINCT t.id) as transaction_count
            ')
            ->groupBy(DB::raw('CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring"
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END'))
            ->get();

        // Day of week patterns
        $weeklyPatterns = $query->clone()->selectRaw('
                DAYOFWEEK(t.transaction_date) as day_of_week,
                DAYNAME(t.transaction_date) as day_name,
                SUM(tsl.quantity) as total_demand,
                AVG(tsl.quantity) as avg_demand,
                COUNT(DISTINCT t.id) as transaction_count
            ')
            ->groupBy(DB::raw('DAYOFWEEK(t.transaction_date)'), DB::raw('DAYNAME(t.transaction_date)'))
            ->orderBy('day_of_week')
            ->get();

        // Category seasonal analysis
        $categorySeasonality = $query->clone()->selectRaw('
                COALESCE(c.name, "Uncategorized") as category_name,
                CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring" 
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END as season,
                SUM(tsl.quantity) as seasonal_demand
            ')
            ->groupBy('c.name', DB::raw('CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring"
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END'))
            ->get();

        // Calculate seasonality indices
        $monthlyIndices = $this->calculateSeasonalityIndices($monthlyPatterns, 'month', 'total_demand');
        $seasonalIndices = $this->calculateSeasonalityIndices($seasonalPatterns, 'season', 'total_demand');

        return [
            'monthly_patterns' => $monthlyPatterns->map(function($item) use ($monthlyIndices) {
                return [
                    'month' => $item->month,
                    'month_name' => $item->month_name,
                    'total_demand' => $item->total_demand,
                    'avg_demand_per_product' => round($item->avg_demand_per_product, 2),
                    'transaction_count' => $item->transaction_count,
                    'seasonality_index' => $monthlyIndices[$item->month] ?? 1.0
                ];
            })->toArray(),
            'seasonal_patterns' => $seasonalPatterns->map(function($item) use ($seasonalIndices) {
                return [
                    'season' => $item->season,
                    'total_demand' => $item->total_demand,
                    'avg_demand' => round($item->avg_demand, 2),
                    'transaction_count' => $item->transaction_count,
                    'seasonality_index' => $seasonalIndices[$item->season] ?? 1.0
                ];
            })->toArray(),
            'weekly_patterns' => $weeklyPatterns->toArray(),
            'category_seasonality' => $categorySeasonality->toArray()
        ];
    }

    /**
     * Calculate seasonality indices
     */
    private function calculateSeasonalityIndices($data, $periodField, $valueField)
    {
        $total = $data->sum($valueField);
        $average = $total / max($data->count(), 1);
        
        $indices = [];
        foreach ($data as $item) {
            $indices[$item->$periodField] = $average > 0 ? round($item->$valueField / $average, 2) : 1.0;
        }
        
        return $indices;
    }

    /**
     * Get reorder point optimization recommendations
     */
    private function getReorderOptimization($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period)
    {
        // Get current stock levels and demand data
        $query = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('purchase_lines as pl', function($join) {
                $join->on('pl.variation_id', '=', 'pv.id')
                     ->whereRaw('pl.id = (SELECT id FROM purchase_lines pl2 WHERE pl2.variation_id = pv.id ORDER BY pl2.created_at DESC LIMIT 1)');
            })
            ->where('p.business_id', $business_id);

        if ($location_id && $location_id != 'all') {
            $query->where('vld.location_id', $location_id);
        }

        if ($category_id && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        $stockData = $query->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                COALESCE(c.name, "Uncategorized") as category_name,
                SUM(vld.qty_available) as current_stock,
                COALESCE(AVG(pl.purchase_price), 0) as unit_cost
            ')
            ->groupBy('p.id', 'p.name', 'p.sku', 'c.name')
            ->get();

        // Get sales velocity for each product
        $salesQuery = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $salesQuery->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id != 'all') {
            $salesQuery->where('p.category_id', $category_id);
        }

        $salesData = $salesQuery->selectRaw('
                p.id as product_id,
                SUM(tsl.quantity) as total_sold,
                AVG(tsl.quantity) as avg_order_qty,
                COUNT(DISTINCT DATE(t.transaction_date)) as active_days,
                STDDEV(tsl.quantity) as demand_volatility
            ')
            ->groupBy('p.id')
            ->get()
            ->keyBy('product_id');

        $periodDays = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;

        // Calculate optimized reorder points for each product
        $optimizations = $stockData->map(function($stock) use ($salesData, $periodDays, $forecast_period) {
            $sales = $salesData->get($stock->product_id);
            
            if (!$sales || $sales->total_sold == 0) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product_name,
                    'product_sku' => $stock->product_sku,
                    'category_name' => $stock->category_name,
                    'current_stock' => $stock->current_stock,
                    'daily_demand' => 0,
                    'lead_time_demand' => 0,
                    'safety_stock' => 0,
                    'reorder_point' => 0,
                    'max_stock' => 0,
                    'economic_order_qty' => 0,
                    'days_of_supply' => 999,
                    'reorder_recommendation' => 'No Sales Data',
                    'priority' => 'Low'
                ];
            }

            // Calculate key metrics
            $dailyDemand = $sales->total_sold / $periodDays;
            $demandVolatility = $sales->demand_volatility ?? 0;
            
            // Lead time assumptions (configurable)
            $leadTimeDays = 7; // Default 1 week
            $serviceLevel = 0.95; // 95% service level
            
            // Calculate safety stock using statistical method
            $leadTimeDemand = $dailyDemand * $leadTimeDays;
            $leadTimeVariance = $demandVolatility * sqrt($leadTimeDays);
            $zScore = 1.65; // For 95% service level
            $safetyStock = max(1, ceil($zScore * $leadTimeVariance));
            
            // Calculate reorder point
            $reorderPoint = ceil($leadTimeDemand + $safetyStock);
            
            // Calculate Economic Order Quantity (EOQ)
            $annualDemand = $dailyDemand * 365;
            $orderingCost = 50; // Assumed ordering cost
            $holdingCostRate = 0.25; // 25% holding cost
            $holdingCost = $stock->unit_cost * $holdingCostRate;
            
            $eoq = $holdingCost > 0 ? ceil(sqrt((2 * $annualDemand * $orderingCost) / $holdingCost)) : ceil($dailyDemand * 30);
            
            // Calculate maximum stock level
            $maxStock = $reorderPoint + $eoq;
            
            // Calculate days of supply
            $daysOfSupply = $dailyDemand > 0 ? $stock->current_stock / $dailyDemand : 999;
            
            // Generate recommendations
            if ($stock->current_stock <= $reorderPoint) {
                $recommendation = 'REORDER NOW';
                $priority = 'High';
            } elseif ($daysOfSupply <= $leadTimeDays * 1.5) {
                $recommendation = 'MONITOR CLOSELY';
                $priority = 'Medium';
            } elseif ($stock->current_stock >= $maxStock) {
                $recommendation = 'OVERSTOCKED';
                $priority = 'Medium';
            } else {
                $recommendation = 'OPTIMAL';
                $priority = 'Low';
            }

            return [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product_name,
                'product_sku' => $stock->product_sku,
                'category_name' => $stock->category_name,
                'current_stock' => $stock->current_stock,
                'daily_demand' => round($dailyDemand, 2),
                'lead_time_demand' => round($leadTimeDemand, 1),
                'safety_stock' => $safetyStock,
                'reorder_point' => $reorderPoint,
                'max_stock' => $maxStock,
                'economic_order_qty' => $eoq,
                'days_of_supply' => round($daysOfSupply, 1),
                'reorder_recommendation' => $recommendation,
                'priority' => $priority,
                'demand_volatility' => round($demandVolatility, 2),
                'service_level' => round($serviceLevel * 100, 1)
            ];
        });

        // Group by recommendation type
        $recommendations = $optimizations->groupBy('reorder_recommendation')->map(function($group, $recommendation) {
            return [
                'recommendation' => $recommendation,
                'count' => $group->count(),
                'total_value' => $group->sum(function($item) {
                    return $item['current_stock'] * 10; // Rough value estimate
                }),
                'products' => $group->sortBy('days_of_supply')->values()->toArray()
            ];
        })->values();

        return [
            'optimizations' => $optimizations->sortBy('days_of_supply')->values()->toArray(),
            'recommendations_summary' => $recommendations->toArray(),
            'critical_items' => $optimizations->where('priority', 'High')->sortBy('days_of_supply')->values()->take(20)->toArray(),
            'optimization_metrics' => [
                'total_products_analyzed' => $optimizations->count(),
                'products_needing_reorder' => $optimizations->where('reorder_recommendation', 'REORDER NOW')->count(),
                'overstocked_products' => $optimizations->where('reorder_recommendation', 'OVERSTOCKED')->count(),
                'avg_days_of_supply' => round($optimizations->avg('days_of_supply'), 1)
            ]
        ];
    }

    /**
     * Get stock-out prevention alerts
     */
    private function getStockoutAlerts($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period)
    {
        // Get products at risk of stock-out based on current stock and demand forecast
        $reorderData = $this->getReorderOptimization($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period);
        $forecastData = $this->getSalesPredictions($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period);
        
        // Create forecast lookup
        $forecastLookup = collect($forecastData)->keyBy('product_id');
        
        // Generate stock-out alerts
        $alerts = collect($reorderData['optimizations'])->map(function($item) use ($forecastLookup, $forecast_period) {
            $forecast = $forecastLookup->get($item['product_id']);
            $predictedDemand = $forecast ? array_sum($forecast['forecasts']['combined']) : 0;
            
            // Calculate stock-out risk
            $monthlyDemand = $item['daily_demand'] * 30;
            $stockoutRisk = $this->calculateStockoutRisk($item, $predictedDemand, $forecast_period);
            
            // Determine alert level
            $alertLevel = $this->determineAlertLevel($item, $stockoutRisk);
            
            // Calculate when stock will run out
            $stockoutDate = $this->calculateStockoutDate($item);
            
            return [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'category_name' => $item['category_name'],
                'current_stock' => $item['current_stock'],
                'daily_demand' => $item['daily_demand'],
                'days_of_supply' => $item['days_of_supply'],
                'reorder_point' => $item['reorder_point'],
                'predicted_demand_3m' => round($predictedDemand, 1),
                'stockout_risk' => $stockoutRisk,
                'alert_level' => $alertLevel['level'],
                'alert_message' => $alertLevel['message'],
                'estimated_stockout_date' => $stockoutDate,
                'recommended_action' => $this->getRecommendedAction($item, $stockoutRisk),
                'urgency_score' => $this->calculateUrgencyScore($item, $stockoutRisk)
            ];
        });

        // Sort by urgency (highest first)
        $sortedAlerts = $alerts->sortByDesc('urgency_score');

        // Group alerts by level
        $alertsByLevel = $alerts->groupBy('alert_level')->map(function($group, $level) {
            return [
                'level' => $level,
                'count' => $group->count(),
                'products' => $group->sortByDesc('urgency_score')->values()->toArray()
            ];
        })->values();

        return [
            'alerts' => $sortedAlerts->values()->toArray(),
            'alerts_by_level' => $alertsByLevel->toArray(),
            'critical_alerts' => $sortedAlerts->where('alert_level', 'Critical')->take(10)->values()->toArray(),
            'high_alerts' => $sortedAlerts->where('alert_level', 'High')->take(15)->values()->toArray(),
            'alert_summary' => [
                'total_alerts' => $alerts->count(),
                'critical_count' => $alerts->where('alert_level', 'Critical')->count(),
                'high_count' => $alerts->where('alert_level', 'High')->count(),
                'medium_count' => $alerts->where('alert_level', 'Medium')->count(),
                'avg_days_to_stockout' => round($alerts->where('days_of_supply', '<', 999)->avg('days_of_supply'), 1)
            ]
        ];
    }

    /**
     * Calculate stock-out risk percentage
     */
    private function calculateStockoutRisk($item, $predictedDemand, $forecast_period)
    {
        if ($item['daily_demand'] <= 0) {
            return 0;
        }
        
        $monthsOfSupply = ($item['current_stock'] / $item['daily_demand']) / 30;
        $demandCoverage = $monthsOfSupply / $forecast_period;
        
        // Risk factors
        $stockLevel = $item['current_stock'] <= $item['reorder_point'] ? 40 : 0;
        $demandRisk = min(30, (1 - $demandCoverage) * 30);
        $volatilityRisk = min(30, $item['demand_volatility'] * 2);
        
        return max(0, min(100, $stockLevel + $demandRisk + $volatilityRisk));
    }

    /**
     * Determine alert level based on risk and stock status
     */
    private function determineAlertLevel($item, $stockoutRisk)
    {
        if ($item['current_stock'] <= 0) {
            return [
                'level' => 'Critical',
                'message' => 'OUT OF STOCK - Immediate replenishment required'
            ];
        } elseif ($item['days_of_supply'] <= 7) {
            return [
                'level' => 'Critical', 
                'message' => 'Less than 7 days of stock remaining'
            ];
        } elseif ($stockoutRisk >= 70) {
            return [
                'level' => 'High',
                'message' => 'High risk of stock-out within forecast period'
            ];
        } elseif ($stockoutRisk >= 40 || $item['days_of_supply'] <= 14) {
            return [
                'level' => 'Medium',
                'message' => 'Medium risk of stock-out - monitor closely'
            ];
        } else {
            return [
                'level' => 'Low',
                'message' => 'Stock levels adequate for forecast period'
            ];
        }
    }

    /**
     * Calculate estimated stock-out date
     */
    private function calculateStockoutDate($item)
    {
        if ($item['daily_demand'] <= 0 || $item['current_stock'] <= 0) {
            return $item['current_stock'] <= 0 ? 'Already out of stock' : 'No demand pattern';
        }
        
        $daysRemaining = $item['current_stock'] / $item['daily_demand'];
        
        if ($daysRemaining <= 1) {
            return 'Today';
        } elseif ($daysRemaining <= 7) {
            return 'Within ' . ceil($daysRemaining) . ' days';
        } else {
            return Carbon::now()->addDays($daysRemaining)->format('M j, Y');
        }
    }

    /**
     * Get recommended action for stock-out prevention
     */
    private function getRecommendedAction($item, $stockoutRisk)
    {
        if ($item['current_stock'] <= 0) {
            return 'EMERGENCY REORDER - Order ' . $item['economic_order_qty'] . ' units immediately';
        } elseif ($item['current_stock'] <= $item['reorder_point']) {
            return 'REORDER NOW - Order ' . $item['economic_order_qty'] . ' units';
        } elseif ($stockoutRisk >= 70) {
            return 'PREPARE TO REORDER - Monitor daily and order ' . $item['economic_order_qty'] . ' units when stock reaches ' . $item['reorder_point'];
        } elseif ($stockoutRisk >= 40) {
            return 'MONITOR CLOSELY - Check stock levels weekly';
        } else {
            return 'NO ACTION REQUIRED - Stock levels adequate';
        }
    }

    /**
     * Calculate urgency score for prioritization
     */
    private function calculateUrgencyScore($item, $stockoutRisk)
    {
        $score = 0;
        
        // Stock level urgency
        if ($item['current_stock'] <= 0) {
            $score += 100;
        } elseif ($item['current_stock'] <= $item['reorder_point']) {
            $score += 80;
        } elseif ($item['days_of_supply'] <= 7) {
            $score += 60;
        }
        
        // Risk factor
        $score += $stockoutRisk * 0.5;
        
        // Demand volume factor (higher volume = higher urgency)
        $score += min(20, $item['daily_demand'] * 2);
        
        return min(200, $score);
    }

    /**
     * Get forecasting summary cards
     */
    private function getForecastingSummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period)
    {
        $reorderData = $this->getReorderOptimization($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period);
        $alertsData = $this->getStockoutAlerts($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period);
        $forecastData = $this->getSalesPredictions($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id, $forecast_period);
        
        // Calculate prediction accuracy
        $totalProducts = count($forecastData);
        $highConfidenceProducts = collect($forecastData)->where('confidence_level', 'High')->count();
        $predictionAccuracy = $totalProducts > 0 ? round(($highConfidenceProducts / $totalProducts) * 100, 1) : 0;
        
        // Calculate total forecasted demand
        $totalForecastedDemand = collect($forecastData)->sum(function($product) {
            return array_sum($product['forecasts']['combined']);
        });

        return [
            'total_products_analyzed' => $totalProducts,
            'prediction_accuracy' => $predictionAccuracy,
            'total_forecasted_demand' => round($totalForecastedDemand, 0),
            'critical_alerts' => $alertsData['alert_summary']['critical_count'],
            'high_alerts' => $alertsData['alert_summary']['high_count'],
            'products_needing_reorder' => $reorderData['optimization_metrics']['products_needing_reorder'],
            'avg_days_of_supply' => $reorderData['optimization_metrics']['avg_days_of_supply'],
            'forecast_period_months' => $forecast_period,
            'high_confidence_products' => $highConfidenceProducts,
            'overstocked_products' => $reorderData['optimization_metrics']['overstocked_products']
        ];
    }
}