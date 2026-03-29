<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Contact;
use Carbon\Carbon;
use App\Transaction;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\TransactionPayment;
use App\TransactionSellLine;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesReportController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display sales report index
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get business locations
        try {
            $business_locations = BusinessLocation::forDropdown($business_id, false, false);

            if (is_array($business_locations)) {
                $business_locations = collect($business_locations);
            }

            $business_locations = $business_locations->prepend(__('advancedreports::lang.all_locations'), '');
        } catch (\Exception $e) {
            \Log::error('AdvancedReports: Error getting business locations: ' . $e->getMessage());
            $business_locations = collect(['' => __('advancedreports::lang.all_locations')]);
        }

        // Get customers
        try {
            $customers = Contact::customersDropdown($business_id, false);

            if (is_array($customers)) {
                $customers = collect($customers);
            }

            $customers = $customers->prepend(__('advancedreports::lang.all_customers'), '');
        } catch (\Exception $e) {
            \Log::error('AdvancedReports: Error getting customers: ' . $e->getMessage());
            $customers = collect(['' => __('advancedreports::lang.all_customers')]);
        }

        // Get payment types
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $payment_types = collect($payment_types)->prepend(__('lang_v1.all'), '');

        return view('advancedreports::sales.index')
            ->with(compact('business_locations', 'customers', 'payment_types'));
    }

    /**
     * Get sales data for DataTables
     */
    public function getSalesData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $location_id = $request->get('location_id');
        $customer_id = $request->get('customer_id');
        $payment_status = $request->get('payment_status');
        $payment_method = $request->get('payment_method');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        try {
            $query = Transaction::leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->with(['payment_lines']);

            // Apply filters
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            if (!empty($customer_id)) {
                $query->where('transactions.contact_id', $customer_id);
            }

            if (!empty($payment_status)) {
                $query->where('transactions.payment_status', $payment_status);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
            }

            // Permission check
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            // Payment method filter
            if (!empty($payment_method)) {
                $query->whereHas('payment_lines', function ($q) use ($payment_method) {
                    $q->where('method', $payment_method);
                });
            }

            $query->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                'transactions.final_total',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.discount_type',
                'transactions.total_before_tax',
                'transactions.payment_status',
                'transactions.created_by',
                'c.name as customer_name',
                'c.supplier_business_name',
                'c.mobile as customer_mobile',
                'c.contact_id as customer_contact_id',
                'bl.name as location_name',
                // FIXED: Use same concatenation as your existing query
                DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as added_by"),
                // Calculate amounts
                DB::raw('transactions.final_total - transactions.discount_amount as net_total'),
                DB::raw('(SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id) as total_paid'),
                DB::raw('transactions.final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id) as due_amount')
            ]);

            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info btn-xs sales_modal_btn" 
                            data-href="' . action([\Modules\AdvancedReports\Http\Controllers\SalesReportController::class, 'show'], [$row->id]) . '"
                            data-container=".sales_modal">
                            <i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '
                        </button>
                    </div>';

                    return $html;
                })
                ->editColumn('transaction_date', function ($row) {
                    return $this->transactionUtil->format_date($row->transaction_date, true);
                })
                ->editColumn('invoice_no', function ($row) {
                    return '<a data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]) . '" 
                    href="#" 
                    class="btn-link sales-invoice-modal" 
                    data-container=".view_modal">' . $row->invoice_no . '</a>';
                })


                ->editColumn('customer_name', function ($row) {
                    $name = $row->customer_name ?: __('advancedreports::lang.walk_in_customer');
                    if (!empty($row->supplier_business_name)) {
                        $name = $row->supplier_business_name . '<br><small>' . $name . '</small>';
                    }
                    if (!empty($row->customer_mobile)) {
                        $name .= '<br><small>' . $row->customer_mobile . '</small>';
                    }
                    return $name;
                })

                // NEW: Invoice Subtotal Column (before any discounts)
                ->addColumn('invoice_subtotal', function ($row) {
                    $subtotal_result = DB::table('transaction_sell_lines')
                        ->where('transaction_id', $row->id)
                        ->select([
                            DB::raw('SUM(unit_price_before_discount * quantity) as invoice_subtotal')
                        ])
                        ->first();

                    $invoice_subtotal = $subtotal_result->invoice_subtotal ?? 0;

                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($invoice_subtotal, 2) . '</span>';
                })
                ->editColumn('tax_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row->tax_amount, 2) . '</span>';
                })
                // NEW: Line Discount Column
                ->addColumn('line_discount', function ($row) {
                    $line_discount_result = DB::table('transaction_sell_lines')
                        ->where('transaction_id', $row->id)
                        ->select([
                            DB::raw('SUM(CASE 
                    WHEN line_discount_type = "percentage" THEN 
                        (unit_price_before_discount * quantity * line_discount_amount / 100)
                    ELSE (line_discount_amount * quantity)
                END) as total_line_discount')
                        ])
                        ->first();

                    $line_discount = $line_discount_result->total_line_discount ?? 0;

                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($line_discount, 2) . '</span>';
                })

                // NEW: Invoice Discount Column
                ->addColumn('invoice_discount', function ($row) {
                    $invoice_discount = 0;
                    if ($row->discount_amount > 0) {
                        if ($row->discount_type == 'percentage') {
                            $invoice_discount = ($row->total_before_tax * $row->discount_amount) / 100;
                        } else {
                            $invoice_discount = $row->discount_amount;
                        }
                    }

                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($invoice_discount, 2) . '</span>';
                })

                // UPDATED: Total Discount Column (line + invoice)
                ->editColumn('discount_amount', function ($row) {
                    // Calculate line discount
                    $line_discount_result = DB::table('transaction_sell_lines')
                        ->where('transaction_id', $row->id)
                        ->select([
                            DB::raw('SUM(CASE 
                    WHEN line_discount_type = "percentage" THEN 
                        (unit_price_before_discount * quantity * line_discount_amount / 100)
                    ELSE (line_discount_amount * quantity)
                END) as total_line_discount')
                        ])
                        ->first();

                    $line_discount = $line_discount_result->total_line_discount ?? 0;

                    // Calculate invoice discount
                    $invoice_discount = 0;
                    if ($row->discount_amount > 0) {
                        if ($row->discount_type == 'percentage') {
                            $invoice_discount = ($row->total_before_tax * $row->discount_amount) / 100;
                        } else {
                            $invoice_discount = $row->discount_amount;
                        }
                    }

                    $total_discount = $line_discount + $invoice_discount;

                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($total_discount, 2) . '</span>';
                })
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row->final_total, 2) . '</span>';
                })
                ->editColumn('total_paid', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row->total_paid, 2) . '</span>';
                })
                ->editColumn('due_amount', function ($row) {
                    $class = $row->due_amount > 0 ? 'text-danger' : 'text-success';
                    return '<span class="display_currency ' . $class . '" data-currency_symbol="true">' .
                        number_format($row->due_amount, 2) . '</span>';
                })
                ->addColumn('payment_method', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';

                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]] ?? $methods[0];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    return $payment_method;
                })
                ->editColumn('payment_status', function ($row) {
                    $status = __('lang_v1.' . $row->payment_status);
                    $class = '';

                    switch ($row->payment_status) {
                        case 'paid':
                            $class = 'label-success';
                            break;
                        case 'due':
                            $class = 'label-danger';
                            break;
                        case 'partial':
                            $class = 'label-warning';
                            break;
                        default:
                            $class = 'label-default';
                    }

                    return '<span class="label ' . $class . '">' . $status . '</span>';
                })
                ->editColumn('created_by', function ($row) {
                    return trim($row->added_by) ?: '-';
                })
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('c.name', 'like', "%{$keyword}%")
                            ->orWhere('c.supplier_business_name', 'like', "%{$keyword}%")
                            ->orWhere('c.mobile', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns([
                    'action',
                    'invoice_no',
                    'customer_name',
                    'invoice_subtotal',    // NEW
                    'tax_amount',
                    'line_discount',
                    'invoice_discount',
                    'discount_amount',
                    'final_total',         // MOVED
                    'total_paid',
                    'due_amount',
                    'payment_status'
                ])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Sales Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading sales data'], 500);
        }
    }

    /**
     * Show detailed sales information
     */
    /**
     * Show detailed sales information - UPDATED to match DataTable user query
     */
    public function show($id)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            // Get transaction with user data using same approach as DataTable
            $transaction = DB::table('transactions as t')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $business_id)
                ->where('t.id', $id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select([
                    't.*',
                    'c.name as customer_name',
                    'c.supplier_business_name',
                    'c.mobile as customer_mobile',
                    'c.email as customer_email',
                    'c.contact_id as customer_contact_id',
                    'bl.name as location_name',
                    DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as added_by")
                ])
                ->first();

            if (!$transaction) {
                return response('<div class="alert alert-danger">Transaction not found or not accessible</div>', 404);
            }

            // Convert to object for easier handling in view
            $transaction = (object) $transaction;

            // Get sell lines separately
            $transaction->sell_lines = DB::table('transaction_sell_lines as tsl')
                ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('tsl.transaction_id', $id)
                ->select([
                    'tsl.*',
                    'p.name as product_name',
                    'v.name as variation_name'
                ])
                ->get();

            // Get payment lines
            $transaction->payment_lines = DB::table('transaction_payments')
                ->where('transaction_id', $id)
                ->get();

            return view('advancedreports::sales.show')->with(compact('transaction'));
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Sales Show Error: ' . $e->getMessage(), [
                'transaction_id' => $id,
                'business_id' => $business_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response('<div class="alert alert-danger">Error loading transaction details. Please check the logs for more information.</div>', 500);
        }
    }

    /**
     * Get sales summary data with discount breakdown
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $location_id = $request->get('location_id');
            $customer_id = $request->get('customer_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final');

            // Apply filters
            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            if (!empty($customer_id)) {
                $query->where('contact_id', $customer_id);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('DATE(transaction_date)'), [$start_date, $end_date]);
            }

            // Permission check
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $summary = $query->select([
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(final_total) as total_sales'),
                DB::raw('SUM(tax_amount) as total_tax'),
                // Calculate INVOICE-level discounts only
                DB::raw('SUM(CASE 
    WHEN discount_type = "percentage" THEN (total_before_tax * discount_amount / 100)
    ELSE discount_amount 
END) as invoice_discount'),

                DB::raw('COUNT(CASE WHEN payment_status = "paid" THEN 1 END) as paid_transactions'),
                DB::raw('COUNT(CASE WHEN payment_status = "due" THEN 1 END) as due_transactions'),
                DB::raw('COUNT(CASE WHEN payment_status = "partial" THEN 1 END) as partial_transactions'),
                DB::raw('SUM(CASE WHEN payment_status = "paid" THEN final_total ELSE 0 END) as paid_amount'),
                DB::raw('SUM(CASE WHEN payment_status = "due" THEN final_total ELSE 0 END) as due_amount'),
                DB::raw('SUM(CASE WHEN payment_status = "partial" THEN final_total ELSE 0 END) as partial_amount'),
                // Calculate actual pending amounts (final_total - paid_amount)
                DB::raw('SUM(CASE WHEN payment_status IN ("due", "partial") THEN 
                (final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)) 
                ELSE 0 END) as total_due_amount'),
                // Additional metrics for total transactions widget
                DB::raw('COUNT(DISTINCT COALESCE(contact_id, 0)) as total_customers'),
                DB::raw('COUNT(DISTINCT (SELECT product_id FROM transaction_sell_lines WHERE transaction_id = transactions.id LIMIT 1)) as total_products_simple')
            ])->first();

            // Calculate LINE-level discounts separately
            $line_discount_query = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            // Apply same filters as main query
            if (!empty($location_id)) {
                $line_discount_query->where('t.location_id', $location_id);
            }

            if (!empty($customer_id)) {
                $line_discount_query->where('t.contact_id', $customer_id);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $line_discount_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            }

            // Permission check
            if ($permitted_locations != 'all') {
                $line_discount_query->whereIn('t.location_id', $permitted_locations);
            }

            $line_discount_result = $line_discount_query->select([
                DB::raw('SUM(CASE 
                WHEN tsl.line_discount_type = "percentage" THEN 
                    (tsl.unit_price_before_discount * tsl.quantity * tsl.line_discount_amount / 100)
                ELSE (tsl.line_discount_amount * tsl.quantity)
            END) as line_discount')
            ])->first();

            $summary->line_discount = $line_discount_result->line_discount ?? 0;
            $summary->total_discount = $summary->invoice_discount + $summary->line_discount;

            // Get total unique products sold (more accurate count)
            $total_products = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            // Apply same filters as main query
            if (!empty($location_id)) {
                $total_products->where('t.location_id', $location_id);
            }

            if (!empty($customer_id)) {
                $total_products->where('t.contact_id', $customer_id);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $total_products->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            }

            // Permission check
            if ($permitted_locations != 'all') {
                $total_products->whereIn('t.location_id', $permitted_locations);
            }

            $summary->total_products = $total_products->distinct('tsl.product_id')->count('tsl.product_id');

            // Get due collections analysis
            $user_id = auth()->user()->id;
            $due_analysis = $this->getTodayDueCollections($user_id, null, null);

            // Merge due analysis with summary
            $summary->due_collected_today = $due_analysis['due_collected_today'];
            $summary->pending_due_today = $due_analysis['pending_due_today'];
            $summary->overdue_amount = $due_analysis['overdue_amount'];

            return response()->json($summary);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Sales Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    /**
     * Get today's due collections analysis
     */
    public function getTodayDueCollections($user_id, $open_time, $close_time)
    {
        $business_id = request()->session()->get('user.business_id');

        // COLLECTED TODAY - Payments made today that reduce existing due amounts
        // This includes: payments on old invoices + additional payments on today's invoices
        $today_due = DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->whereDate('tp.paid_on', now()->toDateString()) // Payments made today
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.business_id', $business_id)
            // Exclude initial payments made at the time of sale creation
            ->where(function ($query) {
                $query->whereRaw('DATE(tp.paid_on) != DATE(t.created_at)')  // Payment made on different day than sale
                    ->orWhereRaw('tp.created_at > DATE_ADD(t.created_at, INTERVAL 5 MINUTE)'); // Or payment made more than 5 minutes after sale creation
            })
            ->select(
                DB::raw('SUM(tp.amount) as total_due_collected'),
                DB::raw('COUNT(DISTINCT tp.transaction_id) as total_due_transactions')
            )
            ->first();

        // PENDING DUES TODAY - Transactions created today that still have pending amounts
        $pending_dues = DB::table('transactions as t')
            ->whereDate('t.transaction_date', now()->toDateString()) // Transactions created today
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.business_id', $business_id)
            ->where('t.payment_status', '!=', 'paid') // Not fully paid
            ->select(
                DB::raw('SUM(t.final_total - COALESCE(
                    (SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = t.id), 0
                )) as total_pending_due'),
                DB::raw('COUNT(t.id) as total_pending_transactions')
            )
            ->first();

        // OVERDUE AMOUNTS - Previous days' unpaid transactions
        $overdue_amounts = DB::table('transactions as t')
            ->whereDate('t.transaction_date', '<', now()->toDateString()) // Previous days
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.business_id', $business_id)
            ->where('t.payment_status', '!=', 'paid') // Not fully paid
            ->select(
                DB::raw('SUM(t.final_total - COALESCE(
                    (SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = t.id), 0
                )) as total_overdue'),
                DB::raw('COUNT(t.id) as total_overdue_transactions')
            )
            ->first();

        return [
            'due_collected_today' => $today_due->total_due_collected ?? 0,
            'due_transactions_count' => $today_due->total_due_transactions ?? 0,
            'pending_due_today' => $pending_dues->total_pending_due ?? 0,
            'pending_transactions_count' => $pending_dues->total_pending_transactions ?? 0,
            'overdue_amount' => $overdue_amounts->total_overdue ?? 0,
            'overdue_transactions_count' => $overdue_amounts->total_overdue_transactions ?? 0,
        ];
    }

    /**
     * Get today's due cash collections
     */
    public function getTodayDueCashCollections($register_id, $open_time, $close_time)
    {
        $business_id = request()->session()->get('user.business_id');

        // Get cash payments made today for due collections
        $due_cash_collections = DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->whereDate('tp.paid_on', now()->toDateString()) // Payments made today
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('tp.method', 'cash') // Only cash payments
            // Exclude initial payments made at time of sale creation
            ->where(function ($query) {
                $query->whereRaw('DATE(tp.paid_on) != DATE(t.created_at)')  // Different day
                    ->orWhereRaw('tp.created_at > DATE_ADD(t.created_at, INTERVAL 5 MINUTE)'); // Or 5+ minutes later
            })
            ->sum('tp.amount');

        return $due_cash_collections ?? 0;
    }

    /**
     * Export sales report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1G');

        try {
            $business_id = request()->session()->get('user.business_id');

            $filters = [
                'location_id' => $request->get('location_id'),
                'customer_id' => $request->get('customer_id'),
                'payment_status' => $request->get('payment_status'),
                'payment_method' => $request->get('payment_method'),
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'business_id' => $business_id
            ];

            $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.csv';

            return $this->exportCSV($filters, $filename);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Sales Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    /**
     * Export CSV
     */
    private function exportCSV($filters, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($filters) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV headers
            fputcsv($file, [
                'Date',
                'Invoice No',
                'Customer',
                'Customer Mobile',
                'Location',
                'Total Amount',
                'Tax Amount',
                'Discount Amount',
                'Net Amount',
                'Payment Status',
                'Payment Method',
                'Created By'
            ]);

            $business_id = $filters['business_id'];
            $location_id = $filters['location_id'];
            $customer_id = $filters['customer_id'];
            $payment_status = $filters['payment_status'];
            $start_date = $filters['start_date'];
            $end_date = $filters['end_date'];

            // Base query
            $query = Transaction::leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->with(['payment_lines']);

            // Apply filters
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            if (!empty($customer_id)) {
                $query->where('transactions.contact_id', $customer_id);
            }

            if (!empty($payment_status)) {
                $query->where('transactions.payment_status', $payment_status);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
            }

            $query->select([
                'transactions.transaction_date',
                'transactions.invoice_no',
                'transactions.final_total',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.discount_type',
                'transactions.payment_status',
                'c.name as customer_name',
                'c.supplier_business_name',
                'c.mobile as customer_mobile',
                'bl.name as location_name',
                'u.first_name as created_by_first_name',
                'u.last_name as created_by_last_name'
            ]);

            // Process in chunks
            $query->chunk(100, function ($transactions) use ($file) {
                foreach ($transactions as $transaction) {
                    $discount = $transaction->discount_amount;
                    if ($transaction->discount_type == 'percentage') {
                        $discount = ($transaction->final_total * $transaction->discount_amount) / 100;
                    }

                    $customer = $transaction->customer_name ?: 'Walk-in Customer';
                    if (!empty($transaction->supplier_business_name)) {
                        $customer = $transaction->supplier_business_name . ' - ' . $customer;
                    }

                    $payment_methods = $transaction->payment_lines->pluck('method')->unique()->toArray();
                    $payment_method = count($payment_methods) == 1 ? $payment_methods[0] : 'Multiple';

                    fputcsv($file, [
                        date('Y-m-d H:i:s', strtotime($transaction->transaction_date)),
                        $transaction->invoice_no,
                        $customer,
                        $transaction->customer_mobile ?: '-',
                        $transaction->location_name ?: '-',
                        number_format($transaction->final_total, 2),
                        number_format($transaction->tax_amount, 2),
                        number_format($discount, 2),
                        number_format($transaction->final_total - $discount, 2),
                        ucfirst($transaction->payment_status),
                        $payment_method,
                        $transaction->created_by_first_name . ' ' . $transaction->created_by_last_name
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
