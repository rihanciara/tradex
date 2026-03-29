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
use Modules\AdvancedReports\Exports\CustomerSegmentationExport;

class CustomerSegmentationController extends Controller
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
     * Display customer segmentation report index page
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

        $data = compact('business_locations', 'customers');
        
        return view('advancedreports::customer-segmentation.index', $data);
    }

    /**
     * Get customer segmentation analytics data
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
            'rfm_analysis' => $this->getRFMAnalysis($business_id, $start_date, $end_date, $location_id, $customer_id),
            'geographic_distribution' => $this->getGeographicDistribution($business_id, $start_date, $end_date, $location_id, $customer_id),
            'demographic_analysis' => $this->getDemographicAnalysis($business_id, $start_date, $end_date, $location_id, $customer_id),
            'vip_customers' => $this->getVIPCustomers($business_id, $start_date, $end_date, $location_id, $customer_id),
            'summary_cards' => $this->getSegmentationSummaryCards($business_id, $start_date, $end_date, $location_id, $customer_id),
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Get RFM (Recency, Frequency, Monetary) Analysis
     */
    private function getRFMAnalysis($business_id, $start_date, $end_date, $location_id = null, $customer_id = null)
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

        // Get customer data with RFM metrics
        $customers = $query->selectRaw('
                t.contact_id,
                COALESCE(c.name, "Walk-in Customer") as customer_name,
                COALESCE(c.mobile, "N/A") as customer_mobile,
                COALESCE(c.email, "N/A") as customer_email,
                COALESCE(c.city, "Unknown") as customer_city,
                COALESCE(c.state, "Unknown") as customer_state,
                COUNT(*) as frequency,
                SUM(t.final_total) as monetary_value,
                AVG(t.final_total) as avg_order_value,
                MAX(t.transaction_date) as last_purchase_date,
                MIN(t.transaction_date) as first_purchase_date,
                DATEDIFF(CURDATE(), MAX(t.transaction_date)) as recency_days
            ')
            ->groupBy('t.contact_id', 'c.name', 'c.mobile', 'c.email', 'c.city', 'c.state')
            ->get();

        // Calculate RFM scores (1-5 scale)
        $customers = $customers->map(function($customer) {
            // Recency Score (lower days = higher score)
            if ($customer->recency_days <= 30) $customer->recency_score = 5;
            elseif ($customer->recency_days <= 60) $customer->recency_score = 4;
            elseif ($customer->recency_days <= 90) $customer->recency_score = 3;
            elseif ($customer->recency_days <= 180) $customer->recency_score = 2;
            else $customer->recency_score = 1;

            // Frequency Score
            if ($customer->frequency >= 10) $customer->frequency_score = 5;
            elseif ($customer->frequency >= 7) $customer->frequency_score = 4;
            elseif ($customer->frequency >= 4) $customer->frequency_score = 3;
            elseif ($customer->frequency >= 2) $customer->frequency_score = 2;
            else $customer->frequency_score = 1;

            // Monetary Score
            if ($customer->monetary_value >= 10000) $customer->monetary_score = 5;
            elseif ($customer->monetary_value >= 5000) $customer->monetary_score = 4;
            elseif ($customer->monetary_value >= 2000) $customer->monetary_score = 3;
            elseif ($customer->monetary_value >= 500) $customer->monetary_score = 2;
            else $customer->monetary_score = 1;

            // RFM Combined Score
            $customer->rfm_score = ($customer->recency_score * 100) + ($customer->frequency_score * 10) + $customer->monetary_score;
            
            // Customer Segment Classification
            $customer->segment = $this->classifyRFMSegment($customer->recency_score, $customer->frequency_score, $customer->monetary_score);
            
            return $customer;
        });

        // Segment distribution
        $segment_distribution = $customers->groupBy('segment')->map(function($group, $segment) {
            return [
                'segment' => $segment,
                'count' => $group->count(),
                'total_value' => $group->sum('monetary_value'),
                'avg_value' => $group->avg('monetary_value'),
                'avg_frequency' => $group->avg('frequency'),
                'avg_recency' => $group->avg('recency_days')
            ];
        })->values();

        // RFM Score Distribution
        $rfm_distribution = [
            'recency_distribution' => $customers->groupBy('recency_score')->map->count(),
            'frequency_distribution' => $customers->groupBy('frequency_score')->map->count(),
            'monetary_distribution' => $customers->groupBy('monetary_score')->map->count()
        ];

        return [
            'customers' => $customers->sortByDesc('rfm_score')->take(50)->values()->map(function($customer) {
                return (array) $customer;
            })->toArray(),
            'segment_distribution' => $segment_distribution->toArray(),
            'rfm_distribution' => $rfm_distribution,
            'total_customers' => $customers->count()
        ];
    }

    /**
     * Classify RFM segment based on scores
     */
    private function classifyRFMSegment($recency, $frequency, $monetary)
    {
        if ($recency >= 4 && $frequency >= 4 && $monetary >= 4) {
            return 'Champions';
        } elseif ($recency >= 3 && $frequency >= 3 && $monetary >= 3) {
            return 'Loyal Customers';
        } elseif ($recency >= 4 && $frequency <= 2 && $monetary <= 2) {
            return 'New Customers';
        } elseif ($recency >= 3 && $frequency <= 2 && $monetary >= 3) {
            return 'Potential Loyalists';
        } elseif ($recency >= 3 && $frequency >= 3 && $monetary <= 2) {
            return 'Need Attention';
        } elseif ($recency <= 2 && $frequency >= 3 && $monetary >= 3) {
            return 'At Risk';
        } elseif ($recency <= 2 && $frequency >= 4 && $monetary >= 4) {
            return 'Cannot Lose Them';
        } elseif ($recency <= 2 && $frequency <= 2 && $monetary <= 2) {
            return 'Lost';
        } elseif ($recency <= 1 && $frequency <= 1) {
            return 'Hibernating';
        } else {
            return 'Others';
        }
    }

    /**
     * Get Geographic Distribution Analysis
     */
    private function getGeographicDistribution($business_id, $start_date, $end_date, $location_id, $customer_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
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

        // City distribution
        $city_distribution = $query->clone()->selectRaw('
                COALESCE(c.city, "Unknown") as city,
                COUNT(DISTINCT t.contact_id) as customer_count,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy('c.city')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // State distribution
        $state_distribution = $query->clone()->selectRaw('
                COALESCE(c.state, "Unknown") as state,
                COUNT(DISTINCT t.contact_id) as customer_count,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy('c.state')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Location-wise performance
        $location_performance = $query->clone()->selectRaw('
                bl.name as location_name,
                COUNT(DISTINCT t.contact_id) as customer_count,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy('bl.id', 'bl.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'city_distribution' => $city_distribution->toArray(),
            'state_distribution' => $state_distribution->toArray(),
            'location_performance' => $location_performance->toArray()
        ];
    }

    /**
     * Get Demographic Analysis
     */
    private function getDemographicAnalysis($business_id, $start_date, $end_date, $location_id, $customer_id)
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

        // Age group analysis (based on date_of_birth if available)
        $age_distribution = $query->clone()->selectRaw('
                CASE 
                    WHEN c.dob IS NULL THEN "Unknown"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 25 THEN "18-24"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 35 THEN "25-34"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 45 THEN "35-44"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 55 THEN "45-54"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 65 THEN "55-64"
                    ELSE "65+"
                END as age_group,
                COUNT(DISTINCT t.contact_id) as customer_count,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy(DB::raw('CASE 
                    WHEN c.dob IS NULL THEN "Unknown"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 25 THEN "18-24"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 35 THEN "25-34"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 45 THEN "35-44"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 55 THEN "45-54"
                    WHEN DATEDIFF(CURDATE(), c.dob)/365 < 65 THEN "55-64"
                    ELSE "65+"
                END'))
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Customer type analysis
        $customer_type_distribution = $query->clone()->selectRaw('
                CASE 
                    WHEN c.type = "customer" THEN "Individual"
                    WHEN c.type = "both" THEN "Individual & Supplier"
                    WHEN c.type = "supplier" THEN "Supplier"
                    ELSE "Unknown"
                END as customer_type,
                COUNT(DISTINCT t.contact_id) as customer_count,
                COUNT(*) as transaction_count,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')
            ->groupBy('c.type')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Purchase behavior patterns
        $purchase_patterns = $query->clone()->selectRaw('
                t.contact_id,
                COALESCE(c.name, "Walk-in Customer") as customer_name,
                COUNT(*) as total_purchases,
                SUM(t.final_total) as total_spent,
                AVG(t.final_total) as avg_order_value,
                MIN(t.transaction_date) as first_purchase,
                MAX(t.transaction_date) as last_purchase,
                CASE 
                    WHEN COUNT(*) = 1 THEN "One-time Buyer"
                    WHEN COUNT(*) <= 3 THEN "Occasional Buyer"
                    WHEN COUNT(*) <= 10 THEN "Regular Buyer"
                    ELSE "Frequent Buyer"
                END as buyer_type
            ')
            ->groupBy('t.contact_id', 'c.name')
            ->get()
            ->groupBy('buyer_type')
            ->map(function($group, $type) {
                return [
                    'buyer_type' => $type,
                    'count' => $group->count(),
                    'total_revenue' => $group->sum('total_spent'),
                    'avg_revenue_per_customer' => $group->avg('total_spent'),
                    'avg_order_value' => $group->avg('avg_order_value')
                ];
            })->values();

        return [
            'age_distribution' => $age_distribution->toArray(),
            'customer_type_distribution' => $customer_type_distribution->toArray(),
            'purchase_patterns' => $purchase_patterns->toArray()
        ];
    }

    /**
     * Get VIP Customer Identification
     */
    private function getVIPCustomers($business_id, $start_date, $end_date, $location_id, $customer_id)
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

        // VIP criteria calculation
        $customers = $query->selectRaw('
                t.contact_id,
                COALESCE(c.name, "Walk-in Customer") as customer_name,
                COALESCE(c.mobile, "N/A") as customer_mobile,
                COALESCE(c.email, "N/A") as customer_email,
                COUNT(*) as total_transactions,
                SUM(t.final_total) as total_spent,
                AVG(t.final_total) as avg_order_value,
                MAX(t.transaction_date) as last_purchase_date,
                MIN(t.transaction_date) as first_purchase_date,
                DATEDIFF(CURDATE(), MAX(t.transaction_date)) as days_since_last_purchase,
                DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) as customer_lifetime_days
            ')
            ->groupBy('t.contact_id', 'c.name', 'c.mobile', 'c.email')
            ->havingRaw('COUNT(*) > 0')
            ->get();

        // Calculate VIP scores and classifications
        $customers = $customers->map(function($customer) {
            // VIP Score calculation (0-100)
            $monetary_score = min(($customer->total_spent / 1000) * 10, 40); // Max 40 points for spending
            $frequency_score = min($customer->total_transactions * 2, 30); // Max 30 points for frequency
            $recency_score = max(30 - ($customer->days_since_last_purchase / 10), 0); // Max 30 points for recency
            
            $customer->vip_score = round($monetary_score + $frequency_score + $recency_score, 1);
            
            // VIP Classification
            if ($customer->vip_score >= 80) {
                $customer->vip_tier = 'Platinum';
                $customer->vip_color = '#E5E4E2';
            } elseif ($customer->vip_score >= 60) {
                $customer->vip_tier = 'Gold';
                $customer->vip_color = '#FFD700';
            } elseif ($customer->vip_score >= 40) {
                $customer->vip_tier = 'Silver';
                $customer->vip_color = '#C0C0C0';
            } elseif ($customer->vip_score >= 20) {
                $customer->vip_tier = 'Bronze';
                $customer->vip_color = '#CD7F32';
            } else {
                $customer->vip_tier = 'Standard';
                $customer->vip_color = '#808080';
            }
            
            return $customer;
        });

        // VIP tier distribution
        $vip_distribution = $customers->groupBy('vip_tier')->map(function($group, $tier) {
            return [
                'tier' => $tier,
                'count' => $group->count(),
                'total_revenue' => $group->sum('total_spent'),
                'avg_revenue_per_customer' => $group->avg('total_spent'),
                'avg_transactions_per_customer' => $group->avg('total_transactions'),
                'avg_vip_score' => $group->avg('vip_score')
            ];
        })->values();

        // Top VIP customers
        $top_vip_customers = $customers->sortByDesc('vip_score')->take(20)->values()->map(function($customer) {
            return (array) $customer;
        });

        // VIP insights
        $total_revenue = $customers->sum('total_spent');
        $vip_revenue = $customers->where('vip_score', '>=', 40)->sum('total_spent');
        $vip_percentage = $total_revenue > 0 ? round(($vip_revenue / $total_revenue) * 100, 1) : 0;

        return [
            'vip_distribution' => $vip_distribution->toArray(),
            'top_vip_customers' => $top_vip_customers->values()->toArray(),
            'vip_insights' => [
                'total_customers' => $customers->count(),
                'vip_customers' => $customers->where('vip_score', '>=', 40)->count(),
                'vip_revenue_percentage' => $vip_percentage,
                'avg_vip_score' => round($customers->avg('vip_score'), 1)
            ]
        ];
    }

    /**
     * Get summary cards data for customer segmentation
     */
    private function getSegmentationSummaryCards($business_id, $start_date, $end_date, $location_id, $customer_id)
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

        $summary = $query->selectRaw('
                COUNT(*) as total_transactions,
                COUNT(DISTINCT t.contact_id) as total_customers,
                COUNT(DISTINCT c.city) as unique_cities,
                COUNT(DISTINCT c.state) as unique_states,
                SUM(t.final_total) as total_revenue,
                AVG(t.final_total) as avg_order_value
            ')->first();

        // Calculate repeat customers
        $repeat_customers = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->whereNotNull('t.contact_id')
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($customer_id && $customer_id != 'all', function($q) use ($customer_id) {
                return $q->where('t.contact_id', $customer_id);
            })
            ->groupBy('contact_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $repeat_rate = $summary->total_customers > 0 ? round(($repeat_customers / $summary->total_customers) * 100, 1) : 0;

        return [
            'total_customers' => $summary->total_customers ?: 0,
            'total_transactions' => $summary->total_transactions ?: 0,
            'unique_cities' => $summary->unique_cities ?: 0,
            'unique_states' => $summary->unique_states ?: 0,
            'total_revenue' => $summary->total_revenue ?: 0,
            'avg_order_value' => $summary->avg_order_value ?: 0,
            'repeat_customers' => $repeat_customers,
            'repeat_rate' => $repeat_rate,
            'formatted_total_revenue' => $this->businessUtil->num_f($summary->total_revenue ?: 0, true),
            'formatted_avg_order_value' => $this->businessUtil->num_f($summary->avg_order_value ?: 0, true)
        ];
    }

    /**
     * Export customer segmentation data to Excel
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
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

            // Get basic export data with basic customer information for now
            $export_data = [];

            // Get basic customer data
            $customers = DB::table('transactions as t')
                ->select([
                    'c.id',
                    'c.name',
                    'c.mobile',
                    'c.email',
                    'c.city',
                    'c.state',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(t.final_total) as total_spent'),
                    DB::raw('AVG(t.final_total) as avg_order_value'),
                    DB::raw('MAX(t.transaction_date) as last_purchase'),
                    DB::raw('MIN(t.transaction_date) as first_purchase')
                ])
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
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
                ->groupBy('c.id', 'c.name', 'c.mobile', 'c.email', 'c.city', 'c.state')
                ->get();

            foreach ($customers as $customer) {
                // Determine customer value tier
                $tier = 'Bronze';
                if ($customer->total_spent >= 10000) {
                    $tier = 'Platinum';
                } elseif ($customer->total_spent >= 5000) {
                    $tier = 'Gold';
                } elseif ($customer->total_spent >= 1000) {
                    $tier = 'Silver';
                }

                $export_data[] = [
                    'Customer ID' => $customer->id,
                    'Customer Name' => $customer->name,
                    'Mobile' => $customer->mobile,
                    'Email' => $customer->email,
                    'City' => $customer->city ?: 'Unknown',
                    'State' => $customer->state ?: 'Unknown',
                    'Transaction Count' => $customer->transaction_count,
                    'Total Spent' => number_format($customer->total_spent, 2),
                    'Avg Order Value' => number_format($customer->avg_order_value, 2),
                    'Value Tier' => $tier,
                    'First Purchase' => $customer->first_purchase,
                    'Last Purchase' => $customer->last_purchase,
                ];
            }

            // Create analytics summary for the export
            $analytics = [
                'summary_cards' => [
                    'total_customers' => $customers->count(),
                    'total_transactions' => $customers->sum('transaction_count'),
                    'total_revenue' => $customers->sum('total_spent'),
                    'avg_order_value' => $customers->avg('avg_order_value'),
                    'repeat_rate' => $customers->where('transaction_count', '>', 1)->count() / max($customers->count(), 1) * 100
                ]
            ];

            // Create Excel file
            $filename = 'customer-segmentation-analysis-' . date('Y-m-d') . '.xlsx';

            return Excel::download(new CustomerSegmentationExport($export_data, $analytics), $filename);

        } catch (\Exception $e) {
            \Log::error('Customer Segmentation Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }
}