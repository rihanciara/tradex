<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseAnalysisController extends Controller
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
     * Display purchase analysis report
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        // Check if module is enabled
        // if (!$this->moduleUtil->isModuleEnabled('AdvancedReports')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $suppliers = $this->getSuppliers($business_id);

        return view('advancedreports::purchase-analysis.index', compact('business_locations', 'suppliers'));
    }

    /**
     * Get purchase analysis summary
     */
    public function getSummary(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $supplier_id = $request->supplier_id;

        // Get purchase trends
        $purchase_trends = $this->getPurchaseTrends($business_id, $start_date, $end_date, $location_id, $supplier_id);

        // Get cost optimization metrics
        $cost_optimization = $this->getCostOptimization($business_id, $start_date, $end_date, $location_id, $supplier_id);

        // Get return analysis
        $return_analysis = $this->getReturnAnalysis($business_id, $start_date, $end_date, $location_id, $supplier_id);

        // Get payment terms analysis
        $payment_terms = $this->getPaymentTermsAnalysis($business_id, $start_date, $end_date, $location_id, $supplier_id);

        // Get top performing suppliers
        $top_suppliers = $this->getTopSuppliers($business_id, $start_date, $end_date, $location_id, 5);

        // Get purchase volume trends
        $volume_trends = $this->getVolumetrends($business_id, $start_date, $end_date, $location_id, $supplier_id);

        return response()->json([
            'purchase_trends' => $purchase_trends,
            'cost_optimization' => $cost_optimization,
            'return_analysis' => $return_analysis,
            'payment_terms' => $payment_terms,
            'top_suppliers' => $top_suppliers,
            'volume_trends' => $volume_trends
        ]);
    }

    /**
     * Get supplier trends data for DataTable
     */
    public function getSupplierTrends(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select([
                'c.id as supplier_id',
                'c.name as supplier_name',
                'c.mobile as supplier_mobile',
                'bl.name as location_name',
                DB::raw('COUNT(t.id) as total_purchases'),
                DB::raw('SUM(t.final_total) as total_amount'),
                DB::raw('AVG(t.final_total) as average_amount'),
                DB::raw('MIN(t.final_total) as min_amount'),
                DB::raw('MAX(t.final_total) as max_amount'),
                DB::raw('MIN(t.transaction_date) as first_purchase'),
                DB::raw('MAX(t.transaction_date) as last_purchase'),
                DB::raw('DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) + 1 as days_active'),
                DB::raw('COUNT(t.id) / (DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) + 1) as purchase_frequency')
            ]);

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->groupBy('c.id', 'c.name', 'c.mobile', 'bl.name')
            ->orderBy('total_amount', 'desc');

        return DataTables::of($query)
            ->addColumn('supplier_display', function ($row) {
                $display = $row->supplier_name ?: 'Unknown Supplier';
                if ($row->supplier_mobile) {
                    $display .= ' (' . $row->supplier_mobile . ')';
                }
                return $display;
            })
            ->addColumn('formatted_total_amount', function ($row) {
                return $this->businessUtil->num_f($row->total_amount, true);
            })
            ->addColumn('formatted_average_amount', function ($row) {
                return $this->businessUtil->num_f($row->average_amount, true);
            })
            ->addColumn('formatted_min_amount', function ($row) {
                return $this->businessUtil->num_f($row->min_amount, true);
            })
            ->addColumn('formatted_max_amount', function ($row) {
                return $this->businessUtil->num_f($row->max_amount, true);
            })
            ->addColumn('purchase_frequency_display', function ($row) {
                return number_format($row->purchase_frequency, 2) . '/day';
            })
            ->addColumn('trend_indicator', function ($row) {
                // Simple trend based on recent activity
                $recent_purchases = DB::table('transactions')
                    ->where('contact_id', $row->supplier_id)
                    ->where('type', 'purchase')
                    ->where('status', 'received')
                    ->whereDate('transaction_date', '>=', Carbon::now()->subDays(30))
                    ->count();

                if ($recent_purchases > 5) {
                    return '<span class="label label-success">High Activity</span>';
                } elseif ($recent_purchases > 2) {
                    return '<span class="label label-warning">Medium Activity</span>';
                } else {
                    return '<span class="label label-danger">Low Activity</span>';
                }
            })
            ->rawColumns(['trend_indicator'])
            ->make(true);
    }

    /**
     * Get cost optimization data for DataTable
     */
    public function getCostOptimizationData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        $query = DB::table('purchase_lines as pl')
            ->leftJoin('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->leftJoin('variations as v', 'pl.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select([
                'pl.id as purchase_line_id',
                'p.id as product_id',
                'p.name as product_name',
                'v.sub_sku',
                'c.name as supplier_name',
                DB::raw('COUNT(pl.id) as purchase_count'),
                DB::raw('SUM(pl.quantity) as total_quantity'),
                DB::raw('AVG(pl.pp_without_discount) as avg_purchase_price'),
                DB::raw('MIN(pl.pp_without_discount) as min_purchase_price'),
                DB::raw('MAX(pl.pp_without_discount) as max_purchase_price'),
                DB::raw('(MAX(pl.pp_without_discount) - MIN(pl.pp_without_discount)) / MIN(pl.pp_without_discount) * 100 as price_variance_percentage'),
                DB::raw('SUM(pl.quantity * pl.pp_without_discount) as total_cost'),
                DB::raw('SUM(pl.quantity * (pl.pp_without_discount - (SELECT MIN(pp_without_discount) FROM purchase_lines pl2 WHERE pl2.variation_id = pl.variation_id))) as potential_savings')
            ]);

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->groupBy('p.id', 'p.name', 'v.sub_sku', 'c.name')
            ->having('purchase_count', '>', 1)
            ->orderBy('potential_savings', 'desc');

        return DataTables::of($query)
            ->addColumn('product_display', function ($row) {
                $display = $row->product_name;
                if ($row->sub_sku) {
                    $display .= ' (' . $row->sub_sku . ')';
                }
                return $display;
            })
            ->addColumn('formatted_avg_price', function ($row) {
                return $this->businessUtil->num_f($row->avg_purchase_price, true);
            })
            ->addColumn('formatted_min_price', function ($row) {
                return $this->businessUtil->num_f($row->min_purchase_price, true);
            })
            ->addColumn('formatted_max_price', function ($row) {
                return $this->businessUtil->num_f($row->max_purchase_price, true);
            })
            ->addColumn('formatted_total_cost', function ($row) {
                return $this->businessUtil->num_f($row->total_cost, true);
            })
            ->addColumn('formatted_potential_savings', function ($row) {
                return $this->businessUtil->num_f($row->potential_savings, true);
            })
            ->addColumn('variance_display', function ($row) {
                $percentage = number_format($row->price_variance_percentage, 1) . '%';
                $class = $row->price_variance_percentage > 20 ? 'text-danger' : ($row->price_variance_percentage > 10 ? 'text-warning' : 'text-success');
                return '<span class="' . $class . '">' . $percentage . '</span>';
            })
            ->addColumn('optimization_status', function ($row) {
                if ($row->potential_savings > 1000) {
                    return '<span class="label label-danger">High Priority</span>';
                } elseif ($row->potential_savings > 500) {
                    return '<span class="label label-warning">Medium Priority</span>';
                } else {
                    return '<span class="label label-success">Low Priority</span>';
                }
            })
            ->rawColumns(['variance_display', 'optimization_status'])
            ->make(true);
    }

    /**
     * Get purchase returns data for DataTable
     */
    public function getReturnAnalysisData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('transactions as pt', 't.return_parent_id', '=', 'pt.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase_return')
            ->where('t.status', 'final')
            ->select([
                't.id',
                't.transaction_date',
                't.ref_no',
                'c.name as supplier_name',
                'c.mobile as supplier_mobile',
                'bl.name as location_name',
                't.final_total as return_amount',
                'pt.ref_no as original_purchase_ref',
                'pt.final_total as original_purchase_amount',
                DB::raw('(t.final_total / pt.final_total) * 100 as return_percentage'),
                't.additional_notes as return_reason',
                DB::raw('DATEDIFF(t.transaction_date, pt.transaction_date) as days_between')
            ]);

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->orderBy('t.transaction_date', 'desc');

        return DataTables::of($query)
            ->addColumn('supplier_display', function ($row) {
                $display = $row->supplier_name ?: 'Unknown Supplier';
                if ($row->supplier_mobile) {
                    $display .= ' (' . $row->supplier_mobile . ')';
                }
                return $display;
            })
            ->addColumn('formatted_return_amount', function ($row) {
                return $this->businessUtil->num_f($row->return_amount, true);
            })
            ->addColumn('formatted_original_amount', function ($row) {
                return $this->businessUtil->num_f($row->original_purchase_amount, true);
            })
            ->addColumn('return_percentage_display', function ($row) {
                $percentage = number_format($row->return_percentage, 1) . '%';
                $class = $row->return_percentage > 50 ? 'text-danger' : ($row->return_percentage > 25 ? 'text-warning' : 'text-info');
                return '<span class="' . $class . '">' . $percentage . '</span>';
            })
            ->addColumn('timeline_status', function ($row) {
                if ($row->days_between <= 7) {
                    return '<span class="label label-success">Quick Return</span>';
                } elseif ($row->days_between <= 30) {
                    return '<span class="label label-warning">Standard Return</span>';
                } else {
                    return '<span class="label label-danger">Late Return</span>';
                }
            })
            ->addColumn('return_reason_display', function ($row) {
                return $row->return_reason ?: 'No reason specified';
            })
            ->rawColumns(['return_percentage_display', 'timeline_status'])
            ->make(true);
    }

    /**
     * Get supplier payment terms analysis data for DataTable
     */
    public function getPaymentTermsData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select([
                'c.id as supplier_id',
                'c.name as supplier_name',
                'c.pay_term_number',
                'c.pay_term_type',
                DB::raw('COUNT(t.id) as total_purchases'),
                DB::raw('SUM(t.final_total) as total_amount'),
                DB::raw('AVG(DATEDIFF(COALESCE((SELECT MAX(tp.paid_on) FROM transaction_payments tp WHERE tp.transaction_id = t.id), t.transaction_date), t.transaction_date)) as avg_payment_days'),
                DB::raw('COUNT(CASE WHEN t.payment_status = "paid" THEN 1 END) as paid_count'),
                DB::raw('COUNT(CASE WHEN t.payment_status = "partial" THEN 1 END) as partial_count'),
                DB::raw('COUNT(CASE WHEN t.payment_status = "due" THEN 1 END) as due_count'),
                DB::raw('SUM(CASE WHEN t.payment_status = "paid" THEN t.final_total ELSE 0 END) as paid_amount'),
                DB::raw('SUM(CASE WHEN t.payment_status != "paid" THEN (t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) ELSE 0 END) as outstanding_amount')
            ]);

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->groupBy('c.id', 'c.name', 'c.pay_term_number', 'c.pay_term_type')
            ->orderBy('total_amount', 'desc');

        return DataTables::of($query)
            ->addColumn('payment_terms_display', function ($row) {
                if ($row->pay_term_number && $row->pay_term_type) {
                    return $row->pay_term_number . ' ' . ucfirst($row->pay_term_type);
                }
                return 'No terms specified';
            })
            ->addColumn('formatted_total_amount', function ($row) {
                return $this->businessUtil->num_f($row->total_amount, true);
            })
            ->addColumn('formatted_paid_amount', function ($row) {
                return $this->businessUtil->num_f($row->paid_amount, true);
            })
            ->addColumn('formatted_outstanding_amount', function ($row) {
                return $this->businessUtil->num_f($row->outstanding_amount, true);
            })
            ->addColumn('payment_performance', function ($row) {
                $performance = $row->total_purchases > 0 ? ($row->paid_count / $row->total_purchases) * 100 : 0;
                $class = $performance >= 80 ? 'success' : ($performance >= 60 ? 'warning' : 'danger');
                return '<div class="progress progress-xs">
                          <div class="progress-bar progress-bar-' . $class . '" style="width: ' . $performance . '%"></div>
                        </div>
                        <span class="text-' . $class . '">' . number_format($performance, 1) . '%</span>';
            })
            ->addColumn('avg_payment_days_display', function ($row) {
                $days = number_format($row->avg_payment_days, 0);
                $expected = $row->pay_term_number ?: 30;
                $class = $row->avg_payment_days <= $expected ? 'text-success' : 'text-danger';
                return '<span class="' . $class . '">' . $days . ' days</span>';
            })
            ->addColumn('compliance_status', function ($row) {
                $expected_days = $row->pay_term_number ?: 30;
                if ($row->avg_payment_days <= $expected_days) {
                    return '<span class="label label-success">Compliant</span>';
                } elseif ($row->avg_payment_days <= $expected_days + 7) {
                    return '<span class="label label-warning">Delayed</span>';
                } else {
                    return '<span class="label label-danger">Non-Compliant</span>';
                }
            })
            ->rawColumns(['payment_performance', 'avg_payment_days_display', 'compliance_status'])
            ->make(true);
    }

    // Private helper methods

    private function getPurchaseTrends($business_id, $start_date, $end_date, $location_id, $supplier_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received');

        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $query->where('t.contact_id', $supplier_id);
        }

        $total_purchases = $query->count();
        $total_amount = $query->sum('t.final_total');
        $average_purchase = $total_purchases > 0 ? $total_amount / $total_purchases : 0;

        return [
            'total_purchases' => $total_purchases,
            'total_amount' => $total_amount ?: 0,
            'average_purchase' => $average_purchase,
            'formatted_total_amount' => $this->businessUtil->num_f($total_amount ?: 0, true),
            'formatted_average_purchase' => $this->businessUtil->num_f($average_purchase, true)
        ];
    }

    private function getCostOptimization($business_id, $start_date, $end_date, $location_id, $supplier_id)
    {
        // Calculate potential savings from price variations
        $savings_query = DB::table('purchase_lines as pl')
            ->leftJoin('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received');

        if (!empty($start_date)) {
            $savings_query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $savings_query->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $savings_query->where('t.location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $savings_query->where('t.contact_id', $supplier_id);
        }

        $potential_savings = $savings_query->selectRaw('
            SUM(pl.quantity * (pl.pp_without_discount - (
                SELECT MIN(pp_without_discount) 
                FROM purchase_lines pl2 
                LEFT JOIN transactions t2 ON pl2.transaction_id = t2.id
                WHERE pl2.variation_id = pl.variation_id 
                AND t2.business_id = t.business_id
                AND t2.status = "received"
            ))) as savings
        ')->value('savings') ?: 0;

        $high_variance_products = DB::table('purchase_lines as pl')
            ->leftJoin('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->leftJoin('variations as v', 'pl.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select('pl.variation_id')
            ->groupBy('pl.variation_id')
            ->havingRaw('(MAX(pl.pp_without_discount) - MIN(pl.pp_without_discount)) / MIN(pl.pp_without_discount) > 0.1')
            ->count();

        return [
            'potential_savings' => $potential_savings,
            'high_variance_products' => $high_variance_products,
            'formatted_potential_savings' => $this->businessUtil->num_f($potential_savings, true)
        ];
    }

    private function getReturnAnalysis($business_id, $start_date, $end_date, $location_id, $supplier_id)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase_return')
            ->where('t.status', 'final');

        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $query->where('t.contact_id', $supplier_id);
        }

        $total_returns = $query->count();
        $total_return_amount = $query->sum('t.final_total');

        // Get return rate
        $total_purchases = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received');

        if (!empty($start_date)) {
            $total_purchases->whereDate('transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $total_purchases->whereDate('transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $total_purchases->where('location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $total_purchases->where('contact_id', $supplier_id);
        }

        $total_purchase_count = $total_purchases->count();
        $return_rate = $total_purchase_count > 0 ? ($total_returns / $total_purchase_count) * 100 : 0;

        return [
            'total_returns' => $total_returns,
            'total_return_amount' => $total_return_amount ?: 0,
            'return_rate' => $return_rate,
            'formatted_total_return_amount' => $this->businessUtil->num_f($total_return_amount ?: 0, true),
            'formatted_return_rate' => number_format($return_rate, 2) . '%'
        ];
    }

    private function getPaymentTermsAnalysis($business_id, $start_date, $end_date, $location_id, $supplier_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received');

        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $query->where('t.contact_id', $supplier_id);
        }

        $on_time_payments = $query->clone()
            ->whereRaw('DATEDIFF(COALESCE((SELECT MAX(tp.paid_on) FROM transaction_payments tp WHERE tp.transaction_id = t.id), t.transaction_date), t.transaction_date) <= COALESCE(c.pay_term_number, 30)')
            ->where('t.payment_status', 'paid')
            ->count();

        $total_completed_payments = $query->clone()->where('t.payment_status', 'paid')->count();
        $compliance_rate = $total_completed_payments > 0 ? ($on_time_payments / $total_completed_payments) * 100 : 0;

        $outstanding_amount = $query->clone()
            ->where('t.payment_status', '!=', 'paid')
            ->sum(DB::raw('t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)'));

        return [
            'on_time_payments' => $on_time_payments,
            'compliance_rate' => $compliance_rate,
            'outstanding_amount' => $outstanding_amount ?: 0,
            'formatted_compliance_rate' => number_format($compliance_rate, 1) . '%',
            'formatted_outstanding_amount' => $this->businessUtil->num_f($outstanding_amount ?: 0, true)
        ];
    }

    private function getTopSuppliers($business_id, $start_date, $end_date, $location_id, $limit)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select([
                'c.name as supplier_name',
                DB::raw('SUM(t.final_total) as total_amount'),
                DB::raw('COUNT(t.id) as purchase_count')
            ]);

        if (!empty($start_date)) {
            $query->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        return $query->groupBy('c.id', 'c.name')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($supplier) {
                $supplier->formatted_total_amount = $this->businessUtil->num_f($supplier->total_amount, true);
                return $supplier;
            });
    }

    private function getVolumetrends($business_id, $start_date, $end_date, $location_id, $supplier_id)
    {
        // Get monthly purchase volume trends
        $trends = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select([
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as month'),
                DB::raw('COUNT(t.id) as purchase_count'),
                DB::raw('SUM(t.final_total) as total_amount')
            ]);

        if (!empty($start_date)) {
            $trends->whereDate('t.transaction_date', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $trends->whereDate('t.transaction_date', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $trends->where('t.location_id', $location_id);
        }
        if (!empty($supplier_id)) {
            $trends->where('t.contact_id', $supplier_id);
        }

        return $trends->groupBy(DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m")'))
            ->orderBy('month')
            ->get()
            ->map(function ($trend) {
                $trend->formatted_total_amount = $this->businessUtil->num_f($trend->total_amount, true);
                return $trend;
            });
    }

    private function getSuppliers($business_id)
    {
        return DB::table('contacts as c')
            ->where('c.business_id', $business_id)
            ->where('c.type', 'supplier')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transactions as t')
                    ->whereRaw('t.contact_id = c.id')
                    ->where('t.type', 'purchase')
                    ->where('t.status', 'received');
            })
            ->select('c.id', 'c.name')
            ->orderBy('c.name')
            ->pluck('name', 'id')
            ->toArray();
    }
}