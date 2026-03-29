<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use App\Contact;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceController extends Controller
{
    /**
     * Display the supplier performance report dashboard
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        // Get currency information
        $currency_symbol = session('currency')['symbol'] ?? ($business->currency->symbol ?? '');
        $currency_placement = session('business.currency_symbol_placement') ?? 'before';

        // Get suppliers for filtering
        $suppliers = Contact::where('business_id', $business_id)
                           ->where('type', 'supplier')
                           ->select('id', DB::raw('COALESCE(supplier_business_name, name) as display_name'))
                           ->get()
                           ->pluck('display_name', 'id')
                           ->prepend(__('lang_v1.all'), '');

        // Performance rating categories
        $rating_categories = [
            'all' => __('All Suppliers'),
            'excellent' => __('Excellent (90-100%)'),
            'good' => __('Good (75-89%)'),
            'average' => __('Average (60-74%)'),
            'poor' => __('Poor (<60%)'),
        ];

        // Performance metrics for filtering
        $performance_metrics = [
            'overall' => __('advancedreports::lang.overall_score'),
            'delivery' => __('advancedreports::lang.delivery_performance'),
            'quality' => __('advancedreports::lang.quality_assessment'),
            'payment' => __('advancedreports::lang.payment_compliance'),
            'risk' => __('advancedreports::lang.supplier_risk_analysis'),
        ];

        return view('advancedreports::supplier-performance.index', compact(
            'business',
            'suppliers',
            'rating_categories',
            'performance_metrics',
            'currency_symbol',
            'currency_placement'
        ));
    }

    /**
     * Get supplier performance data for AJAX calls
     */
    public function getSupplierPerformanceData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $supplier_id = $request->input('supplier_id');
        $performance_metric = $request->input('performance_metric', 'overall');

        try {
            $analytics = [
                'overview' => $this->getSupplierOverview($business_id, $start_date, $end_date, $supplier_id),
                'delivery_performance' => $this->getDeliveryPerformance($business_id, $start_date, $end_date, $supplier_id),
                'quality_assessment' => $this->getQualityAssessment($business_id, $start_date, $end_date, $supplier_id),
                'payment_compliance' => $this->getPaymentCompliance($business_id, $start_date, $end_date, $supplier_id),
                'risk_analysis' => $this->getSupplierRiskAnalysis($business_id, $start_date, $end_date, $supplier_id),
                'rankings' => $this->getSupplierRankings($business_id, $start_date, $end_date, $performance_metric),
                'insights' => $this->getPerformanceInsights($business_id, $start_date, $end_date)
            ];

            return response()->json($analytics);
        } catch (\Exception $e) {
            \Log::error('Supplier Performance Analytics Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading supplier performance data'
            ], 500);
        }
    }

    /**
     * Get supplier performance analytics
     */
    public function getSupplierPerformance(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $supplier_id = $request->input('supplier_id');

        $analytics = [
            'overview' => $this->getSupplierOverview($business_id, $start_date, $end_date, $supplier_id),
            'delivery_performance' => $this->getDeliveryPerformance($business_id, $start_date, $end_date, $supplier_id),
            'quality_assessment' => $this->getQualityAssessment($business_id, $start_date, $end_date, $supplier_id),
            'payment_compliance' => $this->getPaymentCompliance($business_id, $start_date, $end_date, $supplier_id),
            'risk_analysis' => $this->getSupplierRiskAnalysis($business_id, $start_date, $end_date, $supplier_id),
            'comparative_performance' => $this->getComparativePerformance($business_id, $start_date, $end_date),
        ];

        return response()->json($analytics);
    }

    /**
     * Get supplier overview metrics
     */
    private function getSupplierOverview($business_id, $start_date, $end_date, $supplier_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($supplier_id && $supplier_id !== '') {
            $query->where('t.contact_id', $supplier_id);
        }

        $overview = $query->select([
            DB::raw('COUNT(DISTINCT c.id) as total_suppliers'),
            DB::raw('COUNT(DISTINCT t.id) as total_orders'),
            DB::raw('COALESCE(SUM(t.final_total), 0) as total_spent'),
            DB::raw('COALESCE(AVG(t.final_total), 0) as avg_order_value'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "paid" THEN 1 END) as paid_orders'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "due" THEN 1 END) as due_orders'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "partial" THEN 1 END) as partial_orders'),
            DB::raw('COALESCE(AVG(CASE WHEN t.delivery_date IS NOT NULL AND t.transaction_date IS NOT NULL 
                      THEN DATEDIFF(t.delivery_date, t.transaction_date) END), 0) as avg_delivery_days'),
        ])->first();

        // Calculate payment compliance rate
        $payment_compliance_rate = $overview->total_orders > 0 ? 
            ($overview->paid_orders / $overview->total_orders) * 100 : 0;

        return [
            'total_suppliers' => $overview->total_suppliers,
            'total_orders' => $overview->total_orders,
            'total_spent' => $overview->total_spent,
            'avg_order_value' => $overview->avg_order_value,
            'payment_compliance_rate' => $payment_compliance_rate,
            'avg_delivery_days' => round($overview->avg_delivery_days, 1),
            'paid_orders' => $overview->paid_orders,
            'due_orders' => $overview->due_orders,
            'partial_orders' => $overview->partial_orders,
        ];
    }

    /**
     * Get delivery performance metrics
     */
    private function getDeliveryPerformance($business_id, $start_date, $end_date, $supplier_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($supplier_id && $supplier_id !== '') {
            $query->where('t.contact_id', $supplier_id);
        }

        $delivery_metrics = $query->select([
            'c.id as supplier_id',
            DB::raw('COALESCE(c.supplier_business_name, c.name) as supplier_name'),
            DB::raw('COUNT(t.id) as total_orders'),
            DB::raw('COUNT(CASE WHEN t.status = "received" THEN 1 END) as delivered_orders'),
            DB::raw('COUNT(CASE WHEN t.status = "pending" THEN 1 END) as pending_orders'),
            DB::raw('COUNT(CASE WHEN t.status = "ordered" THEN 1 END) as ordered_status'),
            DB::raw('COALESCE(AVG(CASE WHEN t.delivery_date IS NOT NULL AND t.transaction_date IS NOT NULL 
                      THEN DATEDIFF(t.delivery_date, t.transaction_date) END), 0) as avg_delivery_days'),
            DB::raw('COUNT(CASE WHEN t.delivery_date IS NOT NULL AND t.delivery_date <= DATE_ADD(t.transaction_date, INTERVAL 7 DAY) THEN 1 END) as on_time_deliveries'),
        ])
        ->groupBy('c.id', 'supplier_name')
        ->get();

        return $delivery_metrics->map(function($metric) {
            $on_time_rate = $metric->total_orders > 0 ? 
                ($metric->on_time_deliveries / $metric->total_orders) * 100 : 0;
            $delivery_rate = $metric->total_orders > 0 ? 
                ($metric->delivered_orders / $metric->total_orders) * 100 : 0;

            return [
                'supplier_id' => $metric->supplier_id,
                'supplier_name' => $metric->supplier_name,
                'total_orders' => $metric->total_orders,
                'delivered_orders' => $metric->delivered_orders,
                'pending_orders' => $metric->pending_orders,
                'delivery_rate' => round($delivery_rate, 2),
                'on_time_rate' => round($on_time_rate, 2),
                'avg_delivery_days' => round($metric->avg_delivery_days, 1),
                'performance_score' => round(($delivery_rate + $on_time_rate) / 2, 1),
            ];
        })->sortByDesc('performance_score')->values();
    }

    /**
     * Get quality assessment metrics
     */
    private function getQualityAssessment($business_id, $start_date, $end_date, $supplier_id = null)
    {
        // Quality metrics based on returns, adjustments, and transaction patterns
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('transactions as rt', function($join) {
                $join->on('rt.return_parent_id', '=', 't.id')
                     ->where('rt.type', 'purchase_return');
            })
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($supplier_id && $supplier_id !== '') {
            $query->where('t.contact_id', $supplier_id);
        }

        $quality_metrics = $query->select([
            'c.id as supplier_id',
            DB::raw('COALESCE(c.supplier_business_name, c.name) as supplier_name'),
            DB::raw('COUNT(DISTINCT t.id) as total_orders'),
            DB::raw('COALESCE(SUM(t.final_total), 0) as total_value'),
            DB::raw('COUNT(DISTINCT rt.id) as return_count'),
            DB::raw('COALESCE(SUM(rt.final_total), 0) as return_value'),
            DB::raw('COUNT(CASE WHEN t.additional_notes IS NOT NULL AND t.additional_notes != "" THEN 1 END) as orders_with_notes'),
            DB::raw('COUNT(CASE WHEN t.discount_amount > 0 THEN 1 END) as discounted_orders'),
        ])
        ->groupBy('c.id', 'supplier_name')
        ->get();

        return $quality_metrics->map(function($metric) {
            $return_rate = $metric->total_orders > 0 ? 
                ($metric->return_count / $metric->total_orders) * 100 : 0;
            $return_value_rate = $metric->total_value > 0 ? 
                ($metric->return_value / $metric->total_value) * 100 : 0;
            $issue_rate = $metric->total_orders > 0 ? 
                ($metric->orders_with_notes / $metric->total_orders) * 100 : 0;
            
            // Quality score: lower returns and issues = higher score
            $quality_score = 100 - ($return_rate * 2) - ($issue_rate * 1.5) - ($return_value_rate * 0.5);
            $quality_score = max(0, min(100, $quality_score)); // Keep between 0-100

            return [
                'supplier_id' => $metric->supplier_id,
                'supplier_name' => $metric->supplier_name,
                'total_orders' => $metric->total_orders,
                'total_value' => $metric->total_value,
                'return_count' => $metric->return_count,
                'return_rate' => round($return_rate, 2),
                'return_value_rate' => round($return_value_rate, 2),
                'issue_rate' => round($issue_rate, 2),
                'quality_score' => round($quality_score, 1),
                'quality_grade' => $this->getQualityGrade($quality_score),
            ];
        })->sortByDesc('quality_score')->values();
    }

    /**
     * Get payment term compliance
     */
    private function getPaymentCompliance($business_id, $start_date, $end_date, $supplier_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($supplier_id && $supplier_id !== '') {
            $query->where('t.contact_id', $supplier_id);
        }

        $payment_metrics = $query->select([
            'c.id as supplier_id',
            DB::raw('COALESCE(c.supplier_business_name, c.name) as supplier_name'),
            DB::raw('COUNT(DISTINCT t.id) as total_orders'),
            DB::raw('COALESCE(SUM(t.final_total), 0) as total_amount'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "paid" THEN 1 END) as paid_orders'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "due" THEN 1 END) as due_orders'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "partial" THEN 1 END) as partial_orders'),
            DB::raw('COALESCE(SUM(CASE WHEN t.payment_status = "paid" THEN t.final_total END), 0) as paid_amount'),
            DB::raw('COALESCE(SUM(CASE WHEN t.payment_status = "due" THEN t.final_total END), 0) as due_amount'),
            DB::raw('COALESCE(AVG(CASE WHEN tp.paid_on IS NOT NULL AND t.transaction_date IS NOT NULL 
                      THEN DATEDIFF(tp.paid_on, t.transaction_date) END), 0) as avg_payment_days'),
        ])
        ->groupBy('c.id', 'supplier_name')
        ->get();

        return $payment_metrics->map(function($metric) {
            $payment_rate = $metric->total_orders > 0 ? 
                ($metric->paid_orders / $metric->total_orders) * 100 : 0;
            $amount_paid_rate = $metric->total_amount > 0 ? 
                ($metric->paid_amount / $metric->total_amount) * 100 : 0;
            
            // Payment compliance score based on timely payments
            $timeliness_score = $metric->avg_payment_days <= 30 ? 100 : 
                               ($metric->avg_payment_days <= 60 ? 80 : 
                               ($metric->avg_payment_days <= 90 ? 60 : 40));
            
            $compliance_score = ($payment_rate * 0.6) + ($timeliness_score * 0.4);

            return [
                'supplier_id' => $metric->supplier_id,
                'supplier_name' => $metric->supplier_name,
                'total_orders' => $metric->total_orders,
                'total_amount' => $metric->total_amount,
                'paid_orders' => $metric->paid_orders,
                'due_orders' => $metric->due_orders,
                'partial_orders' => $metric->partial_orders,
                'payment_rate' => round($payment_rate, 2),
                'amount_paid_rate' => round($amount_paid_rate, 2),
                'avg_payment_days' => round($metric->avg_payment_days, 1),
                'compliance_score' => round($compliance_score, 1),
                'compliance_grade' => $this->getComplianceGrade($compliance_score),
            ];
        })->sortByDesc('compliance_score')->values();
    }

    /**
     * Get supplier risk analysis
     */
    private function getSupplierRiskAnalysis($business_id, $start_date, $end_date, $supplier_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($supplier_id && $supplier_id !== '') {
            $query->where('t.contact_id', $supplier_id);
        }

        $risk_metrics = $query->select([
            'c.id as supplier_id',
            DB::raw('COALESCE(c.supplier_business_name, c.name) as supplier_name'),
            DB::raw('COUNT(t.id) as total_orders'),
            DB::raw('COALESCE(SUM(t.final_total), 0) as total_value'),
            DB::raw('COALESCE(SUM(CASE WHEN t.payment_status = "due" THEN t.final_total END), 0) as outstanding_amount'),
            DB::raw('COUNT(CASE WHEN t.payment_status = "due" THEN 1 END) as overdue_orders'),
            DB::raw('STDDEV(t.final_total) as order_value_variance'),
            DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as active_days'),
            DB::raw('DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) + 1 as relationship_days'),
        ])
        ->groupBy('c.id', 'supplier_name')
        ->get();

        return $risk_metrics->map(function($metric) use ($start_date, $end_date) {
            // Risk factors calculation
            $concentration_risk = ($metric->total_value / $this->getTotalPurchaseValue($start_date, $end_date)) * 100;
            $dependency_score = min($concentration_risk * 2, 100); // Higher concentration = higher risk
            
            $payment_risk = $metric->total_orders > 0 ? 
                ($metric->overdue_orders / $metric->total_orders) * 100 : 0;
            
            $consistency_risk = $metric->order_value_variance > ($metric->total_value / $metric->total_orders) ? 
                min(($metric->order_value_variance / ($metric->total_value / $metric->total_orders)) * 10, 100) : 0;
            
            $relationship_stability = $metric->relationship_days > 0 ? 
                min(($metric->active_days / $metric->relationship_days) * 100, 100) : 0;
            
            // Overall risk score (lower is better)
            $risk_score = ($payment_risk * 0.4) + ($dependency_score * 0.3) + 
                         ($consistency_risk * 0.2) + ((100 - $relationship_stability) * 0.1);

            return [
                'supplier_id' => $metric->supplier_id,
                'supplier_name' => $metric->supplier_name,
                'total_orders' => $metric->total_orders,
                'total_value' => $metric->total_value,
                'outstanding_amount' => $metric->outstanding_amount,
                'concentration_risk' => round($concentration_risk, 2),
                'payment_risk' => round($payment_risk, 2),
                'consistency_risk' => round($consistency_risk, 2),
                'relationship_stability' => round($relationship_stability, 2),
                'overall_risk_score' => round($risk_score, 1),
                'risk_level' => $this->getRiskLevel($risk_score),
            ];
        })->sortBy('overall_risk_score')->values();
    }

    /**
     * Get comparative performance across suppliers
     */
    private function getComparativePerformance($business_id, $start_date, $end_date)
    {
        $suppliers = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'c.id as supplier_id',
                DB::raw('COALESCE(c.supplier_business_name, c.name) as supplier_name'),
                DB::raw('COUNT(t.id) as total_orders'),
                DB::raw('SUM(t.final_total) as total_spent'),
                DB::raw('AVG(t.final_total) as avg_order_value'),
                DB::raw('COUNT(CASE WHEN t.payment_status = "paid" THEN 1 END) as paid_orders'),
            ])
            ->groupBy('c.id', 'supplier_name')
            ->orderBy('total_spent', 'desc')
            ->get();

        return $suppliers->map(function($supplier) {
            $payment_rate = $supplier->total_orders > 0 ? 
                ($supplier->paid_orders / $supplier->total_orders) * 100 : 0;

            return [
                'supplier_id' => $supplier->supplier_id,
                'supplier_name' => $supplier->supplier_name,
                'total_orders' => $supplier->total_orders,
                'total_spent' => $supplier->total_spent,
                'avg_order_value' => round($supplier->avg_order_value, 2),
                'payment_rate' => round($payment_rate, 2),
                'market_share' => 0, // Will be calculated in the view
            ];
        });
    }

    /**
     * Get supplier rankings
     */
    private function getSupplierRankings($business_id, $start_date, $end_date, $performance_metric = 'overall')
    {
        $rankings = collect();

        // Get all suppliers with performance data
        $suppliers = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->select('id', 'name', 'supplier_business_name')
            ->get();

        foreach ($suppliers as $supplier) {
            $delivery = $this->getDeliveryPerformance($business_id, $start_date, $end_date, $supplier->id);
            $quality = $this->getQualityAssessment($business_id, $start_date, $end_date, $supplier->id);
            $payment = $this->getPaymentCompliance($business_id, $start_date, $end_date, $supplier->id);
            $risk = $this->getSupplierRiskAnalysis($business_id, $start_date, $end_date, $supplier->id);

            // Get first record from each collection (since we're filtering by supplier_id)
            $delivery_data = $delivery->first() ?: ['on_time_rate' => 0, 'performance_score' => 0, 'total_orders' => 0, 'avg_delivery_days' => 0];
            $quality_data = $quality->first() ?: ['quality_score' => 0];
            $payment_data = $payment->first() ?: ['compliance_score' => 0];
            $risk_data = $risk->first() ?: ['overall_risk_score' => 0];

            // Calculate overall score
            $overall_score = ($delivery_data['on_time_rate'] * 0.3 + 
                            $quality_data['quality_score'] * 0.25 + 
                            $payment_data['compliance_score'] * 0.25 + 
                            (100 - $risk_data['overall_risk_score']) * 0.2);

            $rankings->push([
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_business_name ?: $supplier->name,
                'delivery_score' => $delivery_data['on_time_rate'],
                'quality_score' => $quality_data['quality_score'],
                'payment_score' => $payment_data['compliance_score'],
                'risk_score' => $risk_data['overall_risk_score'],
                'overall_score' => $overall_score,
                'orders_count' => $delivery_data['total_orders'],
                'avg_delivery_days' => $delivery_data['avg_delivery_days'],
                'performance_grade' => $this->getPerformanceGrade($overall_score)
            ]);
        }

        // Sort by the specified metric
        $sort_field = $performance_metric === 'overall' ? 'overall_score' : $performance_metric . '_score';
        $rankings = $rankings->sortByDesc($sort_field)->values();

        return $rankings->take(50)->toArray(); // Limit to top 50 suppliers
    }

    /**
     * Get performance insights
     */
    private function getPerformanceInsights($business_id, $start_date, $end_date)
    {
        $insights = [];

        // Get top performers
        $rankings = $this->getSupplierRankings($business_id, $start_date, $end_date);
        
        if (!empty($rankings)) {
            $insights['top_performer'] = $rankings[0];
            $insights['average_score'] = collect($rankings)->avg('overall_score');
            $insights['excellent_suppliers'] = collect($rankings)->where('overall_score', '>=', 90)->count();
            $insights['poor_suppliers'] = collect($rankings)->where('overall_score', '<', 60)->count();
            
            // Key recommendations
            $insights['recommendations'] = [];
            if ($insights['poor_suppliers'] > 0) {
                $insights['recommendations'][] = "Consider reviewing " . $insights['poor_suppliers'] . " underperforming supplier(s)";
            }
            if ($insights['excellent_suppliers'] > 0) {
                $insights['recommendations'][] = "Strengthen relationships with " . $insights['excellent_suppliers'] . " top-performing supplier(s)";
            }
        } else {
            $insights = [
                'top_performer' => null,
                'average_score' => 0,
                'excellent_suppliers' => 0,
                'poor_suppliers' => 0,
                'recommendations' => ['No supplier data available for the selected period']
            ];
        }

        return $insights;
    }

    /**
     * Get performance grade based on score
     */
    private function getPerformanceGrade($score)
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Average';
        return 'Poor';
    }

    /**
     * Helper methods for grading and classification
     */
    private function getQualityGrade($score)
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B+';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        return 'D';
    }

    private function getComplianceGrade($score)
    {
        if ($score >= 95) return 'Excellent';
        if ($score >= 85) return 'Good';
        if ($score >= 70) return 'Average';
        if ($score >= 50) return 'Fair';
        return 'Poor';
    }

    private function getRiskLevel($score)
    {
        if ($score <= 20) return 'Low';
        if ($score <= 40) return 'Medium';
        if ($score <= 60) return 'High';
        return 'Critical';
    }

    private function getTotalPurchaseValue($start_date, $end_date)
    {
        return DB::table('transactions')
            ->where('type', 'purchase')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->sum('final_total') ?: 1; // Avoid division by zero
    }

    /**
     * Export supplier performance data
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $report_type = $request->input('report_type', 'overview');

        $filename = 'supplier_performance_' . $report_type . '_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($business_id, $report_type, $start_date, $end_date) {
            $file = fopen('php://output', 'w');
            
            if ($report_type === 'delivery') {
                fputcsv($file, [
                    'Supplier Name',
                    'Total Orders',
                    'Delivered Orders',
                    'Delivery Rate (%)',
                    'On Time Rate (%)',
                    'Avg Delivery Days',
                    'Performance Score'
                ]);

                $delivery_data = $this->getDeliveryPerformance($business_id, $start_date, $end_date);
                foreach ($delivery_data as $row) {
                    fputcsv($file, [
                        $row['supplier_name'],
                        $row['total_orders'],
                        $row['delivered_orders'],
                        $row['delivery_rate'],
                        $row['on_time_rate'],
                        $row['avg_delivery_days'],
                        $row['performance_score']
                    ]);
                }
            } elseif ($report_type === 'quality') {
                fputcsv($file, [
                    'Supplier Name',
                    'Total Orders',
                    'Total Value',
                    'Return Rate (%)',
                    'Issue Rate (%)',
                    'Quality Score',
                    'Quality Grade'
                ]);

                $quality_data = $this->getQualityAssessment($business_id, $start_date, $end_date);
                foreach ($quality_data as $row) {
                    fputcsv($file, [
                        $row['supplier_name'],
                        $row['total_orders'],
                        number_format($row['total_value'], 2),
                        $row['return_rate'],
                        $row['issue_rate'],
                        $row['quality_score'],
                        $row['quality_grade']
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}