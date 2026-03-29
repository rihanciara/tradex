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
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdvancedReports\Exports\CustomerBehaviorExport;

class CustomerBehaviorController extends Controller
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
     * Display customer behavior report index page
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $locations = BusinessLocation::forDropdown($business_id, true);
        $business_locations = ['all' => __('All')];
        if ($locations) {
            foreach ($locations as $key => $value) {
                $business_locations[$key] = $value;
            }
        }
        
        // Get customers
        $customers = Contact::customersDropdown($business_id, false);
        $customers = collect(['all' => __('All Customers')])->merge($customers)->toArray();
        
        // Get categories
        $categories = Category::forDropdown($business_id, 'product');
        $categories = collect(['all' => __('All Categories')])->merge($categories)->toArray();

        $data = compact('business_locations', 'customers', 'categories');
        
        return view('advancedreports::customer-behavior.index', $data);
    }

    /**
     * Get customer behavior analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $customer_id = $request->get('customer_id');

        // Set default date range if not provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subYear()->format('Y-m-d');
        }

        return response()->json([
            'purchase_patterns' => $this->getPurchasePatterns($business_id, $start_date, $end_date, $location_id, $customer_id),
            'category_preferences' => $this->getCategoryPreferences($business_id, $start_date, $end_date, $location_id, $customer_id),
            'order_value_trends' => $this->getOrderValueTrends($business_id, $start_date, $end_date, $location_id, $customer_id),
            'satisfaction_metrics' => $this->getSatisfactionMetrics($business_id, $start_date, $end_date, $location_id, $customer_id),
            'summary_cards' => $this->getSummaryCards($business_id, $start_date, $end_date, $location_id, $customer_id),
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Get purchase patterns by time/season
     */
    private function getPurchasePatterns($business_id, $start_date, $end_date, $location_id = null, $customer_id = null)
    {
        // Hourly patterns
        $hourly_patterns = $this->getHourlyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id);
        
        // Daily patterns (Monday to Sunday)
        $daily_patterns = $this->getDailyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id);
        
        // Monthly patterns
        $monthly_patterns = $this->getMonthlyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id);
        
        // Seasonal patterns
        $seasonal_patterns = $this->getSeasonalPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id);

        return [
            'hourly' => $hourly_patterns,
            'daily' => $daily_patterns,
            'monthly' => $monthly_patterns,
            'seasonal' => $seasonal_patterns
        ];
    }

    private function getHourlyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $hourly_data = $query->selectRaw('
                HOUR(t.transaction_date) as hour,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_amount,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy(DB::raw('HOUR(t.transaction_date)'))
            ->orderBy('hour')
            ->get();

        // Fill missing hours with zeros
        $complete_data = [];
        for ($i = 0; $i < 24; $i++) {
            $existing = $hourly_data->firstWhere('hour', $i);
            $complete_data[] = [
                'hour' => $i,
                'hour_label' => sprintf('%02d:00', $i),
                'transaction_count' => $existing ? $existing->transaction_count : 0,
                'total_amount' => $existing ? $existing->total_amount : 0,
                'avg_order_value' => $existing ? $existing->avg_order_value : 0
            ];
        }

        return $complete_data;
    }

    private function getDailyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $daily_data = $query->selectRaw('
                DAYOFWEEK(t.transaction_date) as day_of_week,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_amount,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy(DB::raw('DAYOFWEEK(t.transaction_date)'))
            ->orderBy('day_of_week')
            ->get();

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $complete_data = [];
        
        for ($i = 1; $i <= 7; $i++) {
            $existing = $daily_data->firstWhere('day_of_week', $i);
            $complete_data[] = [
                'day_of_week' => $i,
                'day_name' => $days[$i - 1],
                'transaction_count' => $existing ? $existing->transaction_count : 0,
                'total_amount' => $existing ? $existing->total_amount : 0,
                'avg_order_value' => $existing ? $existing->avg_order_value : 0
            ];
        }

        return $complete_data;
    }

    private function getMonthlyPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        return $query->selectRaw('
                DATE_FORMAT(t.transaction_date, "%Y-%m") as month,
                MONTHNAME(t.transaction_date) as month_name,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_amount,
                AVG(t.final_total) as avg_order_value,
                COUNT(DISTINCT t.contact_id) as unique_customers
            ')
            ->groupBy(DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m")'), DB::raw('MONTHNAME(t.transaction_date)'))
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function getSeasonalPurchasePattern($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $seasonal_data = $query->selectRaw('
                CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring"
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END as season,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_amount,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy(DB::raw('CASE 
                    WHEN MONTH(t.transaction_date) IN (12, 1, 2) THEN "Winter"
                    WHEN MONTH(t.transaction_date) IN (3, 4, 5) THEN "Spring"
                    WHEN MONTH(t.transaction_date) IN (6, 7, 8) THEN "Summer"
                    ELSE "Fall"
                END'))
            ->get()
            ->toArray();

        return $seasonal_data;
    }

    /**
     * Get category preferences analytics
     */
    private function getCategoryPreferences($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $category_data = $query->selectRaw('
                COALESCE(c.name, "Uncategorized") as category_name,
                COUNT(DISTINCT t.id) as transaction_count,
                SUM(tsl.quantity) as total_quantity,
                SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_amount,
                AVG(tsl.unit_price_inc_tax * tsl.quantity) as avg_line_amount,
                COUNT(DISTINCT t.contact_id) as unique_customers
            ')
            ->groupBy('c.id', 'c.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Calculate percentages
        $total_amount = $category_data->sum('total_amount');
        $total_transactions = $category_data->sum('transaction_count');
        
        return $category_data->map(function($item) use ($total_amount, $total_transactions) {
            return [
                'category_name' => $item->category_name,
                'transaction_count' => $item->transaction_count,
                'total_quantity' => $item->total_quantity,
                'total_amount' => $item->total_amount,
                'avg_line_amount' => $item->avg_line_amount,
                'unique_customers' => $item->unique_customers,
                'amount_percentage' => $total_amount > 0 ? round(($item->total_amount / $total_amount) * 100, 2) : 0,
                'transaction_percentage' => $total_transactions > 0 ? round(($item->transaction_count / $total_transactions) * 100, 2) : 0
            ];
        })->toArray();
    }

    /**
     * Get average order value trends
     */
    private function getOrderValueTrends($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        // Weekly trends
        $weekly_trends = $query->clone()->selectRaw('
                YEARWEEK(t.transaction_date) as year_week,
                DATE(t.transaction_date - INTERVAL WEEKDAY(t.transaction_date) DAY) as week_start,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_amount,
                AVG(t.final_total) as avg_order_value,
                MIN(t.final_total) as min_order_value,
                MAX(t.final_total) as max_order_value
            ')
            ->groupBy(DB::raw('YEARWEEK(t.transaction_date)'), DB::raw('DATE(t.transaction_date - INTERVAL WEEKDAY(t.transaction_date) DAY)'))
            ->orderBy('year_week')
            ->get();

        // Customer segmentation by order value
        $customer_segments = $this->getCustomerOrderValueSegments($business_id, $start_date, $end_date, $location_id, $customer_id);

        return [
            'weekly_trends' => $weekly_trends->toArray(),
            'customer_segments' => $customer_segments
        ];
    }

    private function getCustomerOrderValueSegments($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $customer_data = $query->selectRaw('
                t.contact_id,
                COALESCE(c.name, "Walk-in Customer") as customer_name,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_spent,
                AVG(t.final_total) as avg_order_value,
                MIN(t.final_total) as min_order_value,
                MAX(t.final_total) as max_order_value
            ')
            ->groupBy('t.contact_id', 'c.name')
            ->havingRaw('COUNT(*) > 0')
            ->get();

        // Segment customers by AOV
        $segments = [
            'high_value' => $customer_data->where('avg_order_value', '>=', 500)->count(),
            'medium_value' => $customer_data->whereBetween('avg_order_value', [100, 499.99])->count(),
            'low_value' => $customer_data->where('avg_order_value', '<', 100)->count(),
        ];

        return [
            'segments' => $segments,
            'top_customers' => $customer_data->sortByDesc('avg_order_value')->take(10)->values()->toArray()
        ];
    }

    /**
     * Get customer satisfaction metrics
     */
    private function getSatisfactionMetrics($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        // Calculate satisfaction based on business metrics
        $repeat_customer_rate = $this->getRepeatCustomerRate($business_id, $start_date, $end_date, $location_id, $customer_id);
        $return_rate = $this->getReturnRate($business_id, $start_date, $end_date, $location_id, $customer_id);
        $customer_retention = $this->getCustomerRetention($business_id, $start_date, $end_date, $location_id, $customer_id);
        $purchase_frequency = $this->getPurchaseFrequencyMetrics($business_id, $start_date, $end_date, $location_id, $customer_id);

        // Calculate overall satisfaction score (0-100)
        $satisfaction_score = $this->calculateSatisfactionScore($repeat_customer_rate, $return_rate, $customer_retention);

        return [
            'satisfaction_score' => $satisfaction_score,
            'repeat_customer_rate' => $repeat_customer_rate,
            'return_rate' => $return_rate,
            'customer_retention' => $customer_retention,
            'purchase_frequency' => $purchase_frequency
        ];
    }

    private function getRepeatCustomerRate($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('t.contact_id')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $total_customers = $query->clone()->distinct('contact_id')->count('contact_id');
        $repeat_customers = $query->clone()
            ->selectRaw('contact_id, COUNT(*) as purchase_count')
            ->groupBy('contact_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return $total_customers > 0 ? round(($repeat_customers / $total_customers) * 100, 2) : 0;
    }

    private function getReturnRate($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $total_sales_query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        $returns_query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell_return')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $total_sales_query->where('t.location_id', $location_id);
            $returns_query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $total_sales_query->where('t.contact_id', $customer_id);
            $returns_query->where('t.contact_id', $customer_id);
        }

        $total_sales = $total_sales_query->count();
        $total_returns = $returns_query->count();

        return $total_sales > 0 ? round(($total_returns / $total_sales) * 100, 2) : 0;
    }

    private function getCustomerRetention($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        // Calculate customers who made purchases in both first and second half of the period
        $mid_date = Carbon::parse($start_date)->addDays(Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) / 2)->format('Y-m-d');

        $first_half_customers = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('t.contact_id')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<', $mid_date)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($customer_id && $customer_id != 'all', function($q) use ($customer_id) {
                return $q->where('t.contact_id', $customer_id);
            })
            ->distinct('contact_id')
            ->pluck('contact_id');

        $second_half_customers = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('t.contact_id')
            ->whereDate('t.transaction_date', '>=', $mid_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($customer_id && $customer_id != 'all', function($q) use ($customer_id) {
                return $q->where('t.contact_id', $customer_id);
            })
            ->distinct('contact_id')
            ->pluck('contact_id');

        $retained_customers = $first_half_customers->intersect($second_half_customers)->count();
        
        return $first_half_customers->count() > 0 ? round(($retained_customers / $first_half_customers->count()) * 100, 2) : 0;
    }

    private function getPurchaseFrequencyMetrics($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('t.contact_id')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $customer_frequencies = $query->selectRaw('
                contact_id,
                COUNT(*) as purchase_count,
                DATEDIFF(MAX(transaction_date), MIN(transaction_date)) as days_span
            ')
            ->groupBy('contact_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $avg_frequency = $customer_frequencies->avg('purchase_count') ?: 0;
        $avg_days_between = $customer_frequencies->where('days_span', '>', 0)->avg(function($item) {
            return $item->days_span / ($item->purchase_count - 1);
        }) ?: 0;

        return [
            'avg_purchases_per_customer' => round($avg_frequency, 2),
            'avg_days_between_purchases' => round($avg_days_between, 2),
            'total_repeat_customers' => $customer_frequencies->count()
        ];
    }

    private function calculateSatisfactionScore($repeat_rate, $return_rate, $retention_rate)
    {
        // Weighted satisfaction calculation
        $repeat_weight = 0.4;
        $return_weight = 0.3; // Lower return rate = higher satisfaction
        $retention_weight = 0.3;

        $repeat_score = min($repeat_rate, 100);
        $return_score = max(0, 100 - ($return_rate * 2)); // Penalize returns more heavily
        $retention_score = min($retention_rate, 100);

        $satisfaction = ($repeat_score * $repeat_weight) + 
                       ($return_score * $return_weight) + 
                       ($retention_score * $retention_weight);

        return round($satisfaction, 1);
    }

    /**
     * Get summary cards data
     */
    private function getSummaryCards($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        
        if ($customer_id && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $summary = $query->selectRaw('
                COUNT(*) as total_transactions,
                COUNT(DISTINCT t.contact_id) as unique_customers,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')->first();

        // Get most popular time
        $peak_hour = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($customer_id && $customer_id != 'all', function($q) use ($customer_id) {
                return $q->where('t.contact_id', $customer_id);
            })
            ->selectRaw('HOUR(t.transaction_date) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(t.transaction_date)'))
            ->orderBy('count', 'desc')
            ->first();

        return [
            'total_transactions' => $summary->total_transactions ?: 0,
            'unique_customers' => $summary->unique_customers ?: 0,
            'total_revenue' => $summary->total_revenue ?: 0,
            'avg_order_value' => $summary->avg_order_value ?: 0,
            'peak_hour' => $peak_hour ? sprintf('%02d:00', $peak_hour->hour) : 'N/A',
            'formatted_total_revenue' => $this->businessUtil->num_f($summary->total_revenue ?: 0, true),
            'formatted_avg_order_value' => $this->businessUtil->num_f($summary->avg_order_value ?: 0, true)
        ];
    }

    /**
     * Export customer behavior data to Excel
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');
            $customer_id = $request->get('customer_id');

            // Set default date range if not provided
            if (empty($start_date) || empty($end_date)) {
                $end_date = Carbon::now()->format('Y-m-d');
                $start_date = Carbon::now()->subYear()->format('Y-m-d');
            }

            // Prepare filters
            $filters = [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $location_id,
                'customer_id' => $customer_id
            ];

            $filename = 'customer_behavior_analysis_' . date('Y_m_d_H_i_s') . '.xlsx';

            return Excel::download(new CustomerBehaviorExport($business_id, $filters), $filename);
        } catch (\Exception $e) {
            \Log::error('Customer Behavior Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    public function testExport(Request $request)
    {
        $filename = 'test-export.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add BOM for proper UTF-8 handling in Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Test', 'Export', 'Working']);
            fputcsv($file, ['Hello', 'World', '123']);
            fputcsv($file, ['Date', date('Y-m-d'), 'Success']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}