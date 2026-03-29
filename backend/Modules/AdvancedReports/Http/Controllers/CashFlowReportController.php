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
use Carbon\Carbon;

class CashFlowReportController extends Controller
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
     * Display cash flow report index page
     */
    public function index(Request $request)
    {
        // Permission check can be added later
        // if (!auth()->user()->can('view_cash_flow_report')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $locations = BusinessLocation::forDropdown($business_id, true);
        $business_locations = ['all' => __('All')];
        if ($locations) {
            foreach ($locations as $key => $value) {
                $business_locations[$key] = $value;
            }
        }
        
        // Get payment methods
        $payment_methods = $this->getPaymentMethods($business_id);
        
        // Get customers with outstanding receivables  
        $customers = Contact::customersDropdown($business_id, false);
        
        // Get suppliers with outstanding payables
        $suppliers = Contact::suppliersDropdown($business_id, false);

        $data = compact('business_locations', 'payment_methods', 'customers', 'suppliers');
        
        return view('advancedreports::cash-flow.index', $data);
    }

    /**
     * Get cash flow summary data
     */
    public function getSummary(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        // Set default date range if not provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subDays(30)->format('Y-m-d');
        }

        // Get cash inflows
        $cash_inflows = $this->getCashInflows($business_id, $start_date, $end_date, $location_id);
        
        // Get cash outflows
        $cash_outflows = $this->getCashOutflows($business_id, $start_date, $end_date, $location_id);
        
        // Calculate net cash flow
        $net_cash_flow = $cash_inflows['total'] - $cash_outflows['total'];
        
        // Get opening cash balance
        $opening_balance = $this->getOpeningCashBalance($business_id, $start_date, $location_id);
        
        // Get closing cash balance
        $closing_balance = $opening_balance + $net_cash_flow;
        
        // Get outstanding receivables
        $receivables = $this->getOutstandingReceivables($business_id, $location_id);
        
        // Get outstanding payables
        $payables = $this->getOutstandingPayables($business_id, $location_id);
        
        // Get payment method breakdown
        $payment_methods = $this->getPaymentMethodSummary($business_id, $start_date, $end_date, $location_id);
        
        // Get previous period data for comparison
        $previous_start = Carbon::parse($start_date)->subDays(Carbon::parse($end_date)->diffInDays(Carbon::parse($start_date)))->format('Y-m-d');
        $previous_end = Carbon::parse($start_date)->subDay()->format('Y-m-d');
        
        $previous_inflows = $this->getCashInflows($business_id, $previous_start, $previous_end, $location_id);
        $previous_outflows = $this->getCashOutflows($business_id, $previous_start, $previous_end, $location_id);
        $previous_net_flow = $previous_inflows['total'] - $previous_outflows['total'];

        return response()->json([
            'cash_inflows' => $cash_inflows,
            'cash_outflows' => $cash_outflows,
            'net_cash_flow' => $net_cash_flow,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'receivables' => $receivables,
            'payables' => $payables,
            'payment_methods' => $payment_methods,
            'previous_net_flow' => $previous_net_flow,
            'currency_symbol' => session()->get('business.currency_symbol'),
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Get daily cash flow data
     */
    public function getDailyCashFlow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        // Set default date range if not provided (last 30 days)
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subDays(30)->format('Y-m-d');
        }

        $query = DB::table('transactions as t')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final')
            ->whereIn('t.type', ['sell', 'purchase', 'expense'])
            ->select([
                DB::raw('DATE(t.transaction_date) as date'),
                DB::raw('SUM(CASE WHEN t.type = "sell" THEN COALESCE(tp.amount, 0) ELSE 0 END) as cash_inflow'),
                DB::raw('SUM(CASE WHEN t.type IN ("purchase", "expense") THEN COALESCE(tp.amount, 0) ELSE 0 END) as cash_outflow'),
                DB::raw('(SUM(CASE WHEN t.type = "sell" THEN COALESCE(tp.amount, 0) ELSE 0 END) - SUM(CASE WHEN t.type IN ("purchase", "expense") THEN COALESCE(tp.amount, 0) ELSE 0 END)) as net_flow')
            ]);

        // Apply date filters (always applied since we ensure dates are set above)
        $query->whereDate('t.transaction_date', '>=', $start_date)
              ->whereDate('t.transaction_date', '<=', $end_date);
        
        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->groupBy(DB::raw('DATE(t.transaction_date)'))
              ->orderBy('date', 'desc');






        return DataTables::of($query)
            ->addColumn('formatted_date', function ($row) {
                return Carbon::parse($row->date)->format('M d, Y');
            })
            ->addColumn('formatted_cash_inflow', function ($row) {
                return '<span class="cash_inflow" data-orig-value="'.$row->cash_inflow.'">'.$this->businessUtil->num_f($row->cash_inflow, true).'</span>';
            })
            ->addColumn('formatted_cash_outflow', function ($row) {
                return '<span class="cash_outflow" data-orig-value="'.$row->cash_outflow.'">'.$this->businessUtil->num_f($row->cash_outflow, true).'</span>';
            })
            ->addColumn('formatted_net_flow', function ($row) {
                return '<span class="net_flow" data-orig-value="'.$row->net_flow.'">'.$this->businessUtil->num_f($row->net_flow, true).'</span>';
            })
            ->addColumn('flow_indicator', function ($row) {
                if ($row->net_flow > 0) {
                    return '<span class="label label-success">Positive</span>';
                } elseif ($row->net_flow < 0) {
                    return '<span class="label label-danger">Negative</span>';
                } else {
                    return '<span class="label label-default">Neutral</span>';
                }
            })
            ->filterColumn('date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(t.transaction_date, '%M %d, %Y') like ?", ["%$keyword%"]);
            })
            ->filterColumn('cash_inflow', function($query, $keyword) {
                $query->havingRaw("cash_inflow like ?", ["%$keyword%"]);
            })
            ->filterColumn('cash_outflow', function($query, $keyword) {
                $query->havingRaw("cash_outflow like ?", ["%$keyword%"]);
            })
            ->filterColumn('net_flow', function($query, $keyword) {
                $query->havingRaw("net_flow like ?", ["%$keyword%"]);
            })
            ->rawColumns(['formatted_cash_inflow', 'formatted_cash_outflow', 'formatted_net_flow', 'flow_indicator'])
            ->make(true);
    }

    /**
     * Get payment method analysis data
     */
    public function getPaymentMethodAnalysis(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('accounts as acc', 'tp.account_id', '=', 'acc.id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final')
            ->select([
                'tp.method',
                DB::raw('acc.name as account_name'),
                DB::raw('COUNT(tp.id) as transaction_count'),
                DB::raw('SUM(tp.amount) as total_amount'),
                DB::raw('AVG(tp.amount) as average_amount'),
                DB::raw('SUM(CASE WHEN t.type = "sell" THEN tp.amount ELSE 0 END) as inflow_amount'),
                DB::raw('SUM(CASE WHEN t.type IN ("purchase", "expense") THEN tp.amount ELSE 0 END) as outflow_amount')
            ]);

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('tp.paid_on', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('tp.paid_on', '<=', $end_date);
        }
        
        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->groupBy('tp.method', 'acc.name')
              ->orderBy('total_amount', 'desc');


        return DataTables::of($query)
            ->addColumn('method_display', function ($row) {
                $method_name = ucfirst(str_replace('_', ' ', $row->method));
                if ($row->account_name) {
                    $method_name .= ' (' . $row->account_name . ')';
                }
                return $method_name;
            })
            ->addColumn('formatted_total_amount', function ($row) {
                return '<span class="total_amount" data-orig-value="'.$row->total_amount.'">'.$this->businessUtil->num_f($row->total_amount, true).'</span>';
            })
            ->addColumn('formatted_average_amount', function ($row) {
                return '<span class="average_amount" data-orig-value="'.$row->average_amount.'">'.$this->businessUtil->num_f($row->average_amount, true).'</span>';
            })
            ->addColumn('formatted_inflow_amount', function ($row) {
                return '<span class="inflow_amount" data-orig-value="'.$row->inflow_amount.'">'.$this->businessUtil->num_f($row->inflow_amount, true).'</span>';
            })
            ->addColumn('formatted_outflow_amount', function ($row) {
                return '<span class="outflow_amount" data-orig-value="'.$row->outflow_amount.'">'.$this->businessUtil->num_f($row->outflow_amount, true).'</span>';
            })
            ->addColumn('percentage', function ($row) {
                // This will be calculated on frontend based on total
                return '<span class="percentage">0%</span>';
            })
            ->filterColumn('method', function($query, $keyword) {
                $query->where('tp.method', 'like', "%$keyword%");
            })
            ->filterColumn('account_name', function($query, $keyword) {
                $query->where('acc.name', 'like', "%$keyword%");
            })
            ->filterColumn('total_amount', function($query, $keyword) {
                $query->havingRaw("total_amount like ?", ["%$keyword%"]);
            })
            ->filterColumn('average_amount', function($query, $keyword) {
                $query->havingRaw("average_amount like ?", ["%$keyword%"]);
            })
            ->filterColumn('inflow_amount', function($query, $keyword) {
                $query->havingRaw("inflow_amount like ?", ["%$keyword%"]);
            })
            ->filterColumn('outflow_amount', function($query, $keyword) {
                $query->havingRaw("outflow_amount like ?", ["%$keyword%"]);
            })
            ->rawColumns(['formatted_total_amount', 'formatted_average_amount', 'formatted_inflow_amount', 'formatted_outflow_amount', 'percentage'])
            ->make(true);
    }

    /**
     * Get outstanding receivables
     */
    public function getReceivables(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->location_id;

        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.id',
                't.transaction_date',
                't.invoice_no',
                'c.name as customer_name',
                'c.mobile as customer_mobile',
                'bl.name as location_name',
                't.final_total',
                DB::raw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as due_amount'),
                't.payment_status',
                DB::raw('DATEDIFF(CURDATE(), t.transaction_date) as days_overdue')
            ]);

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->having('due_amount', '>', 0)
              ->orderBy('days_overdue', 'desc');

        // Calculate totals for all receivables
        $totalsQuery = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->whereRaw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) > 0');

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $totalsQuery->where('t.location_id', $location_id);
        }

        $totals = $totalsQuery->selectRaw('
            SUM(t.final_total) as total_final_total,
            SUM(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as total_due_amount
        ')->first();

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return '<a href="#" class="btn btn-xs btn-info view-transaction" data-id="' . $row->id . '">
                            <i class="fa fa-eye"></i> View
                        </a>';
            })
            ->addColumn('formatted_final_total', function ($row) {
                return $this->businessUtil->num_f($row->final_total, true);
            })
            ->addColumn('formatted_due_amount', function ($row) {
                return $this->businessUtil->num_f($row->due_amount, true);
            })
            ->addColumn('overdue_status', function ($row) {
                if ($row->days_overdue <= 0) {
                    return '<span class="label label-success">Current</span>';
                } elseif ($row->days_overdue <= 30) {
                    return '<span class="label label-warning">1-30 days</span>';
                } elseif ($row->days_overdue <= 60) {
                    return '<span class="label label-orange">31-60 days</span>';
                } else {
                    return '<span class="label label-danger">60+ days</span>';
                }
            })
            ->with([
                'totals' => [
                    'total_final_total' => round(($totals->total_final_total ?? 0), 2),
                    'total_due_amount' => round(($totals->total_due_amount ?? 0), 2)
                ]
            ])
            ->filterColumn('transaction_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(t.transaction_date, '%Y-%m-%d') like ?", ["%$keyword%"]);
            })
            ->filterColumn('invoice_no', function($query, $keyword) {
                $query->where('t.invoice_no', 'like', "%$keyword%");
            })
            ->filterColumn('customer_name', function($query, $keyword) {
                $query->where('c.name', 'like', "%$keyword%");
            })
            ->filterColumn('location_name', function($query, $keyword) {
                $query->where('bl.name', 'like', "%$keyword%");
            })
            ->filterColumn('payment_status', function($query, $keyword) {
                $query->where('t.payment_status', 'like', "%$keyword%");
            })
            ->rawColumns(['action', 'overdue_status'])
            ->make(true);
    }

    /**
     * Get outstanding payables
     */
    public function getPayables(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->location_id;

        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.id',
                't.transaction_date',
                't.ref_no',
                'c.name as supplier_name',
                'c.mobile as supplier_mobile',
                'bl.name as location_name',
                't.final_total',
                DB::raw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as due_amount'),
                't.payment_status',
                't.type',
                DB::raw('DATEDIFF(CURDATE(), t.transaction_date) as days_overdue')
            ]);

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $query->having('due_amount', '>', 0)
              ->orderBy('days_overdue', 'desc');

        // Calculate totals for all payables
        $totalsQuery = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->whereRaw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) > 0');

        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $totalsQuery->where('t.location_id', $location_id);
        }

        $totals = $totalsQuery->selectRaw('
            SUM(t.final_total) as total_final_total,
            SUM(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as total_due_amount
        ')->first();

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return '<a href="#" class="btn btn-xs btn-info view-transaction" data-id="' . $row->id . '">
                            <i class="fa fa-eye"></i> View
                        </a>';
            })
            ->addColumn('transaction_type', function ($row) {
                return ucfirst($row->type);
            })
            ->addColumn('formatted_final_total', function ($row) {
                return '<span class="final_total" data-orig-value="'.$row->final_total.'">'.$this->businessUtil->num_f($row->final_total, true).'</span>';
            })
            ->addColumn('formatted_due_amount', function ($row) {
                return '<span class="due_amount" data-orig-value="'.$row->due_amount.'">'.$this->businessUtil->num_f($row->due_amount, true).'</span>';
            })
            ->addColumn('overdue_status', function ($row) {
                if ($row->days_overdue <= 0) {
                    return '<span class="label label-success">Current</span>';
                } elseif ($row->days_overdue <= 30) {
                    return '<span class="label label-warning">1-30 days</span>';
                } elseif ($row->days_overdue <= 60) {
                    return '<span class="label label-orange">31-60 days</span>';
                } else {
                    return '<span class="label label-danger">60+ days</span>';
                }
            })
            ->with([
                'totals' => [
                    'total_final_total' => round(($totals->total_final_total ?? 0), 2),
                    'total_due_amount' => round(($totals->total_due_amount ?? 0), 2)
                ]
            ])
            ->filterColumn('transaction_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(t.transaction_date, '%Y-%m-%d') like ?", ["%$keyword%"]);
            })
            ->filterColumn('ref_no', function($query, $keyword) {
                $query->where('t.ref_no', 'like', "%$keyword%");
            })
            ->filterColumn('supplier_name', function($query, $keyword) {
                $query->where('c.name', 'like', "%$keyword%");
            })
            ->filterColumn('type', function($query, $keyword) {
                $query->where('t.type', 'like', "%$keyword%");
            })
            ->filterColumn('payment_status', function($query, $keyword) {
                $query->where('t.payment_status', 'like', "%$keyword%");
            })
            ->rawColumns(['action', 'formatted_final_total', 'formatted_due_amount', 'overdue_status'])
            ->make(true);
    }

    /**
     * Get cash flow forecast
     */
    public function getForecast(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->location_id;
        $forecast_days = $request->get('forecast_days', 30);

        // Get current cash balance
        $current_balance = $this->getCurrentCashBalance($business_id, $location_id);
        
        // Get scheduled receivables (due invoices)
        $scheduled_receivables = $this->getScheduledReceivables($business_id, $location_id, $forecast_days);
        
        // Get scheduled payables (due bills)
        $scheduled_payables = $this->getScheduledPayables($business_id, $location_id, $forecast_days);
        
        // Calculate projected cash flow for each day
        $forecast = [];
        $running_balance = $current_balance;
        
        for ($i = 0; $i <= $forecast_days; $i++) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            
            $daily_receivables = $scheduled_receivables->where('expected_date', $date)->sum('amount');
            $daily_payables = $scheduled_payables->where('expected_date', $date)->sum('amount');
            
            $daily_net_flow = $daily_receivables - $daily_payables;
            $running_balance += $daily_net_flow;
            
            $forecast[] = [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->format('M d, Y'),
                'receivables' => $daily_receivables,
                'payables' => $daily_payables,
                'net_flow' => $daily_net_flow,
                'projected_balance' => $running_balance,
                'formatted_receivables' => $this->businessUtil->num_f($daily_receivables, true),
                'formatted_payables' => $this->businessUtil->num_f($daily_payables, true),
                'formatted_net_flow' => $this->businessUtil->num_f($daily_net_flow, true),
                'formatted_projected_balance' => $this->businessUtil->num_f($running_balance, true),
                'balance_trend' => $running_balance >= $current_balance ? 'positive' : 'negative'
            ];
        }

        return response()->json([
            'forecast' => $forecast,
            'current_balance' => $current_balance,
            'formatted_current_balance' => $this->businessUtil->num_f($current_balance, true)
        ]);
    }

    // Private helper methods

    private function getCashInflows($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        if (!empty($start_date)) {
            $query->whereDate('tp.paid_on', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('tp.paid_on', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $total = $query->sum('tp.amount');
        $count = $query->count();

        return [
            'total' => $total ?: 0,
            'count' => $count ?: 0,
            'formatted_total' => $this->businessUtil->num_f($total ?: 0, true)
        ];
    }

    private function getCashOutflows($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final');

        if (!empty($start_date)) {
            $query->whereDate('tp.paid_on', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('tp.paid_on', '<=', $end_date);
        }
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $total = $query->sum('tp.amount');
        $count = $query->count();

        return [
            'total' => $total ?: 0,
            'count' => $count ?: 0,
            'formatted_total' => $this->businessUtil->num_f($total ?: 0, true)
        ];
    }

    private function getOpeningCashBalance($business_id, $date, $location_id = null)
    {
        // This would typically come from a cash account or opening balance
        // For now, we'll calculate based on cumulative payments before the start date
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final')
            ->whereDate('tp.paid_on', '<', $date);

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $inflows = $query->clone()->where('t.type', 'sell')->sum('tp.amount');
        $outflows = $query->clone()->whereIn('t.type', ['purchase', 'expense'])->sum('tp.amount');

        return ($inflows ?: 0) - ($outflows ?: 0);
    }

    private function getOutstandingReceivables($business_id, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid');

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $total = $query->sum(DB::raw('t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)'));
        $count = $query->count();

        return [
            'total' => $total ?: 0,
            'count' => $count ?: 0,
            'formatted_total' => $this->businessUtil->num_f($total ?: 0, true)
        ];
    }

    private function getOutstandingPayables($business_id, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid');

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $total = $query->sum(DB::raw('t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)'));
        $count = $query->count();

        return [
            'total' => $total ?: 0,
            'count' => $count ?: 0,
            'formatted_total' => $this->businessUtil->num_f($total ?: 0, true)
        ];
    }

    private function getPaymentMethods($business_id)
    {
        return DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->select('tp.method')
            ->distinct()
            ->pluck('method')
            ->mapWithKeys(function ($method) {
                return [$method => ucfirst(str_replace('_', ' ', $method))];
            })
            ->toArray();
    }

    private function getPaymentMethodSummary($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final');

        // Apply date filters
        if (!empty($start_date)) {
            $query->whereDate('tp.paid_on', '>=', $start_date);
        }
        if (!empty($end_date)) {
            $query->whereDate('tp.paid_on', '<=', $end_date);
        }
        
        // Apply location filter
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $results = $query->select([
                'tp.method',
                DB::raw('COUNT(tp.id) as transaction_count'),
                DB::raw('SUM(tp.amount) as total_amount')
            ])
            ->groupBy('tp.method')
            ->get();

        return $results;
    }

    private function getCurrentCashBalance($business_id, $location_id = null)
    {
        // Calculate current cash balance from all payments
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final');

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $inflows = $query->clone()->where('t.type', 'sell')->sum('tp.amount');
        $outflows = $query->clone()->whereIn('t.type', ['purchase', 'expense'])->sum('tp.amount');

        return ($inflows ?: 0) - ($outflows ?: 0);
    }

    private function getScheduledReceivables($business_id, $location_id, $days)
    {
        // Get outstanding receivables and estimate collection dates
        $receivables = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.id',
                't.transaction_date',
                DB::raw('t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0) as amount'),
                DB::raw('DATE_ADD(t.transaction_date, INTERVAL 30 DAY) as expected_date') // Assume 30-day payment terms
            ])
            ->whereBetween(DB::raw('DATE_ADD(t.transaction_date, INTERVAL 30 DAY)'), [
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addDays($days)->format('Y-m-d')
            ]);

        if (!empty($location_id) && $location_id != 'all') {
            $receivables->where('t.location_id', $location_id);
        }

        return collect($receivables->get());
    }

    private function getScheduledPayables($business_id, $location_id, $days)
    {
        // Get outstanding payables and estimate payment dates
        $payables = DB::table('transactions as t')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.id',
                't.transaction_date',
                DB::raw('t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0) as amount'),
                DB::raw('DATE_ADD(t.transaction_date, INTERVAL 30 DAY) as expected_date') // Assume 30-day payment terms
            ])
            ->whereBetween(DB::raw('DATE_ADD(t.transaction_date, INTERVAL 30 DAY)'), [
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addDays($days)->format('Y-m-d')
            ]);

        if (!empty($location_id) && $location_id != 'all') {
            $payables->where('t.location_id', $location_id);
        }

        return collect($payables->get());
    }

    /**
     * Export cash flow report
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $format = $request->get('format', 'csv'); // csv, excel, pdf

        // Set default date range if not provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subDays(30)->format('Y-m-d');
        }

        // Get comprehensive cash flow data for export
        $export_data = $this->getExportData($business_id, $start_date, $end_date, $location_id);
        
        // Get currency symbol for proper formatting
        $currency_symbol = $request->session()->get('business.currency_symbol', '');
        
        // Handle different export formats
        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($export_data, $start_date, $end_date, $currency_symbol);
            case 'excel':
                return $this->exportToExcel($export_data, $start_date, $end_date, $currency_symbol);
            default:
                return $this->exportToCsv($export_data, $start_date, $end_date, $currency_symbol);
        }
    }

    private function exportToCsv($export_data, $start_date, $end_date, $currency_symbol = '')
    {
        $filename = 'cash-flow-report-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($export_data, $currency_symbol) {
            $file = fopen('php://output', 'w');
            
            // Add summary section
            fputcsv($file, ['CASH FLOW REPORT']);
            fputcsv($file, ['Period:', $export_data['period']]);
            fputcsv($file, ['Generated:', date('Y-m-d H:i:s')]);
            fputcsv($file, []); // Empty row
            
            // Cash flow summary
            fputcsv($file, ['CASH FLOW SUMMARY']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Opening Balance', $currency_symbol . $export_data['summary']['opening_balance']]);
            fputcsv($file, ['Cash Inflows', $currency_symbol . $export_data['summary']['cash_inflows']]);
            fputcsv($file, ['Cash Outflows', $currency_symbol . $export_data['summary']['cash_outflows']]);
            fputcsv($file, ['Net Cash Flow', $currency_symbol . $export_data['summary']['net_cash_flow']]);
            fputcsv($file, ['Closing Balance', $currency_symbol . $export_data['summary']['closing_balance']]);
            fputcsv($file, []); // Empty row
            
            // Daily cash flow
            if (!empty($export_data['daily_flows'])) {
                fputcsv($file, ['DAILY CASH FLOW']);
                fputcsv($file, ['Date', 'Cash Inflows', 'Cash Outflows', 'Net Flow', 'Running Balance']);
                foreach ($export_data['daily_flows'] as $day) {
                    fputcsv($file, [
                        $day['date'],
                        number_format($day['cash_inflow'], 2),
                        number_format($day['cash_outflow'], 2),
                        number_format($day['net_flow'], 2),
                        number_format($day['running_balance'], 2)
                    ]);
                }
                fputcsv($file, []); // Empty row
            }
            
            // Outstanding receivables
            if (!empty($export_data['receivables'])) {
                fputcsv($file, ['OUTSTANDING RECEIVABLES']);
                fputcsv($file, ['Invoice No', 'Customer', 'Date', 'Total Amount', 'Due Amount', 'Days Overdue']);
                foreach ($export_data['receivables'] as $receivable) {
                    fputcsv($file, [
                        $receivable['invoice_no'],
                        $receivable['customer_name'],
                        $receivable['transaction_date'],
                        number_format($receivable['final_total'], 2),
                        number_format($receivable['due_amount'], 2),
                        $receivable['days_overdue']
                    ]);
                }
                fputcsv($file, []); // Empty row
            }
            
            // Outstanding payables
            if (!empty($export_data['payables'])) {
                fputcsv($file, ['OUTSTANDING PAYABLES']);
                fputcsv($file, ['Ref No', 'Supplier', 'Date', 'Type', 'Total Amount', 'Due Amount', 'Days Overdue']);
                foreach ($export_data['payables'] as $payable) {
                    fputcsv($file, [
                        $payable['ref_no'],
                        $payable['supplier_name'],
                        $payable['transaction_date'],
                        ucfirst($payable['type']),
                        number_format($payable['final_total'], 2),
                        number_format($payable['due_amount'], 2),
                        $payable['days_overdue']
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($export_data, $start_date, $end_date, $currency_symbol = '')
    {
        // For Excel, we'll use the same CSV format but with .xlsx extension
        $filename = 'cash-flow-report-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($export_data, $currency_symbol) {
            $file = fopen('php://output', 'w');
            
            // Add summary section with enhanced formatting for Excel
            fputcsv($file, ['CASH FLOW REPORT']);
            fputcsv($file, ['Period:', $export_data['period']]);
            fputcsv($file, ['Location:', $export_data['location'] ?? 'All Locations']);
            fputcsv($file, ['Generated:', date('Y-m-d H:i:s')]);
            fputcsv($file, []); // Empty row
            
            // Executive Summary
            fputcsv($file, ['EXECUTIVE SUMMARY']);
            fputcsv($file, ['Opening Balance', $currency_symbol . number_format($export_data['summary']['opening_balance'], 2)]);
            fputcsv($file, ['Total Inflows', $currency_symbol . number_format($export_data['summary']['cash_inflows'], 2)]);
            fputcsv($file, ['Total Outflows', $currency_symbol . number_format($export_data['summary']['cash_outflows'], 2)]);
            fputcsv($file, ['Net Cash Flow', $currency_symbol . number_format($export_data['summary']['net_cash_flow'], 2)]);
            fputcsv($file, ['Closing Balance', $currency_symbol . number_format($export_data['summary']['closing_balance'], 2)]);
            fputcsv($file, ['Outstanding Receivables', $currency_symbol . number_format($export_data['summary']['total_receivables'], 2)]);
            fputcsv($file, ['Outstanding Payables', $currency_symbol . number_format($export_data['summary']['total_payables'], 2)]);
            fputcsv($file, []); // Empty row
            
            // Payment method breakdown
            if (!empty($export_data['payment_methods'])) {
                fputcsv($file, ['PAYMENT METHOD BREAKDOWN']);
                fputcsv($file, ['Payment Method', 'Transaction Count', 'Total Amount', 'Inflows', 'Outflows']);
                foreach ($export_data['payment_methods'] as $method) {
                    fputcsv($file, [
                        ucfirst(str_replace('_', ' ', $method['method'])),
                        $method['transaction_count'],
                        number_format($method['total_amount'], 2),
                        number_format($method['inflow_amount'], 2),
                        number_format($method['outflow_amount'], 2)
                    ]);
                }
                fputcsv($file, []); // Empty row
            }
            
            // Daily breakdown (last 30 days or selected period)
            if (!empty($export_data['daily_flows'])) {
                fputcsv($file, ['DAILY CASH FLOW BREAKDOWN']);
                fputcsv($file, ['Date', 'Day', 'Inflows', 'Outflows', 'Net Flow', 'Balance Trend']);
                foreach ($export_data['daily_flows'] as $day) {
                    $trend = $day['net_flow'] > 0 ? 'Positive' : ($day['net_flow'] < 0 ? 'Negative' : 'Neutral');
                    fputcsv($file, [
                        $day['date'],
                        Carbon::parse($day['date'])->format('l'),
                        number_format($day['cash_inflow'], 2),
                        number_format($day['cash_outflow'], 2),
                        number_format($day['net_flow'], 2),
                        $trend
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($export_data, $start_date, $end_date, $currency_symbol = '')
    {
        // Generate HTML that can be converted to PDF
        $html = view('advancedreports::cash-flow.partials.pdf_export', compact('export_data'))->render();
        
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="cash-flow-report-' . date('Y-m-d') . '.html"',
        ];
        
        return response($html, 200, $headers);
    }

    private function getExportData($business_id, $start_date, $end_date, $location_id)
    {
        // Get cash flow summary
        $cash_inflows = $this->getCashInflows($business_id, $start_date, $end_date, $location_id);
        $cash_outflows = $this->getCashOutflows($business_id, $start_date, $end_date, $location_id);
        $net_cash_flow = $cash_inflows['total'] - $cash_outflows['total'];
        $opening_balance = $this->getOpeningCashBalance($business_id, $start_date, $location_id);
        $closing_balance = $opening_balance + $net_cash_flow;
        
        // Get receivables and payables
        $receivables = $this->getOutstandingReceivables($business_id, $location_id);
        $payables = $this->getOutstandingPayables($business_id, $location_id);
        
        // Get payment methods summary
        $payment_methods = $this->getPaymentMethodSummary($business_id, $start_date, $end_date, $location_id);
        
        // Get daily cash flow data
        $daily_flows = $this->getDailyFlowsForExport($business_id, $start_date, $end_date, $location_id);
        
        // Get detailed receivables and payables for export
        $receivables_detail = $this->getReceivablesDetail($business_id, $location_id);
        $payables_detail = $this->getPayablesDetail($business_id, $location_id);
        
        return [
            'period' => "$start_date to $end_date",
            'location' => $this->getLocationName($location_id),
            'summary' => [
                'opening_balance' => number_format($opening_balance, 2),
                'cash_inflows' => number_format($cash_inflows['total'], 2),
                'cash_outflows' => number_format($cash_outflows['total'], 2),
                'net_cash_flow' => number_format($net_cash_flow, 2),
                'closing_balance' => number_format($closing_balance, 2),
                'total_receivables' => number_format($receivables['total'], 2),
                'total_payables' => number_format($payables['total'], 2)
            ],
            'payment_methods' => $payment_methods->toArray(),
            'daily_flows' => $daily_flows,
            'receivables' => $receivables_detail,
            'payables' => $payables_detail
        ];
    }

    private function getDailyFlowsForExport($business_id, $start_date, $end_date, $location_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.status', 'final')
            ->whereIn('t.type', ['sell', 'purchase', 'expense'])
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        $flows = $query->select([
                DB::raw('DATE(t.transaction_date) as date'),
                DB::raw('SUM(CASE WHEN t.type = "sell" THEN COALESCE(tp.amount, 0) ELSE 0 END) as cash_inflow'),
                DB::raw('SUM(CASE WHEN t.type IN ("purchase", "expense") THEN COALESCE(tp.amount, 0) ELSE 0 END) as cash_outflow')
            ])
            ->groupBy(DB::raw('DATE(t.transaction_date)'))
            ->orderBy('date', 'asc')
            ->get();

        $running_balance = $this->getOpeningCashBalance($business_id, $start_date, $location_id);
        $daily_flows = [];

        foreach ($flows as $flow) {
            $net_flow = $flow->cash_inflow - $flow->cash_outflow;
            $running_balance += $net_flow;
            
            $daily_flows[] = [
                'date' => $flow->date,
                'cash_inflow' => $flow->cash_inflow,
                'cash_outflow' => $flow->cash_outflow,
                'net_flow' => $net_flow,
                'running_balance' => $running_balance
            ];
        }

        return $daily_flows;
    }

    private function getReceivablesDetail($business_id, $location_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.invoice_no',
                'c.name as customer_name',
                't.transaction_date',
                't.final_total',
                DB::raw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as due_amount'),
                DB::raw('DATEDIFF(CURDATE(), t.transaction_date) as days_overdue')
            ]);

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        return $query->having('due_amount', '>', 0)
              ->orderBy('days_overdue', 'desc')
              ->get()
              ->toArray();
    }

    private function getPayablesDetail($business_id, $location_id)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->whereIn('t.type', ['purchase', 'expense'])
            ->where('t.status', 'final')
            ->where('t.payment_status', '!=', 'paid')
            ->select([
                't.ref_no',
                'c.name as supplier_name',
                't.transaction_date',
                't.type',
                't.final_total',
                DB::raw('(t.final_total - COALESCE((SELECT SUM(tp.amount) FROM transaction_payments tp WHERE tp.transaction_id = t.id), 0)) as due_amount'),
                DB::raw('DATEDIFF(CURDATE(), t.transaction_date) as days_overdue')
            ]);

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('t.location_id', $location_id);
        }

        return $query->having('due_amount', '>', 0)
              ->orderBy('days_overdue', 'desc')
              ->get()
              ->toArray();
    }

    private function getLocationName($location_id)
    {
        if (empty($location_id) || $location_id == 'all') {
            return 'All Locations';
        }

        $location = DB::table('business_locations')->where('id', $location_id)->first();
        return $location ? $location->name : 'Unknown Location';
    }
}