<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffProductivityController extends Controller
{
    /**
     * Display staff productivity report
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.staff_productivity')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get business locations
        $business_locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->pluck('name', 'id');

        // Get categories
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $categories->prepend(__('advancedreports::lang.all_categories'), 'all');

        // Get staff members (users who have created transactions)
        $staff_members = DB::table('users as u')
            ->join('transactions as t', 'u.id', '=', 't.created_by')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select('u.id', DB::raw("COALESCE(CONCAT(u.first_name, ' ', IFNULL(u.last_name, '')), u.first_name, u.username, CONCAT('User ', u.id)) as name"))
            ->distinct()
            ->orderBy('u.first_name')
            ->pluck('name', 'id');

        return view('advancedreports::staff-productivity.index', compact(
            'business_locations',
            'categories',
            'staff_members'
        ));
    }

    /**
     * Get staff productivity analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $location_ids = $request->get('location_ids', []);
        $category_id = $request->get('category_id', 'all');
        $staff_ids = $request->get('staff_ids', []);

        try {
            return response()->json([
                'staff_sales_performance' => $this->getStaffSalesPerformance($business_id, $start_date, $end_date, $location_ids, $category_id, $staff_ids),
                'working_hours_efficiency' => $this->getWorkingHoursEfficiency($business_id, $start_date, $end_date, $location_ids, $staff_ids),
                'commission_tracking' => $this->getCommissionTracking($business_id, $start_date, $end_date, $location_ids, $staff_ids),
                'performance_suggestions' => $this->getPerformanceImprovementSuggestions($business_id, $start_date, $end_date, $location_ids, $staff_ids),
                'productivity_trends' => $this->getProductivityTrends($business_id, $start_date, $end_date, $location_ids, $staff_ids),
                'staff_comparison' => $this->getStaffComparison($business_id, $start_date, $end_date, $location_ids, $category_id, $staff_ids)
            ]);
        } catch (\Exception $e) {
            \Log::error('Staff Productivity Analytics Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load analytics data'], 500);
        }
    }

    /**
     * Get staff sales performance data
     */
    private function getStaffSalesPerformance($business_id, $start_date, $end_date, $selected_locations = [], $category_id = 'all', $selected_staff = [])
    {
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $query->whereIn('t.location_id', $selected_locations);
        }

        if (!empty($selected_staff)) {
            $query->whereIn('t.created_by', $selected_staff);
        }

        if ($category_id && $category_id !== 'all') {
            $query->join('products as p', 'v.product_id', '=', 'p.id')
                  ->where('p.category_id', $category_id);
        }

        $staff_performance = $query->select([
            'u.id as staff_id',
            'u.first_name',
            'u.last_name',
            'bl.name as location_name',
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_sales'),
            DB::raw('SUM(tsl.quantity * v.dpp_inc_tax) as total_cost'),
            DB::raw('AVG(tsl.quantity * tsl.unit_price_inc_tax) as avg_sale_value'),
            DB::raw('COUNT(DISTINCT t.contact_id) as unique_customers'),
            DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as active_days')
        ])
        ->groupBy('u.id', 'u.first_name', 'u.last_name', 'bl.name')
        ->orderBy('total_sales', 'desc')
        ->get();

        // Calculate additional metrics
        $staff_with_metrics = $staff_performance->map(function($staff) use ($start_date, $end_date) {
            $total_days = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;
            $gross_profit = $staff->total_sales - $staff->total_cost;
            $profit_margin = $staff->total_sales > 0 ? ($gross_profit / $staff->total_sales) * 100 : 0;
            
            return [
                'staff_id' => $staff->staff_id,
                'staff_name' => trim($staff->first_name . ' ' . ($staff->last_name ?? '')),
                'location_name' => $staff->location_name,
                'total_sales' => $staff->total_sales,
                'total_transactions' => $staff->total_transactions,
                'avg_sale_value' => $staff->avg_sale_value,
                'unique_customers' => $staff->unique_customers,
                'active_days' => $staff->active_days,
                'gross_profit' => $gross_profit,
                'profit_margin' => $profit_margin,
                'daily_avg_sales' => $staff->active_days > 0 ? $staff->total_sales / $staff->active_days : 0,
                'transactions_per_day' => $staff->active_days > 0 ? $staff->total_transactions / $staff->active_days : 0,
                'customers_per_day' => $staff->active_days > 0 ? $staff->unique_customers / $staff->active_days : 0,
                'sales_per_customer' => $staff->unique_customers > 0 ? $staff->total_sales / $staff->unique_customers : 0
            ];
        });

        return [
            'staff_performance' => $staff_with_metrics,
            'totals' => [
                'total_sales' => $staff_with_metrics->sum('total_sales'),
                'total_transactions' => $staff_with_metrics->sum('total_transactions'),
                'total_staff' => $staff_with_metrics->count(),
                'avg_profit_margin' => $staff_with_metrics->avg('profit_margin'),
                'top_performer' => $staff_with_metrics->first()
            ]
        ];
    }

    /**
     * Get working hours efficiency data
     */
    private function getWorkingHoursEfficiency($business_id, $start_date, $end_date, $selected_locations = [], $selected_staff = [])
    {
        // Get staff working hours from attendance or shifts (simplified for demo)
        $efficiency_data = DB::table('transactions as t')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $efficiency_data->whereIn('t.location_id', $selected_locations);
        }

        if (!empty($selected_staff)) {
            $efficiency_data->whereIn('t.created_by', $selected_staff);
        }

        $working_hours = $efficiency_data->select([
            'u.id as staff_id',
            'u.first_name',
            'u.last_name',
            'bl.name as location_name',
            DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as working_days'),
            DB::raw('MIN(TIME(t.transaction_date)) as earliest_transaction'),
            DB::raw('MAX(TIME(t.transaction_date)) as latest_transaction'),
            DB::raw('COUNT(t.id) as total_transactions'),
            DB::raw('SUM(t.final_total) as total_sales')
        ])
        ->groupBy('u.id', 'u.first_name', 'u.last_name', 'bl.name')
        ->get();

        // Calculate efficiency metrics
        $efficiency_metrics = $working_hours->map(function($staff) {
            // Estimate working hours (simplified - in real scenario would use actual time tracking)
            $estimated_daily_hours = 8; // Default 8 hours per day
            $total_estimated_hours = $staff->working_days * $estimated_daily_hours;
            
            return [
                'staff_id' => $staff->staff_id,
                'staff_name' => trim($staff->first_name . ' ' . ($staff->last_name ?? '')),
                'location_name' => $staff->location_name,
                'working_days' => $staff->working_days,
                'estimated_hours' => $total_estimated_hours,
                'total_transactions' => $staff->total_transactions,
                'total_sales' => $staff->total_sales,
                'transactions_per_hour' => $total_estimated_hours > 0 ? $staff->total_transactions / $total_estimated_hours : 0,
                'sales_per_hour' => $total_estimated_hours > 0 ? $staff->total_sales / $total_estimated_hours : 0,
                'efficiency_score' => $this->calculateEfficiencyScore($staff->total_sales, $total_estimated_hours, $staff->total_transactions)
            ];
        });

        return [
            'efficiency_metrics' => $efficiency_metrics,
            'averages' => [
                'avg_transactions_per_hour' => $efficiency_metrics->avg('transactions_per_hour'),
                'avg_sales_per_hour' => $efficiency_metrics->avg('sales_per_hour'),
                'avg_efficiency_score' => $efficiency_metrics->avg('efficiency_score')
            ]
        ];
    }

    /**
     * Calculate efficiency score
     */
    private function calculateEfficiencyScore($sales, $hours, $transactions)
    {
        if ($hours == 0) return 0;
        
        $sales_per_hour = $sales / $hours;
        $transactions_per_hour = $transactions / $hours;
        
        // Weighted scoring (60% sales, 40% transactions)
        $normalized_sales = min($sales_per_hour / 1000, 1) * 60; // Normalize to max 1000/hour
        $normalized_transactions = min($transactions_per_hour / 10, 1) * 40; // Normalize to max 10/hour
        
        return round($normalized_sales + $normalized_transactions, 2);
    }

    /**
     * Get commission tracking data
     */
    private function getCommissionTracking($business_id, $start_date, $end_date, $selected_locations = [], $selected_staff = [])
    {
        $commission_rate = 0.05; // 5% commission rate (configurable in real scenario)
        
        $commission_data = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $commission_data->whereIn('t.location_id', $selected_locations);
        }

        if (!empty($selected_staff)) {
            $commission_data->whereIn('t.created_by', $selected_staff);
        }

        $commissions = $commission_data->select([
            'u.id as staff_id',
            'u.first_name',
            'u.last_name',
            'bl.name as location_name',
            DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_sales'),
            DB::raw('SUM(tsl.quantity * v.dpp_inc_tax) as total_cost'),
            DB::raw('COUNT(DISTINCT t.id) as total_transactions')
        ])
        ->groupBy('u.id', 'u.first_name', 'u.last_name', 'bl.name')
        ->get();

        $commission_tracking = $commissions->map(function($staff) use ($commission_rate) {
            $gross_profit = $staff->total_sales - $staff->total_cost;
            $commission_amount = $gross_profit * $commission_rate;
            
            return [
                'staff_id' => $staff->staff_id,
                'staff_name' => trim($staff->first_name . ' ' . ($staff->last_name ?? '')),
                'location_name' => $staff->location_name,
                'total_sales' => $staff->total_sales,
                'gross_profit' => $gross_profit,
                'commission_rate' => $commission_rate * 100,
                'commission_amount' => $commission_amount,
                'total_transactions' => $staff->total_transactions,
                'avg_commission_per_transaction' => $staff->total_transactions > 0 ? $commission_amount / $staff->total_transactions : 0
            ];
        });

        return [
            'commission_data' => $commission_tracking,
            'summary' => [
                'total_commissions' => $commission_tracking->sum('commission_amount'),
                'avg_commission_per_staff' => $commission_tracking->avg('commission_amount'),
                'top_earner' => $commission_tracking->sortByDesc('commission_amount')->first()
            ]
        ];
    }

    /**
     * Get performance improvement suggestions
     */
    private function getPerformanceImprovementSuggestions($business_id, $start_date, $end_date, $selected_locations = [], $selected_staff = [])
    {
        // Get staff performance data for analysis
        $staff_data = $this->getStaffSalesPerformance($business_id, $start_date, $end_date, $selected_locations, 'all', $selected_staff);
        $efficiency_data = $this->getWorkingHoursEfficiency($business_id, $start_date, $end_date, $selected_locations, $selected_staff);
        
        $suggestions = [];
        
        foreach ($staff_data['staff_performance'] as $staff) {
            $staff_suggestions = [];
            
            // Sales performance suggestions
            if ($staff['daily_avg_sales'] < 500) {
                $staff_suggestions[] = [
                    'type' => 'sales',
                    'priority' => 'high',
                    'suggestion' => 'Focus on upselling and cross-selling techniques to increase daily sales average',
                    'current_metric' => round($staff['daily_avg_sales'], 2),
                    'target_metric' => 500,
                    'improvement_potential' => '40-60%'
                ];
            }
            
            // Transaction frequency suggestions
            if ($staff['transactions_per_day'] < 10) {
                $staff_suggestions[] = [
                    'type' => 'transactions',
                    'priority' => 'medium',
                    'suggestion' => 'Improve customer engagement and product recommendations to increase transaction frequency',
                    'current_metric' => round($staff['transactions_per_day'], 2),
                    'target_metric' => 10,
                    'improvement_potential' => '25-35%'
                ];
            }
            
            // Profit margin suggestions
            if ($staff['profit_margin'] < 20) {
                $staff_suggestions[] = [
                    'type' => 'profit',
                    'priority' => 'high',
                    'suggestion' => 'Focus on selling higher-margin products and avoid excessive discounting',
                    'current_metric' => round($staff['profit_margin'], 1),
                    'target_metric' => 25,
                    'improvement_potential' => '15-25%'
                ];
            }
            
            // Customer retention suggestions
            if ($staff['customers_per_day'] < 5) {
                $staff_suggestions[] = [
                    'type' => 'customers',
                    'priority' => 'medium',
                    'suggestion' => 'Implement customer retention strategies and referral programs',
                    'current_metric' => round($staff['customers_per_day'], 2),
                    'target_metric' => 8,
                    'improvement_potential' => '30-50%'
                ];
            }
            
            $suggestions[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name'],
                'location_name' => $staff['location_name'],
                'overall_score' => $this->calculateOverallPerformanceScore($staff),
                'suggestions' => $staff_suggestions,
                'training_recommendations' => $this->getTrainingRecommendations($staff)
            ];
        }
        
        return [
            'staff_suggestions' => collect($suggestions)->sortBy('overall_score'),
            'improvement_categories' => $this->getImprovementCategories($suggestions)
        ];
    }

    /**
     * Calculate overall performance score
     */
    private function calculateOverallPerformanceScore($staff)
    {
        $sales_score = min($staff['daily_avg_sales'] / 10, 100); // Max score at 1000/day
        $transaction_score = min($staff['transactions_per_day'] * 5, 100); // Max score at 20/day
        $profit_score = min($staff['profit_margin'] * 2, 100); // Max score at 50% margin
        $customer_score = min($staff['customers_per_day'] * 10, 100); // Max score at 10/day
        
        return round(($sales_score + $transaction_score + $profit_score + $customer_score) / 4, 1);
    }

    /**
     * Get training recommendations
     */
    private function getTrainingRecommendations($staff)
    {
        $recommendations = [];
        
        if ($staff['profit_margin'] < 15) {
            $recommendations[] = 'Product Knowledge & Pricing Strategy Training';
        }
        
        if ($staff['transactions_per_day'] < 8) {
            $recommendations[] = 'Customer Engagement & Sales Techniques';
        }
        
        if ($staff['customers_per_day'] < 4) {
            $recommendations[] = 'Customer Retention & Relationship Building';
        }
        
        if ($staff['avg_sale_value'] < 100) {
            $recommendations[] = 'Upselling & Cross-selling Techniques';
        }
        
        return $recommendations;
    }

    /**
     * Get improvement categories
     */
    private function getImprovementCategories($suggestions)
    {
        $categories = [
            'sales' => 0,
            'transactions' => 0,
            'profit' => 0,
            'customers' => 0
        ];
        
        foreach ($suggestions as $staff) {
            foreach ($staff['suggestions'] as $suggestion) {
                $categories[$suggestion['type']]++;
            }
        }
        
        return $categories;
    }

    /**
     * Get productivity trends over time
     */
    private function getProductivityTrends($business_id, $start_date, $end_date, $selected_locations = [], $selected_staff = [])
    {
        $monthly_trends = DB::table('transactions as t')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($selected_locations)) {
            $monthly_trends->whereIn('t.location_id', $selected_locations);
        }

        if (!empty($selected_staff)) {
            $monthly_trends->whereIn('t.created_by', $selected_staff);
        }

        $trends = $monthly_trends->select([
            'u.id as staff_id',
            'u.first_name',
            'u.last_name',
            DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as period'),
            DB::raw('COUNT(t.id) as transactions'),
            DB::raw('SUM(t.final_total) as sales'),
            DB::raw('COUNT(DISTINCT t.contact_id) as customers')
        ])
        ->groupBy('u.id', 'u.first_name', 'u.last_name', 'period')
        ->orderBy('period')
        ->get();

        // Group by staff
        $staff_trends = [];
        foreach ($trends as $trend) {
            $staff_id = $trend->staff_id;
            $staff_name = trim($trend->first_name . ' ' . ($trend->last_name ?? ''));
            
            if (!isset($staff_trends[$staff_id])) {
                $staff_trends[$staff_id] = [
                    'staff_name' => $staff_name,
                    'trends' => []
                ];
            }
            
            $staff_trends[$staff_id]['trends'][] = [
                'period' => Carbon::createFromFormat('Y-m', $trend->period)->format('M Y'),
                'transactions' => $trend->transactions,
                'sales' => $trend->sales,
                'customers' => $trend->customers
            ];
        }

        return $staff_trends;
    }

    /**
     * Get staff comparison data
     */
    private function getStaffComparison($business_id, $start_date, $end_date, $selected_locations = [], $category_id = 'all', $selected_staff = [])
    {
        $staff_data = $this->getStaffSalesPerformance($business_id, $start_date, $end_date, $selected_locations, $category_id, $selected_staff);
        
        // Sort and rank staff
        $ranked_staff = collect($staff_data['staff_performance'])->sortByDesc('total_sales')->values();
        
        return [
            'staff_ranking' => $ranked_staff->map(function($staff, $index) {
                return array_merge($staff, [
                    'rank' => $index + 1,
                    'performance_rating' => $this->getPerformanceRating($staff['profit_margin'], $staff['daily_avg_sales'])
                ]);
            }),
            'comparison_metrics' => [
                'highest_sales' => $ranked_staff->first(),
                'highest_profit_margin' => $ranked_staff->sortByDesc('profit_margin')->first(),
                'most_transactions' => $ranked_staff->sortByDesc('total_transactions')->first(),
                'most_customers' => $ranked_staff->sortByDesc('unique_customers')->first()
            ]
        ];
    }

    /**
     * Get performance rating
     */
    private function getPerformanceRating($profit_margin, $daily_sales)
    {
        $score = 0;
        
        // Profit margin scoring
        if ($profit_margin >= 25) $score += 40;
        elseif ($profit_margin >= 20) $score += 30;
        elseif ($profit_margin >= 15) $score += 20;
        else $score += 10;
        
        // Sales scoring
        if ($daily_sales >= 1000) $score += 40;
        elseif ($daily_sales >= 750) $score += 30;
        elseif ($daily_sales >= 500) $score += 20;
        else $score += 10;
        
        // Additional factors (simplified)
        $score += 20; // Base score for participation
        
        if ($score >= 90) return 'Excellent';
        elseif ($score >= 75) return 'Good';
        elseif ($score >= 60) return 'Average';
        else return 'Needs Improvement';
    }
}