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
use App\Transaction;
use App\User;
use Carbon\Carbon;

class LocationPerformanceController extends Controller
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
     * Display location performance analysis
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $locations = BusinessLocation::forDropdown($business_id, false);
        $business_locations = [];
        if ($locations) {
            foreach ($locations as $key => $value) {
                $business_locations[$key] = $value;
            }
        }

        // Get categories for filtering
        $categories = Category::forDropdown($business_id, 'product');
        $categories = collect(['all' => __('All Categories')])->merge($categories)->toArray();

        // Get users (staff) for performance analysis
        $staff = User::forDropdown($business_id, false, false);

        $data = compact('business_locations', 'categories', 'staff');

        return view('advancedreports::location-performance.index', $data);
    }

    /**
     * Get location performance analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $start_date = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $selected_locations = $request->get('location_ids', []);
        $category_id = $request->get('category_id');
        $compare_period = $request->get('compare_period', 'previous_period');

        return response()->json([
            'location_comparison' => $this->getLocationComparison($business_id, $start_date, $end_date, $selected_locations, $category_id),
            'performance_benchmarks' => $this->getPerformanceBenchmarks($business_id, $start_date, $end_date, $selected_locations, $compare_period),
            'regional_sales' => $this->getRegionalSalesAnalysis($business_id, $start_date, $end_date, $selected_locations, $category_id),
            'location_profitability' => $this->getLocationProfitability($business_id, $start_date, $end_date, $selected_locations),
            'performance_trends' => $this->getPerformanceTrends($business_id, $start_date, $end_date, $selected_locations),
            'staff_performance' => $this->getStaffPerformanceByLocation($business_id, $start_date, $end_date, $selected_locations)
        ]);
    }

    /**
     * Multi-location comparison
     */
    private function getLocationComparison($business_id, $start_date, $end_date, $selected_locations = [], $category_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        if ($category_id && $category_id !== 'all') {
            $query->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                  ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                  ->join('products as p', 'v.product_id', '=', 'p.id')
                  ->where('p.category_id', $category_id);
        }

        $location_data = $query->select([
            'bl.id as location_id',
            'bl.name as location_name',
            'bl.city',
            'bl.state',
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('SUM(t.final_total) as total_sales'),
            DB::raw('AVG(t.final_total) as avg_transaction_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as unique_customers'),
            DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as active_days')
        ])
        ->groupBy('bl.id', 'bl.name', 'bl.city', 'bl.state')
        ->get();

        // Calculate additional metrics
        $location_comparison = $location_data->map(function($location) use ($business_id, $start_date, $end_date) {
            $days_in_period = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;
            
            // Get profit margins
            $profit_data = $this->getLocationProfit($business_id, $location->location_id, $start_date, $end_date);
            
            // Calculate performance metrics
            $daily_avg_sales = $location->total_sales / $days_in_period;
            $customer_retention = $this->getCustomerRetention($business_id, $location->location_id, $start_date, $end_date);
            
            return [
                'location_id' => $location->location_id,
                'location_name' => $location->location_name,
                'city' => $location->city,
                'state' => $location->state,
                'total_sales' => $location->total_sales,
                'total_transactions' => $location->total_transactions,
                'avg_transaction_value' => $location->avg_transaction_value,
                'unique_customers' => $location->unique_customers,
                'daily_avg_sales' => $daily_avg_sales,
                'active_days' => $location->active_days,
                'gross_profit' => $profit_data['gross_profit'],
                'profit_margin' => $profit_data['profit_margin'],
                'customer_retention_rate' => $customer_retention,
                'sales_per_customer' => $location->unique_customers > 0 ? $location->total_sales / $location->unique_customers : 0,
                'transactions_per_day' => $location->active_days > 0 ? $location->total_transactions / $location->active_days : 0
            ];
        });

        return [
            'locations' => $location_comparison,
            'totals' => [
                'total_sales' => $location_comparison->sum('total_sales'),
                'total_transactions' => $location_comparison->sum('total_transactions'),
                'total_customers' => $location_comparison->sum('unique_customers'),
                'avg_profit_margin' => $location_comparison->avg('profit_margin')
            ]
        ];
    }

    /**
     * Performance benchmarking
     */
    private function getPerformanceBenchmarks($business_id, $start_date, $end_date, $selected_locations = [], $compare_period = 'previous_period')
    {
        // Calculate benchmark period based on comparison type
        $period_diff = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;
        
        switch ($compare_period) {
            case 'previous_period':
                $benchmark_start = Carbon::parse($start_date)->subDays($period_diff)->format('Y-m-d');
                $benchmark_end = Carbon::parse($start_date)->subDay()->format('Y-m-d');
                break;
            case 'same_period_last_year':
                $benchmark_start = Carbon::parse($start_date)->subYear()->format('Y-m-d');
                $benchmark_end = Carbon::parse($end_date)->subYear()->format('Y-m-d');
                break;
            default:
                $benchmark_start = Carbon::parse($start_date)->subDays($period_diff)->format('Y-m-d');
                $benchmark_end = Carbon::parse($start_date)->subDay()->format('Y-m-d');
        }

        $current_metrics = $this->getLocationMetrics($business_id, $start_date, $end_date, $selected_locations);
        $benchmark_metrics = $this->getLocationMetrics($business_id, $benchmark_start, $benchmark_end, $selected_locations);

        $benchmarks = [];
        foreach ($current_metrics as $location_id => $current) {
            $benchmark = $benchmark_metrics[$location_id] ?? [];
            
            $benchmarks[$location_id] = [
                'location_name' => $current['location_name'],
                'sales_growth' => $this->calculateGrowth($benchmark['total_sales'] ?? 0, $current['total_sales']),
                'transaction_growth' => $this->calculateGrowth($benchmark['total_transactions'] ?? 0, $current['total_transactions']),
                'customer_growth' => $this->calculateGrowth($benchmark['unique_customers'] ?? 0, $current['unique_customers']),
                'avg_transaction_growth' => $this->calculateGrowth($benchmark['avg_transaction_value'] ?? 0, $current['avg_transaction_value']),
                'profit_margin_change' => ($current['profit_margin'] ?? 0) - ($benchmark['profit_margin'] ?? 0),
                'current_period' => $current,
                'benchmark_period' => $benchmark,
                'performance_score' => $this->calculatePerformanceScore($current, $benchmark)
            ];
        }

        return [
            'benchmarks' => $benchmarks,
            'period_info' => [
                'current_period' => ['start' => $start_date, 'end' => $end_date],
                'benchmark_period' => ['start' => $benchmark_start, 'end' => $benchmark_end],
                'comparison_type' => $compare_period
            ]
        ];
    }

    /**
     * Regional sales analysis
     */
    private function getRegionalSalesAnalysis($business_id, $start_date, $end_date, $selected_locations = [], $category_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        // Regional analysis by state/city
        $regional_data = $query->select([
            'bl.state',
            'bl.city',
            DB::raw('COUNT(DISTINCT bl.id) as location_count'),
            DB::raw('SUM(t.final_total) as regional_sales'),
            DB::raw('COUNT(t.id) as regional_transactions'),
            DB::raw('AVG(t.final_total) as avg_transaction_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as regional_customers')
        ])
        ->groupBy('bl.state', 'bl.city')
        ->orderBy('regional_sales', 'desc')
        ->get();

        // Get top performing regions
        $top_regions = $regional_data->take(10);
        
        // Get sales trends by region over time
        $regional_trends = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $regional_trends->whereIn('t.location_id', $selected_locations);
        }

        $trends_data = $regional_trends->select([
            'bl.state',
            'bl.city',
            DB::raw('YEAR(t.transaction_date) as year'),
            DB::raw('MONTH(t.transaction_date) as month'),
            DB::raw('SUM(t.final_total) as monthly_sales')
        ])
        ->groupBy('bl.state', 'bl.city', 'year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        // Calculate market share by region
        $total_sales = $regional_data->sum('regional_sales');
        $market_share = $regional_data->map(function($region) use ($total_sales) {
            return [
                'region' => $region->city . ', ' . $region->state,
                'sales' => $region->regional_sales,
                'market_share' => $total_sales > 0 ? ($region->regional_sales / $total_sales) * 100 : 0,
                'location_count' => $region->location_count,
                'avg_per_location' => $region->location_count > 0 ? $region->regional_sales / $region->location_count : 0
            ];
        });

        return [
            'regional_summary' => $regional_data,
            'top_regions' => $top_regions,
            'market_share' => $market_share,
            'trends' => $trends_data->groupBy(function($item) {
                return $item->city . ', ' . $item->state;
            })->map(function($regionTrends) {
                return $regionTrends->map(function($trend) {
                    return [
                        'period' => Carbon::createFromDate($trend->year, $trend->month, 1)->format('M Y'),
                        'sales' => $trend->monthly_sales
                    ];
                });
            })
        ];
    }

    /**
     * Location profitability analysis
     */
    private function getLocationProfitability($business_id, $start_date, $end_date, $selected_locations = [])
    {
        $profitability = [];

        $locations = !empty($selected_locations) 
            ? BusinessLocation::whereIn('id', $selected_locations)->where('business_id', $business_id)->get()
            : BusinessLocation::where('business_id', $business_id)->get();

        foreach ($locations as $location) {
            $location_profit = $this->calculateLocationProfitability($business_id, $location->id, $start_date, $end_date);
            $profitability[] = array_merge($location_profit, [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'city' => $location->city,
                'state' => $location->state
            ]);
        }

        // Sort by profitability
        usort($profitability, function($a, $b) {
            return $b['net_profit'] <=> $a['net_profit'];
        });

        return [
            'location_profitability' => $profitability,
            'profitability_ranking' => array_values($profitability),
            'average_metrics' => [
                'avg_gross_profit_margin' => collect($profitability)->avg('gross_profit_margin'),
                'avg_net_profit_margin' => collect($profitability)->avg('net_profit_margin'),
                'avg_roi' => collect($profitability)->avg('roi')
            ]
        ];
    }

    /**
     * Performance trends over time
     */
    private function getPerformanceTrends($business_id, $start_date, $end_date, $selected_locations = [])
    {
        $query = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        $trends = $query->select([
            'bl.id as location_id',
            'bl.name as location_name',
            DB::raw('YEAR(t.transaction_date) as year'),
            DB::raw('MONTH(t.transaction_date) as month'),
            DB::raw('SUM(t.final_total) as monthly_sales'),
            DB::raw('COUNT(t.id) as monthly_transactions'),
            DB::raw('AVG(t.final_total) as avg_transaction_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as monthly_customers')
        ])
        ->groupBy('bl.id', 'bl.name', 'year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        return $trends->groupBy('location_id')->map(function($locationTrends, $locationId) {
            $location_name = $locationTrends->first()->location_name;
            return [
                'location_name' => $location_name,
                'trends' => $locationTrends->map(function($trend) {
                    return [
                        'period' => Carbon::createFromDate($trend->year, $trend->month, 1)->format('M Y'),
                        'sales' => $trend->monthly_sales,
                        'transactions' => $trend->monthly_transactions,
                        'avg_transaction_value' => $trend->avg_transaction_value,
                        'customers' => $trend->monthly_customers
                    ];
                })->values()
            ];
        });
    }

    /**
     * Staff performance by location
     */
    private function getStaffPerformanceByLocation($business_id, $start_date, $end_date, $selected_locations = [])
    {
        $query = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        $staff_performance = $query->select([
            'bl.id as location_id',
            'bl.name as location_name',
            'u.id as staff_id',
            'u.first_name',
            'u.last_name',
            DB::raw('COUNT(t.id) as total_sales'),
            DB::raw('SUM(t.final_total) as total_amount'),
            DB::raw('AVG(t.final_total) as avg_sale_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as customers_served')
        ])
        ->groupBy('bl.id', 'bl.name', 'u.id', 'u.first_name', 'u.last_name')
        ->orderBy('total_amount', 'desc')
        ->get();

        return $staff_performance->groupBy('location_id')->map(function($locationStaff, $locationId) {
            return [
                'location_name' => $locationStaff->first()->location_name,
                'staff' => $locationStaff->map(function($staff) {
                    return [
                        'staff_id' => $staff->staff_id,
                        'staff_name' => $staff->first_name . ' ' . $staff->last_name,
                        'total_sales' => $staff->total_sales,
                        'total_amount' => $staff->total_amount,
                        'avg_sale_value' => $staff->avg_sale_value,
                        'customers_served' => $staff->customers_served,
                        'sales_per_customer' => $staff->customers_served > 0 ? $staff->total_amount / $staff->customers_served : 0
                    ];
                })->values()
            ];
        });
    }

    // Helper methods

    private function getLocationProfit($business_id, $location_id, $start_date, $end_date)
    {
        $sales_data = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_sales'),
                DB::raw('SUM(tsl.quantity * v.dpp_inc_tax) as total_cost')
            ])
            ->first();

        $total_sales = $sales_data->total_sales ?? 0;
        $total_cost = $sales_data->total_cost ?? 0;
        $gross_profit = $total_sales - $total_cost;
        $profit_margin = $total_sales > 0 ? ($gross_profit / $total_sales) * 100 : 0;

        return [
            'total_sales' => $total_sales,
            'total_cost' => $total_cost,
            'gross_profit' => $gross_profit,
            'profit_margin' => $profit_margin
        ];
    }

    private function getCustomerRetention($business_id, $location_id, $start_date, $end_date)
    {
        // Calculate retention based on repeat customers
        $total_customers = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->distinct('contact_id')
            ->count();

        $repeat_customers = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->groupBy('contact_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return $total_customers > 0 ? ($repeat_customers / $total_customers) * 100 : 0;
    }

    private function getLocationMetrics($business_id, $start_date, $end_date, $selected_locations = [])
    {
        $query = DB::table('transactions as t')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        $metrics = $query->select([
            'bl.id as location_id',
            'bl.name as location_name',
            DB::raw('COUNT(t.id) as total_transactions'),
            DB::raw('SUM(t.final_total) as total_sales'),
            DB::raw('AVG(t.final_total) as avg_transaction_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as unique_customers')
        ])
        ->groupBy('bl.id', 'bl.name')
        ->get();

        $result = [];
        foreach ($metrics as $metric) {
            $profit_data = $this->getLocationProfit($business_id, $metric->location_id, $start_date, $end_date);
            $result[$metric->location_id] = array_merge([
                'location_name' => $metric->location_name,
                'total_transactions' => $metric->total_transactions,
                'total_sales' => $metric->total_sales,
                'avg_transaction_value' => $metric->avg_transaction_value,
                'unique_customers' => $metric->unique_customers
            ], $profit_data);
        }

        return $result;
    }

    private function calculateGrowth($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function calculatePerformanceScore($current, $benchmark)
    {
        $score = 0;
        $weight_sales = 0.3;
        $weight_transactions = 0.2;
        $weight_customers = 0.2;
        $weight_margin = 0.3;

        // Sales growth score
        $sales_growth = $this->calculateGrowth($benchmark['total_sales'] ?? 0, $current['total_sales']);
        $score += $weight_sales * min(100, max(0, 50 + $sales_growth));

        // Transaction growth score
        $transaction_growth = $this->calculateGrowth($benchmark['total_transactions'] ?? 0, $current['total_transactions']);
        $score += $weight_transactions * min(100, max(0, 50 + $transaction_growth));

        // Customer growth score
        $customer_growth = $this->calculateGrowth($benchmark['unique_customers'] ?? 0, $current['unique_customers']);
        $score += $weight_customers * min(100, max(0, 50 + $customer_growth));

        // Profit margin score
        $margin_score = min(100, max(0, ($current['profit_margin'] ?? 0) * 2));
        $score += $weight_margin * $margin_score;

        return round($score, 1);
    }

    private function calculateLocationProfitability($business_id, $location_id, $start_date, $end_date)
    {
        // Get revenue and cost data
        $financial_data = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as revenue'),
                DB::raw('SUM(tsl.quantity * v.dpp_inc_tax) as cogs'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            ])
            ->first();

        // Get operational expenses (simplified calculation)
        $expenses = $this->getLocationExpenses($business_id, $location_id, $start_date, $end_date);

        $revenue = $financial_data->revenue ?? 0;
        $cogs = $financial_data->cogs ?? 0;
        $gross_profit = $revenue - $cogs;
        $net_profit = $gross_profit - $expenses;
        
        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $gross_profit,
            'expenses' => $expenses,
            'net_profit' => $net_profit,
            'gross_profit_margin' => $revenue > 0 ? ($gross_profit / $revenue) * 100 : 0,
            'net_profit_margin' => $revenue > 0 ? ($net_profit / $revenue) * 100 : 0,
            'roi' => $expenses > 0 ? ($net_profit / $expenses) * 100 : 0,
            'transaction_count' => $financial_data->transaction_count ?? 0
        ];
    }

    private function getLocationExpenses($business_id, $location_id, $start_date, $end_date)
    {
        // Get expenses for this location (if expense system tracks location)
        $expenses = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'expense')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->where(function($query) use ($location_id) {
                $query->where('location_id', $location_id)
                      ->orWhereNull('location_id'); // Include general expenses
            })
            ->sum('final_total');

        return $expenses ?? 0;
    }
}