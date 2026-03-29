<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductCategoryController extends Controller
{
    /**
     * Display product category performance report
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.product_category_performance')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get business locations
        $business_locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->pluck('name', 'id');
        $business_locations->prepend(__('advancedreports::lang.all_locations'), 'all');

        // Get categories with hierarchy
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $categories->prepend(__('advancedreports::lang.all_categories'), 'all');

        // Get brands for cross-selling analysis
        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->pluck('name', 'id');
        $brands->prepend(__('advancedreports::lang.all_brands'), 'all');

        return view('advancedreports::product-category.index', compact(
            'business_locations',
            'categories',
            'brands'
        ));
    }

    /**
     * Get category performance analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->subMonths(12)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->get('location_id');
        $category_id = $request->get('category_id');
        $brand_id = $request->get('brand_id');
        
        // Convert single values to arrays for consistency with methods
        $location_ids = $this->processFilterIds($location_id);
        $category_ids = $this->processFilterIds($category_id);
        $brand_ids = $this->processFilterIds($brand_id);

        try {
            \Log::info('Product Category Analytics Request', [
                'business_id' => $business_id, 
                'start_date' => $start_date, 
                'end_date' => $end_date,
                'location_ids' => $location_ids,
                'category_ids' => $category_ids
            ]);
            
            $category_contribution = $this->getCategoryContribution($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids);
            \Log::info('Category contribution data', [
                'categories_count' => isset($category_contribution['categories']) ? count($category_contribution['categories']) : 0,
                'summary' => $category_contribution['summary'] ?? null,
                'raw_data' => $category_contribution
            ]);
            
            return response()->json([
                'category_contribution' => $category_contribution,
                'cross_selling_opportunities' => $this->getCrossSellingOpportunities($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'margin_analysis' => $this->getMarginAnalysByCategory($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'growth_trends' => $this->getCategoryGrowthTrends($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'seasonal_patterns' => $this->getSeasonalPatterns($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'inventory_turnover' => $this->getInventoryTurnoverByCategory($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'top_performers' => $this->getTopPerformingCategories($business_id, $start_date, $end_date, $location_ids, $brand_ids),
                'category_comparison' => $this->getCategoryComparison($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids)
            ]);
        } catch (\Exception $e) {
            \Log::error('Product Category Analytics Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load analytics data'], 500);
        }
    }

    /**
     * Process filter IDs - handle 'all', arrays, and single values
     */
    private function processFilterIds($filterValue)
    {
        if (empty($filterValue)) {
            return [];
        }
        
        if (is_array($filterValue)) {
            // Remove 'all' from array and return numeric IDs only
            $filtered = array_filter($filterValue, function($value) {
                return $value !== 'all' && is_numeric($value);
            });
            return array_values($filtered);
        }
        
        // Single value - return as array if not 'all'
        return ($filterValue !== 'all' && is_numeric($filterValue)) ? [$filterValue] : [];
    }

    /**
     * Category contribution analysis
     */
    private function getCategoryContribution($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        if (!empty($category_ids)) {
            $query->whereIn('c.id', $category_ids);
        }

        if (!empty($brand_ids)) {
            $query->whereIn('b.id', $brand_ids);
        }

        $categories = $query->select([
            'c.id as category_id',
            'c.name as category_name',
            DB::raw('SUM(tsl.quantity) as total_quantity'),
            DB::raw('SUM(tsl.unit_price_before_discount * tsl.quantity) as gross_sales'),
            DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_sales'),
            DB::raw('SUM((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) * tsl.quantity) as gross_profit'),
            DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
            DB::raw('COUNT(DISTINCT tsl.product_id) as unique_products'),
            DB::raw('AVG(tsl.unit_price_inc_tax) as avg_unit_price')
        ])
        ->groupBy('c.id', 'c.name')
        ->orderBy('total_sales', 'desc')
        ->get();

        // Calculate contribution percentages
        $total_sales = $categories->sum('total_sales');
        $total_profit = $categories->sum('gross_profit');

        foreach ($categories as $category) {
            $category->sales_contribution = $total_sales > 0 ? ($category->total_sales / $total_sales) * 100 : 0;
            $category->profit_contribution = $total_profit > 0 ? ($category->gross_profit / $total_profit) * 100 : 0;
            $category->profit_margin = $category->total_sales > 0 ? ($category->gross_profit / $category->total_sales) * 100 : 0;
            $category->avg_transaction_value = $category->transaction_count > 0 ? $category->total_sales / $category->transaction_count : 0;
        }

        return [
            'categories' => $categories,
            'summary' => [
                'total_categories' => $categories->count(),
                'total_sales' => $total_sales,
                'total_profit' => $total_profit,
                'avg_margin' => $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0
            ]
        ];
    }

    /**
     * Cross-selling opportunities analysis
     */
    private function getCrossSellingOpportunities($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        // Market basket analysis - categories frequently bought together
        $basket_query = DB::table('transactions as t1')
            ->join('transaction_sell_lines as tsl1', 't1.id', '=', 'tsl1.transaction_id')
            ->join('variations as v1', 'tsl1.variation_id', '=', 'v1.id')
            ->join('products as p1', 'v1.product_id', '=', 'p1.id')
            ->join('categories as c1', 'p1.category_id', '=', 'c1.id')
            ->join('transaction_sell_lines as tsl2', 't1.id', '=', 'tsl2.transaction_id')
            ->join('variations as v2', 'tsl2.variation_id', '=', 'v2.id')
            ->join('products as p2', 'v2.product_id', '=', 'p2.id')
            ->join('categories as c2', 'p2.category_id', '=', 'c2.id')
            ->where('t1.business_id', $business_id)
            ->where('t1.type', 'sell')
            ->where('t1.status', 'final')
            ->whereBetween('t1.transaction_date', [$start_date, $end_date])
            ->where('c1.id', '<>', DB::raw('c2.id')); // Different categories

        if (!empty($location_ids)) {
            $basket_query->whereIn('t1.location_id', $location_ids);
        }

        $market_basket = $basket_query->select([
            'c1.id as category_a_id',
            'c1.name as category_a_name',
            'c2.id as category_b_id', 
            'c2.name as category_b_name',
            DB::raw('COUNT(DISTINCT t1.id) as co_occurrence_count'),
            DB::raw('SUM(tsl1.unit_price_inc_tax * tsl1.quantity + tsl2.unit_price_inc_tax * tsl2.quantity) as combined_value')
        ])
        ->groupBy('c1.id', 'c1.name', 'c2.id', 'c2.name')
        ->having('co_occurrence_count', '>=', 3) // Minimum 3 co-occurrences
        ->orderBy('co_occurrence_count', 'desc')
        ->limit(20)
        ->get();

        // Calculate confidence and lift for association rules
        foreach ($market_basket as $basket) {
            // Get individual category transaction counts
            $category_a_count = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('p.category_id', $basket->category_a_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereBetween('t.transaction_date', [$start_date, $end_date])
                ->count();

            $total_transactions = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start_date, $end_date])
                ->count();

            $basket->confidence = $category_a_count > 0 ? ($basket->co_occurrence_count / $category_a_count) * 100 : 0;
            $basket->avg_basket_value = $basket->co_occurrence_count > 0 ? $basket->combined_value / $basket->co_occurrence_count : 0;
        }

        // Category affinity analysis
        $category_affinity = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('COUNT(DISTINCT t.contact_id) as unique_customers'),
                DB::raw('AVG(tsl.quantity) as avg_quantity_per_transaction'),
                DB::raw('COUNT(DISTINCT t.id) as purchase_frequency')
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('unique_customers', 'desc')
            ->get();

        return [
            'market_basket' => $market_basket,
            'category_affinity' => $category_affinity,
            'cross_sell_recommendations' => $market_basket->take(10)
        ];
    }

    /**
     * Margin analysis by category
     */
    private function getMarginAnalysByCategory($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        if (!empty($category_ids)) {
            $query->whereIn('c.id', $category_ids);
        }

        $margin_data = $query->select([
            'c.id as category_id',
            'c.name as category_name',
            DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as revenue'),
            DB::raw('SUM(COALESCE(v.dpp_inc_tax, 0) * tsl.quantity) as cost'),
            DB::raw('SUM((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) * tsl.quantity) as gross_profit'),
            DB::raw('MIN((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) / NULLIF(tsl.unit_price_inc_tax, 0) * 100) as min_margin'),
            DB::raw('MAX((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) / NULLIF(tsl.unit_price_inc_tax, 0) * 100) as max_margin'),
            DB::raw('AVG((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) / NULLIF(tsl.unit_price_inc_tax, 0) * 100) as avg_margin'),
            DB::raw('COUNT(DISTINCT p.id) as product_count')
        ])
        ->groupBy('c.id', 'c.name')
        ->orderBy('gross_profit', 'desc')
        ->get();

        // Calculate margin percentages and performance metrics
        foreach ($margin_data as $category) {
            $category->margin_percentage = $category->revenue > 0 ? ($category->gross_profit / $category->revenue) * 100 : 0;
            $category->roi = $category->cost > 0 ? ($category->gross_profit / $category->cost) * 100 : 0;
            
            // Margin consistency (lower standard deviation = more consistent)
            $category->margin_consistency = abs($category->max_margin - $category->min_margin);
            
            // Performance score (combines profitability and volume)
            $category->performance_score = ($category->margin_percentage * 0.6) + (($category->revenue / 1000) * 0.4);
        }

        // Margin benchmarks
        $avg_margin = $margin_data->avg('margin_percentage');
        $high_margin_categories = $margin_data->filter(function($cat) use ($avg_margin) {
            return $cat->margin_percentage > ($avg_margin * 1.2);
        });
        
        $low_margin_categories = $margin_data->filter(function($cat) use ($avg_margin) {
            return $cat->margin_percentage < ($avg_margin * 0.8);
        });

        return [
            'margin_data' => $margin_data,
            'benchmarks' => [
                'avg_margin' => $avg_margin,
                'total_revenue' => $margin_data->sum('revenue'),
                'total_profit' => $margin_data->sum('gross_profit'),
                'high_margin_count' => $high_margin_categories->count(),
                'low_margin_count' => $low_margin_categories->count()
            ],
            'high_margin_categories' => $high_margin_categories->take(5),
            'low_margin_categories' => $low_margin_categories->take(5)
        ];
    }

    /**
     * Category growth trends
     */
    private function getCategoryGrowthTrends($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        // Monthly growth trends
        $monthly_trends = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as monthly_sales'),
                DB::raw('SUM(tsl.quantity) as monthly_quantity'),
                DB::raw('COUNT(DISTINCT t.id) as monthly_transactions')
            ])
            ->groupBy('c.id', 'c.name', DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m")'))
            ->orderBy('c.name')
            ->orderBy('month')
            ->get();

        // Calculate growth rates
        $growth_analysis = [];
        foreach ($monthly_trends->groupBy('category_id') as $category_id => $months) {
            $months = $months->sortBy('month');
            $growth_rates = [];
            
            for ($i = 1; $i < $months->count(); $i++) {
                $current = $months->values()[$i];
                $previous = $months->values()[$i-1];
                
                $growth_rate = $previous->monthly_sales > 0 
                    ? (($current->monthly_sales - $previous->monthly_sales) / $previous->monthly_sales) * 100 
                    : 0;
                    
                $growth_rates[] = $growth_rate;
            }
            
            $category_name = $months->first()->category_name;
            $avg_growth = count($growth_rates) > 0 ? array_sum($growth_rates) / count($growth_rates) : 0;
            
            $growth_analysis[$category_id] = [
                'category_name' => $category_name,
                'avg_monthly_growth' => $avg_growth,
                'total_months' => $months->count(),
                'trend_direction' => $avg_growth > 0 ? 'growing' : ($avg_growth < 0 ? 'declining' : 'stable'),
                'monthly_data' => $months
            ];
        }

        return [
            'monthly_trends' => $monthly_trends->groupBy('category_name'),
            'growth_analysis' => $growth_analysis,
            'trend_summary' => [
                'growing_categories' => collect($growth_analysis)->where('trend_direction', 'growing')->count(),
                'declining_categories' => collect($growth_analysis)->where('trend_direction', 'declining')->count(),
                'stable_categories' => collect($growth_analysis)->where('trend_direction', 'stable')->count(),
            ]
        ];
    }

    /**
     * Seasonal patterns analysis
     */
    private function getSeasonalPatterns($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }

        if (!empty($category_ids)) {
            $query->whereIn('c.id', $category_ids);
        }

        if (!empty($brand_ids)) {
            $query->whereIn('b.id', $brand_ids);
        }

        // Get monthly patterns
        $monthly_data = $query->clone()
            ->select([
                'c.name as category_name',
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('MONTHNAME(t.transaction_date) as month_name'),
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as sales'),
                DB::raw('SUM(tsl.quantity) as quantity')
            ])
            ->groupBy('c.name', DB::raw('MONTH(t.transaction_date)'), DB::raw('MONTHNAME(t.transaction_date)'))
            ->get();

        // Add debug logging
        \Log::info('Seasonal patterns monthly data count: ' . $monthly_data->count());
        \Log::info('Seasonal patterns monthly sample: ' . $monthly_data->take(5)->toJson());
        
        // Create monthly patterns grouped by category
        $monthly_patterns = [];
        foreach ($monthly_data as $record) {
            $monthly_patterns[$record->category_name][$record->month] = $record->sales;
        }
        
        \Log::info('Processed monthly patterns: ' . json_encode($monthly_patterns));

        // For now, focus on monthly patterns only
        return [
            'monthly_patterns' => $monthly_patterns,
            'daily_patterns' => [],
            'hourly_patterns' => [],
            'peak_analysis' => [
                'peak_month' => null,
                'peak_day' => null,
                'peak_hour' => null
            ]
        ];
    }

    /**
     * Inventory turnover by category
     */
    private function getInventoryTurnoverByCategory($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [])
    {
        $turnover_data = DB::table('products as p')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('variation_location_details as vld', function($join) use ($location_ids) {
                $join->on('v.id', '=', 'vld.variation_id');
                if (!empty($location_ids)) {
                    $join->whereIn('vld.location_id', $location_ids);
                }
            })
            ->leftJoin('transaction_sell_lines as tsl', function($join) use ($start_date, $end_date) {
                $join->on('v.id', '=', 'tsl.variation_id')
                     ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                     ->where('t.type', 'sell')
                     ->where('t.status', 'final')
                     ->whereBetween('t.transaction_date', [$start_date, $end_date]);
            })
            ->where('p.business_id', $business_id)
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('SUM(COALESCE(vld.qty_available, 0)) as current_stock'),
                DB::raw('SUM(COALESCE(tsl.quantity, 0)) as quantity_sold'),
                DB::raw('AVG(COALESCE(vld.qty_available, 0)) as avg_stock_level'),
                DB::raw('COUNT(DISTINCT p.id) as product_count'),
                DB::raw('SUM(COALESCE(tsl.unit_price_inc_tax * tsl.quantity, 0)) as sales_value')
            ])
            ->groupBy('c.id', 'c.name')
            ->get();

        // Calculate turnover metrics
        foreach ($turnover_data as $category) {
            // Days between start and end date
            $days = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date));
            $days = max($days, 1);

            // Inventory turnover ratio = Cost of Goods Sold / Average Inventory
            $category->turnover_ratio = $category->avg_stock_level > 0 
                ? $category->quantity_sold / $category->avg_stock_level 
                : 0;
            
            // Days to sell inventory
            $category->days_to_sell = $category->turnover_ratio > 0 
                ? $days / $category->turnover_ratio 
                : 999;
                
            // Stock velocity (units sold per day)
            $category->stock_velocity = $category->quantity_sold / $days;
            
            // Stock efficiency score
            $category->efficiency_score = $category->turnover_ratio * 10; // Scale for display
        }

        return [
            'turnover_data' => $turnover_data->sortByDesc('turnover_ratio'),
            'summary' => [
                'avg_turnover_ratio' => $turnover_data->avg('turnover_ratio'),
                'total_categories' => $turnover_data->count(),
                'fast_moving' => $turnover_data->where('turnover_ratio', '>', 2)->count(),
                'slow_moving' => $turnover_data->where('turnover_ratio', '<', 0.5)->count()
            ]
        ];
    }

    /**
     * Top performing categories
     */
    private function getTopPerformingCategories($business_id, $start_date, $end_date, $location_ids = [], $brand_ids = [])
    {
        return DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_sales'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Category comparison analysis
     */
    private function getCategoryComparison($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [])
    {
        $comparison_data = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as revenue'),
                DB::raw('SUM((tsl.unit_price_inc_tax - COALESCE(v.dpp_inc_tax, 0)) * tsl.quantity) as profit'),
                DB::raw('COUNT(DISTINCT t.contact_id) as unique_customers'),
                DB::raw('AVG(tsl.unit_price_inc_tax) as avg_price'),
                DB::raw('SUM(tsl.quantity) as units_sold')
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('revenue', 'desc')
            ->get();

        foreach ($comparison_data as $category) {
            $category->profit_margin = $category->revenue > 0 ? ($category->profit / $category->revenue) * 100 : 0;
            $category->revenue_per_customer = $category->unique_customers > 0 ? $category->revenue / $category->unique_customers : 0;
        }

        return $comparison_data;
    }

    /**
     * Export category performance data
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $location_ids = $request->get('location_ids', []);
        $category_ids = $request->get('category_ids', []);

        // Get comprehensive category data
        $category_data = $this->getCategoryContribution($business_id, $start_date, $end_date, $location_ids, $category_ids);
        $margin_data = $this->getMarginAnalysByCategory($business_id, $start_date, $end_date, $location_ids, $category_ids);
        $turnover_data = $this->getInventoryTurnoverByCategory($business_id, $start_date, $end_date, $location_ids, $category_ids);

        // Prepare CSV data
        $csvData = [];
        $csvData[] = [
            __('advancedreports::lang.category'),
            __('advancedreports::lang.revenue'),
            __('advancedreports::lang.gross_profit'),
            __('advancedreports::lang.profit_margin') . ' (%)',
            __('advancedreports::lang.sales_contribution') . ' (%)',
            __('advancedreports::lang.profit_contribution') . ' (%)',
            __('advancedreports::lang.units_sold'),
            __('advancedreports::lang.transactions'),
            __('advancedreports::lang.avg_transaction_value'),
            __('advancedreports::lang.turnover_ratio'),
            __('advancedreports::lang.days_to_sell'),
            __('advancedreports::lang.stock_velocity')
        ];

        // Merge data by category
        $merged_data = [];
        foreach ($category_data['categories'] as $category) {
            $merged_data[$category->category_id] = [
                'name' => $category->category_name,
                'revenue' => $category->total_sales,
                'profit' => $category->gross_profit,
                'margin' => $category->profit_margin,
                'sales_contribution' => $category->sales_contribution,
                'profit_contribution' => $category->profit_contribution,
                'units_sold' => $category->total_quantity,
                'transactions' => $category->transaction_count,
                'avg_transaction_value' => $category->avg_transaction_value,
                'turnover_ratio' => 0,
                'days_to_sell' => 0,
                'stock_velocity' => 0
            ];
        }

        // Add turnover data
        foreach ($turnover_data['turnover_data'] as $turnover) {
            if (isset($merged_data[$turnover->category_id])) {
                $merged_data[$turnover->category_id]['turnover_ratio'] = $turnover->turnover_ratio;
                $merged_data[$turnover->category_id]['days_to_sell'] = $turnover->days_to_sell;
                $merged_data[$turnover->category_id]['stock_velocity'] = $turnover->stock_velocity;
            }
        }

        // Add data rows
        foreach ($merged_data as $data) {
            $csvData[] = [
                $data['name'],
                number_format($data['revenue'], 2),
                number_format($data['profit'], 2),
                number_format($data['margin'], 2),
                number_format($data['sales_contribution'], 2),
                number_format($data['profit_contribution'], 2),
                number_format($data['units_sold'], 0),
                number_format($data['transactions'], 0),
                number_format($data['avg_transaction_value'], 2),
                number_format($data['turnover_ratio'], 2),
                number_format($data['days_to_sell'], 1),
                number_format($data['stock_velocity'], 2)
            ];
        }

        // Generate filename
        $filename = 'product_category_performance_' . $start_date . '_to_' . $end_date . '.csv';

        // Create CSV response
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}