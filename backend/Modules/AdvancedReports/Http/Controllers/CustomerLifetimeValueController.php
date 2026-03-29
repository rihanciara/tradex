<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;

class CustomerLifetimeValueController extends Controller
{
    protected $businessUtil;
    protected $moduleUtil;
    protected $commonUtil;

    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil, Util $commonUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.customer_lifetime_value')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('advancedreports::customer-lifetime-value.index', compact('business_locations', 'business'));
    }

    public function getCustomerLifetimeValueData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.customer_lifetime_value')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $customer_group_id = $request->get('customer_group_id');

        // Get customer analytics data
        $analytics = $this->getCustomerAnalytics($business_id, $start_date, $end_date, $location_id, $customer_group_id);

        return response()->json($analytics);
    }

    public function getCustomerSegmentationData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.customer_lifetime_value')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');

        $segmentation = $this->getCustomerValueSegmentation($business_id, $start_date, $end_date, $location_id);

        return DataTables::of($segmentation)
            ->addColumn('customer_name', function ($row) {
                return $row->customer_name ?: 'Walk-in Customer';
            })
            ->addColumn('segment', function ($row) {
                return $this->getCustomerSegment($row->clv_score, $row->purchase_frequency, $row->recency_days);
            })
            ->addColumn('formatted_total_spent', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->total_spent . '</span>';
            })
            ->addColumn('formatted_clv', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->clv_score . '</span>';
            })
            ->addColumn('last_purchase_date', function ($row) {
                return $row->last_purchase ? Carbon::parse($row->last_purchase)->format('M d, Y') : 'Never';
            })
            ->addColumn('risk_level', function ($row) {
                $risk = $this->getChurnRisk($row->recency_days, $row->purchase_frequency);
                $class = $risk === 'High' ? 'label-danger' : ($risk === 'Medium' ? 'label-warning' : 'label-success');
                return '<span class="label ' . $class . '">' . $risk . '</span>';
            })
            ->rawColumns(['formatted_total_spent', 'formatted_clv', 'risk_level'])
            ->make(true);
    }

    private function getCustomerAnalytics($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null)
    {
        // Base query for transactions
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final');

        if ($start_date && $end_date) {
            $query->whereBetween('transaction_date', [$start_date, $end_date]);
        }

        if ($location_id) {
            $query->where('location_id', $location_id);
        }

        // Customer segmentation data
        $segmentation = $this->getCustomerValueSegmentation($business_id, $start_date, $end_date, $location_id);
        
        // Calculate totals
        $total_customers = $segmentation->count();
        $total_revenue = $segmentation->sum('total_spent');
        $avg_clv = $segmentation->avg('clv_score');
        
        // Segment counts
        $segments = [
            'Champions' => 0,
            'Loyal Customers' => 0,
            'Potential Loyalists' => 0,
            'New Customers' => 0,
            'Promising' => 0,
            'Need Attention' => 0,
            'About to Sleep' => 0,
            'At Risk' => 0,
            'Cannot Lose Them' => 0,
            'Hibernating' => 0,
            'Lost' => 0
        ];

        foreach ($segmentation as $customer) {
            $segment = $this->getCustomerSegment($customer->clv_score, $customer->purchase_frequency, $customer->recency_days);
            if (isset($segments[$segment])) {
                $segments[$segment]++;
            }
        }

        // Purchase frequency analysis
        $frequency_analysis = $this->getPurchaseFrequencyAnalysis($business_id, $start_date, $end_date, $location_id);
        
        // Customer retention metrics
        $retention_metrics = $this->getCustomerRetentionMetrics($business_id, $start_date, $end_date, $location_id);
        
        // Churn prediction
        $churn_prediction = $this->getChurnPrediction($business_id, $location_id);
        
        // Loyalty trends
        $loyalty_trends = $this->getLoyaltyTrends($business_id, $location_id, $segmentation);

        return [
            'summary' => [
                'total_customers' => $total_customers,
                'total_revenue' => number_format($total_revenue, 2),
                'formatted_total_revenue' => $this->commonUtil->num_f($total_revenue, true),
                'avg_clv' => number_format($avg_clv, 2),
                'formatted_avg_clv' => $this->commonUtil->num_f($avg_clv, true),
                'active_customers' => $segmentation->where('recency_days', '<=', 90)->count(),
                'at_risk_customers' => $segmentation->where('recency_days', '>', 180)->count()
            ],
            'segmentation' => $segments,
            'frequency_analysis' => $frequency_analysis,
            'retention_metrics' => $retention_metrics,
            'churn_prediction' => $churn_prediction,
            'loyalty_trends' => $loyalty_trends,
            'top_customers' => $segmentation->sortByDesc('clv_score')->take(10)->values()->all()
        ];
    }

    private function getCustomerValueSegmentation($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = "
            SELECT 
                c.id as customer_id,
                c.name as customer_name,
                c.email,
                c.mobile,
                COUNT(DISTINCT t.id) as purchase_frequency,
                SUM(t.final_total) as total_spent,
                MAX(t.transaction_date) as last_purchase,
                MIN(t.transaction_date) as first_purchase,
                DATEDIFF(NOW(), MAX(t.transaction_date)) as recency_days,
                DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) as customer_lifespan_days,
                (SUM(t.final_total) / NULLIF(COUNT(DISTINCT t.id), 0)) as avg_order_value,
                CASE 
                    WHEN COUNT(DISTINCT t.id) = 1 THEN SUM(t.final_total)
                    WHEN DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) = 0 THEN SUM(t.final_total) * 2
                    ELSE (SUM(t.final_total) / NULLIF(COUNT(DISTINCT t.id), 0)) * 
                         GREATEST(COUNT(DISTINCT t.id), 
                                 LEAST(12, (COUNT(DISTINCT t.id) * 365.0 / NULLIF(DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)), 1))))
                END as clv_score
            FROM contacts c
            LEFT JOIN transactions t ON c.id = t.contact_id 
                AND t.business_id = ? 
                AND t.type = 'sell' 
                AND t.status = 'final'";

        $params = [$business_id];

        if ($start_date && $end_date) {
            $query .= " AND t.transaction_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        if ($location_id) {
            $query .= " AND t.location_id = ?";
            $params[] = $location_id;
        }

        $query .= "
            WHERE c.business_id = ? 
                AND c.type = 'customer'
                AND c.contact_status = 'active'
            GROUP BY c.id, c.name, c.email, c.mobile
            HAVING purchase_frequency > 0
            ORDER BY clv_score DESC";

        $params[] = $business_id;

        return collect(\DB::select($query, $params));
    }

    private function getCustomerSegment($clv_score, $frequency, $recency_days)
    {
        // RFM-based segmentation with CLV enhancement
        $recency_score = $this->getRecencyScore($recency_days);
        $frequency_score = $this->getFrequencyScore($frequency);
        $monetary_score = $this->getMonetaryScore($clv_score);

        $rfm_score = $recency_score . $frequency_score . $monetary_score;

        // Define segments based on RFM scores
        $segment_map = [
            '555' => 'Champions',
            '554' => 'Champions',
            '544' => 'Champions',
            '545' => 'Champions',
            '454' => 'Champions',
            '455' => 'Champions',
            '445' => 'Champions',
            
            '543' => 'Loyal Customers',
            '444' => 'Loyal Customers',
            '435' => 'Loyal Customers',
            '355' => 'Loyal Customers',
            '354' => 'Loyal Customers',
            '345' => 'Loyal Customers',
            '344' => 'Loyal Customers',
            
            '553' => 'Potential Loyalists',
            '551' => 'Potential Loyalists',
            '552' => 'Potential Loyalists',
            '541' => 'Potential Loyalists',
            '542' => 'Potential Loyalists',
            
            '512' => 'New Customers',
            '511' => 'New Customers',
            '422' => 'New Customers',
            '421' => 'New Customers',
            '412' => 'New Customers',
            '411' => 'New Customers',
            
            '533' => 'Promising',
            '532' => 'Promising',
            '531' => 'Promising',
            '523' => 'Promising',
            '522' => 'Promising',
            '521' => 'Promising',
            '515' => 'Promising',
            '514' => 'Promising',
            '513' => 'Promising',
            
            '155' => 'Cannot Lose Them',
            '154' => 'Cannot Lose Them',
            '144' => 'Cannot Lose Them',
            '214' => 'Cannot Lose Them',
            '215' => 'Cannot Lose Them',
            '115' => 'Cannot Lose Them',
            '114' => 'Cannot Lose Them',
            
            '331' => 'About to Sleep',
            '321' => 'About to Sleep',
            '231' => 'About to Sleep',
            '241' => 'About to Sleep',
            '251' => 'About to Sleep',
            
            '155' => 'At Risk',
            '254' => 'At Risk',
            '245' => 'At Risk',
            '253' => 'At Risk',
            '252' => 'At Risk',
            '243' => 'At Risk',
            '242' => 'At Risk',
            '235' => 'At Risk',
            '234' => 'At Risk',
            
            '332' => 'Need Attention',
            '322' => 'Need Attention',
            '231' => 'Need Attention',
            '241' => 'Need Attention',
            '251' => 'Need Attention',
            
            '155' => 'Hibernating',
            '144' => 'Hibernating',
            '135' => 'Hibernating',
            '134' => 'Hibernating',
            '145' => 'Hibernating',
            '233' => 'Hibernating',
            '232' => 'Hibernating',
            '223' => 'Hibernating',
            '222' => 'Hibernating',
            '132' => 'Hibernating',
            '123' => 'Hibernating',
            '122' => 'Hibernating',
            '212' => 'Hibernating',
            '213' => 'Hibernating',
        ];

        return $segment_map[$rfm_score] ?? 'Lost';
    }

    private function getRecencyScore($recency_days)
    {
        if ($recency_days <= 30) return 5;
        if ($recency_days <= 60) return 4;
        if ($recency_days <= 90) return 3;
        if ($recency_days <= 180) return 2;
        return 1;
    }

    private function getFrequencyScore($frequency)
    {
        if ($frequency >= 10) return 5;
        if ($frequency >= 7) return 4;
        if ($frequency >= 4) return 3;
        if ($frequency >= 2) return 2;
        return 1;
    }

    private function getMonetaryScore($clv_score)
    {
        if ($clv_score >= 10000) return 5;
        if ($clv_score >= 5000) return 4;
        if ($clv_score >= 2000) return 3;
        if ($clv_score >= 500) return 2;
        return 1;
    }

    private function getPurchaseFrequencyAnalysis($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereNotNull('contact_id');

        if ($start_date && $end_date) {
            $query->whereBetween('transaction_date', [$start_date, $end_date]);
        }

        if ($location_id) {
            $query->where('location_id', $location_id);
        }

        $transactions = $query->selectRaw('
            contact_id,
            COUNT(*) as purchase_count,
            AVG(final_total) as avg_order_value,
            SUM(final_total) as total_spent,
            MIN(transaction_date) as first_purchase,
            MAX(transaction_date) as last_purchase
        ')
        ->groupBy('contact_id')
        ->get();

        // Categorize by frequency
        $frequency_segments = [
            'One-time' => 0,
            'Occasional (2-5)' => 0,
            'Regular (6-10)' => 0,
            'Frequent (11-20)' => 0,
            'Super Frequent (20+)' => 0
        ];

        foreach ($transactions as $transaction) {
            $count = $transaction->purchase_count;
            if ($count == 1) $frequency_segments['One-time']++;
            elseif ($count <= 5) $frequency_segments['Occasional (2-5)']++;
            elseif ($count <= 10) $frequency_segments['Regular (6-10)']++;
            elseif ($count <= 20) $frequency_segments['Frequent (11-20)']++;
            else $frequency_segments['Super Frequent (20+)']++;
        }

        return [
            'segments' => $frequency_segments,
            'avg_purchase_frequency' => round($transactions->avg('purchase_count'), 2),
            'avg_order_value' => $this->commonUtil->num_f($transactions->avg('avg_order_value'), true),
            'total_repeat_customers' => $transactions->where('purchase_count', '>', 1)->count(),
            'repeat_rate' => $transactions->count() > 0 ? 
                round(($transactions->where('purchase_count', '>', 1)->count() / $transactions->count()) * 100, 2) : 0
        ];
    }

    private function getCustomerRetentionMetrics($business_id, $start_date, $end_date, $location_id = null)
    {
        // Calculate retention rates by cohort
        $cohort_analysis = $this->getCohortAnalysis($business_id, $location_id);
        
        // Use Laravel Query Builder instead of raw SQL
        $customer_lifetimes = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereNotNull('contact_id')
            ->selectRaw('
                contact_id,
                COUNT(*) as purchase_count,
                DATEDIFF(NOW(), MAX(transaction_date)) as days_since_last,
                DATEDIFF(MAX(transaction_date), MIN(transaction_date)) as customer_lifetime_days
            ')
            ->groupBy('contact_id')
            ->get();

        // Calculate metrics from the collection
        $avg_lifetime_days = $customer_lifetimes->avg('customer_lifetime_days');
        $avg_purchases_per_customer = $customer_lifetimes->avg('purchase_count');
        
        // Count active customers in different periods using Laravel Query Builder
        $active_3m = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', now()->subMonths(3))
            ->distinct('contact_id')
            ->count('contact_id');
            
        $active_6m = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', now()->subMonths(6))
            ->distinct('contact_id')
            ->count('contact_id');
            
        $active_12m = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', now()->subMonths(12))
            ->distinct('contact_id')
            ->count('contact_id');

        // Additional retention metrics
        $new_customers_30d = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', now()->subDays(30))
            ->distinct('contact_id')
            ->whereNotIn('contact_id', function($query) use ($business_id) {
                $query->select('contact_id')
                    ->from('transactions')
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->whereDate('transaction_date', '<', now()->subDays(30));
            })
            ->count('contact_id');

        $returning_customers_30d = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', now()->subDays(30))
            ->whereIn('contact_id', function($query) use ($business_id) {
                $query->select('contact_id')
                    ->from('transactions')
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->whereDate('transaction_date', '<', now()->subDays(30));
            })
            ->distinct('contact_id')
            ->count('contact_id');

        $customer_growth_rate = $active_3m > 0 ? round((($active_3m - $active_6m) / $active_6m) * 100, 2) : 0;
        $retention_rate_3m = $active_6m > 0 ? round(($active_3m / $active_6m) * 100, 2) : 0;
        $retention_rate_6m = $active_12m > 0 ? round(($active_6m / $active_12m) * 100, 2) : 0;

        return [
            'avg_customer_lifetime_days' => round($avg_lifetime_days ?? 0, 0),
            'avg_purchases_per_customer' => round($avg_purchases_per_customer ?? 0, 2),
            'retention_3m' => $active_3m,
            'retention_6m' => $active_6m,
            'retention_12m' => $active_12m,
            'new_customers_30d' => $new_customers_30d,
            'returning_customers_30d' => $returning_customers_30d,
            'customer_growth_rate' => $customer_growth_rate,
            'retention_rate_3m' => $retention_rate_3m,
            'retention_rate_6m' => $retention_rate_6m,
            'cohort_analysis' => $cohort_analysis
        ];
    }

    private function getCohortAnalysis($business_id, $location_id = null)
    {
        // Simplified cohort analysis using Laravel Query Builder
        $customer_cohorts = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereNotNull('contact_id')
            ->selectRaw('
                contact_id,
                DATE_FORMAT(MIN(transaction_date), "%Y-%m") as cohort_month,
                MIN(transaction_date) as first_purchase,
                MAX(transaction_date) as last_purchase
            ')
            ->groupBy('contact_id')
            ->get()
            ->groupBy('cohort_month')
            ->map(function($customers, $month) {
                $total_customers = $customers->count();
                $active_1m = $customers->where('last_purchase', '>=', now()->subMonth())->count();
                $active_3m = $customers->where('last_purchase', '>=', now()->subMonths(3))->count();
                $active_6m = $customers->where('last_purchase', '>=', now()->subMonths(6))->count();
                
                return (object) [
                    'cohort_month' => $month,
                    'customers_in_cohort' => $total_customers,
                    'active_1m' => $active_1m,
                    'active_3m' => $active_3m,
                    'active_6m' => $active_6m
                ];
            })
            ->sortByDesc('cohort_month')
            ->take(12)
            ->values();
        
        return $customer_cohorts->all();
    }

    private function getChurnPrediction($business_id, $location_id = null)
    {
        // Use Laravel Query Builder for churn prediction
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereNotNull('contact_id');
            
        if ($location_id) {
            $query->where('location_id', $location_id);
        }
        
        // First, let's get all customers with transactions
        $customer_recency = $query->selectRaw('
                contact_id,
                MAX(transaction_date) as last_transaction_date,
                DATEDIFF(NOW(), MAX(transaction_date)) as recency_days
            ')
            ->groupBy('contact_id')
            ->havingRaw('recency_days IS NOT NULL')
            ->get();

        // Categorize customers by recency with explicit filtering
        $high_risk = $customer_recency->filter(function($customer) {
            return $customer->recency_days > 90;
        })->count();
        
        $medium_risk = $customer_recency->filter(function($customer) {
            return $customer->recency_days >= 60 && $customer->recency_days <= 90;
        })->count();
        
        $low_risk = $customer_recency->filter(function($customer) {
            return $customer->recency_days >= 30 && $customer->recency_days < 60;
        })->count();
        
        $active = $customer_recency->filter(function($customer) {
            return $customer->recency_days < 30;
        })->count();
        
        $total_customers = $customer_recency->count();
        $avg_days_since_purchase = $customer_recency->avg('recency_days');

        return [
            'high_risk' => $high_risk,
            'medium_risk' => $medium_risk,
            'low_risk' => $low_risk,
            'active' => $active,
            'avg_days_since_purchase' => round($avg_days_since_purchase ?? 0, 1),
            'total_customers' => $total_customers,
            'churn_rate' => $total_customers > 0 ? 
                round(($high_risk / $total_customers) * 100, 2) : 0
        ];
    }

    private function getChurnRisk($recency_days, $frequency)
    {
        if ($recency_days > 180 || ($recency_days > 90 && $frequency <= 2)) {
            return 'High';
        } elseif ($recency_days > 90 || ($recency_days > 60 && $frequency <= 3)) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    private function getLoyaltyTrends($business_id, $location_id = null, $segmentation = null)
    {
        // Calculate loyalty trends based on customer behavior patterns
        $loyalty_data = collect();
        
        // If segmentation data is provided, use it; otherwise fetch fresh data
        if (!$segmentation) {
            $segmentation = $this->getCustomerValueSegmentation($business_id, null, null, $location_id);
        }
        
        // Group customers by loyalty segments
        $loyalty_segments = collect($segmentation)->map(function($customer) {
            $segment = $this->getCustomerSegment($customer->clv_score, $customer->purchase_frequency, $customer->recency_days);
            return (object) [
                'customer_id' => $customer->customer_id,
                'segment' => $segment,
                'clv_score' => $customer->clv_score,
                'frequency' => $customer->purchase_frequency,
                'recency' => $customer->recency_days,
                'total_spent' => $customer->total_spent
            ];
        });
        
        // Calculate loyalty distribution
        $loyalty_distribution = $loyalty_segments->groupBy('segment')->map(function($customers, $segment) {
            return [
                'segment' => $segment,
                'count' => $customers->count(),
                'avg_clv' => $customers->avg('clv_score'),
                'avg_frequency' => $customers->avg('frequency'),
                'total_value' => $customers->sum('total_spent')
            ];
        });
        
        // Calculate loyalty trends over last 6 months
        $loyalty_trends_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month_start = now()->subMonths($i)->startOfMonth();
            $month_end = now()->subMonths($i)->endOfMonth();
            $month_name = $month_start->format('M Y');
            
            // Get customers active in this month
            $active_customers = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereNotNull('contact_id')
                ->whereBetween('transaction_date', [$month_start, $month_end])
                ->distinct('contact_id')
                ->count();
                
            // Get repeat customers in this month
            $repeat_customers = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereNotNull('contact_id')
                ->whereBetween('transaction_date', [$month_start, $month_end])
                ->selectRaw('contact_id, COUNT(*) as purchase_count')
                ->groupBy('contact_id')
                ->having('purchase_count', '>', 1)
                ->count();
                
            $loyalty_score = $active_customers > 0 ? round(($repeat_customers / $active_customers) * 100, 1) : 0;
            
            $loyalty_trends_data[] = [
                'month' => $month_name,
                'active_customers' => $active_customers,
                'repeat_customers' => $repeat_customers,
                'loyalty_score' => $loyalty_score
            ];
        }
        
        // Calculate overall loyalty metrics
        $total_customers = $loyalty_segments->count();
        $loyal_segments = ['Champions', 'Loyal Customers', 'Potential Loyalists'];
        $loyal_customers = $loyalty_segments->whereIn('segment', $loyal_segments)->count();
        $overall_loyalty_rate = $total_customers > 0 ? round(($loyal_customers / $total_customers) * 100, 1) : 0;
        
        // High-value loyal customers (top 20% by CLV in loyal segments)
        $loyal_customers_data = $loyalty_segments->whereIn('segment', $loyal_segments);
        $high_value_threshold = $loyal_customers_data->isNotEmpty() ? $loyal_customers_data->sortByDesc('clv_score')->values()->get((int)($loyal_customers_data->count() * 0.2)) : null;
        $high_value_loyal = $high_value_threshold ? $loyal_customers_data->where('clv_score', '>=', $high_value_threshold->clv_score)->count() : 0;
        
        // Average loyalty metrics
        $avg_frequency_loyal = $loyal_customers_data->isNotEmpty() ? round($loyal_customers_data->avg('frequency'), 1) : 0;
        $avg_clv_loyal = $loyal_customers_data->isNotEmpty() ? round($loyal_customers_data->avg('clv_score'), 2) : 0;
        
        return [
            'trends_data' => $loyalty_trends_data,
            'distribution' => $loyalty_distribution->values()->all(),
            'metrics' => [
                'total_customers' => $total_customers,
                'loyal_customers' => $loyal_customers,
                'loyalty_rate' => $overall_loyalty_rate,
                'high_value_loyal' => $high_value_loyal,
                'avg_frequency_loyal' => $avg_frequency_loyal,
                'avg_clv_loyal' => $avg_clv_loyal,
                'current_month_score' => end($loyalty_trends_data)['loyalty_score'] ?? 0
            ]
        ];
    }

    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.customer_lifetime_value')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $customer_group_id = $request->get('customer_group_id');

        // Get customer segmentation data for export
        $segmentation = $this->getCustomerValueSegmentation($business_id, $start_date, $end_date, $location_id);

        $export_data = [];
        foreach ($segmentation as $customer) {
            $segment = $this->getCustomerSegment($customer->clv_score, $customer->purchase_frequency, $customer->recency_days);
            $risk = $this->getChurnRisk($customer->recency_days, $customer->purchase_frequency);
            
            $export_data[] = [
                'Customer' => $customer->customer_name ?: 'Walk-in Customer',
                'Email' => $customer->email ?: '-',
                'Mobile' => $customer->mobile ?: '-',
                'Segment' => $segment,
                'Total Spent' => number_format($customer->total_spent, 2),
                'Purchase Frequency' => $customer->purchase_frequency,
                'CLV Score' => number_format($customer->clv_score, 2),
                'Avg Order Value' => number_format($customer->avg_order_value, 2),
                'Last Purchase' => $customer->last_purchase ? date('M d, Y', strtotime($customer->last_purchase)) : 'Never',
                'First Purchase' => $customer->first_purchase ? date('M d, Y', strtotime($customer->first_purchase)) : 'Never',
                'Customer Lifetime (Days)' => $customer->customer_lifespan_days,
                'Recency (Days)' => $customer->recency_days,
                'Risk Level' => $risk
            ];
        }

        // Create CSV
        $filename = 'customer-lifetime-value-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($export_data) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            if (!empty($export_data)) {
                fputcsv($file, array_keys($export_data[0]));
            }
            
            // Add data
            foreach ($export_data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}