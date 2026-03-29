<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\AdvancedReports\Exports\DailySummaryReportExport;
use Maatwebsite\Excel\Facades\Excel;

class DailySummaryReportController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;

    /**
     * Constructor
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // Handle incoming date parameters if any
        $start_date = null;
        $end_date = null;

        $date_range = $request->input('date_range');
        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $start_date = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $end_date = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        }

        return view('advancedreports::daily.summary.index')
            ->with(compact('business_locations', 'start_date', 'end_date'));
    }

    /**
     * Daily Summary Report (Compatible with existing route structure)
     */
    public function dailySummaryReport(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->only(['location_id']);
        $permitted_locations = auth()->user()->permitted_locations();
        $date_range = $request->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $start_date = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $end_date = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        } else {
            $start_date = \Carbon\Carbon::today();
            $end_date = \Carbon\Carbon::today()->endOfDay();
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('advancedreports::daily.summary.index')->with(
            compact(
                'start_date',
                'end_date',
                'business_locations',
            )
        );
    }

    /**
     * Get daily summary data for DataTables
     */
    public function getDailySummaryData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            // Build date range with improved date processing
            if (empty($start_date) || empty($end_date)) {
                $start_date = \Carbon\Carbon::now()->subDays(30)->format('Y-m-d');
                $end_date = \Carbon\Carbon::now()->format('Y-m-d');
            } else {
                // Handle various date formats
                try {
                    if (!(\Carbon\Carbon::hasFormat($start_date, 'Y-m-d'))) {
                        $start_date = \Carbon\Carbon::parse($start_date)->format('Y-m-d');
                    }
                    if (!(\Carbon\Carbon::hasFormat($end_date, 'Y-m-d'))) {
                        $end_date = \Carbon\Carbon::parse($end_date)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    \Log::warning('Date parsing error: ' . $e->getMessage());
                    $start_date = \Carbon\Carbon::now()->subDays(30)->format('Y-m-d');
                    $end_date = \Carbon\Carbon::now()->format('Y-m-d');
                }
            }

            // Get only dates that have transactions (sales, purchases, or expenses)
            $dates = $this->getDatesWithTransactions($business_id, $start_date, $end_date, $location_id, $permitted_locations);

            $daily_data = collect();

            foreach ($dates as $date) {
        // Get sales for this date - UPDATED CALL
$sales = $this->getDailySalesData($business_id, $date, $location_id, $permitted_locations);
$purchases = $this->getDailyPurchasesData($business_id, $date, $location_id, $permitted_locations);
$expenses = $this->getDailyExpensesData($business_id, $date, $location_id, $permitted_locations);
        // Get payments for this date
        $payments = $this->getDailyPayments($business_id, $date, $location_id, $permitted_locations);

        $daily_data->push([
            'date' => $date,
            'formatted_date' => \Carbon\Carbon::parse($date)->format('d-m-Y'),
            'day_name' => \Carbon\Carbon::parse($date)->format('l'),
            'total_sales' => $sales['total'] ?? 0,
            'sales_count' => $sales['count'] ?? 0,
            'total_purchases' => $purchases['total'] ?? 0,
            'purchases_count' => $purchases['count'] ?? 0,
            'total_expenses' => $expenses['total'] ?? 0,
            'expenses_count' => $expenses['count'] ?? 0,
            'cash_received' => $payments['cash'] ?? 0,
            'card_received' => $payments['card'] ?? 0,
            'bank_transfer' => $payments['bank_transfer'] ?? 0,
            'total_payments' => $payments['total'] ?? 0,
            'net_profit' => ($sales['total'] ?? 0) - ($purchases['total'] ?? 0) - ($expenses['total'] ?? 0)
        ]);
    }

            return Datatables::of($daily_data)
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-primary btn-xs view-details" data-date="' . $row['date'] . '">
                        <i class="fa fa-eye"></i> View Details
                    </button>';
                })
                ->editColumn('total_sales', function ($row) {
                    return '<span class="display_currency" data-orig-value="' . $row['total_sales'] . '">' .
                        number_format($row['total_sales'], 2) . '</span>';
                })
                ->editColumn('total_purchases', function ($row) {
                    return '<span class="display_currency" data-orig-value="' . $row['total_purchases'] . '">' .
                        number_format($row['total_purchases'], 2) . '</span>';
                })
                ->editColumn('total_expenses', function ($row) {
                    return '<span class="display_currency" data-orig-value="' . $row['total_expenses'] . '">' .
                        number_format($row['total_expenses'], 2) . '</span>';
                })
                ->editColumn('cash_received', function ($row) {
                    return '<span class="display_currency" data-orig-value="' . $row['cash_received'] . '">' .
                        number_format($row['cash_received'], 2) . '</span>';
                })
                ->editColumn('card_received', function ($row) {
                    return '<span class="display_currency" data-orig-value="' . $row['card_received'] . '">' .
                        number_format($row['card_received'], 2) . '</span>';
                })
                ->editColumn('net_profit', function ($row) {
                    $class = $row['net_profit'] >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="display_currency ' . $class . '" data-orig-value="' . $row['net_profit'] . '">' .
                        number_format($row['net_profit'], 2) . '</span>';
                })
                ->rawColumns(['action', 'total_sales', 'total_purchases', 'total_expenses', 'cash_received', 'card_received', 'net_profit'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Summary Data Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get dates that have transactions (sales, purchases, or expenses)
     */
    private function getDatesWithTransactions($business_id, $start_date, $end_date, $location_id = null, $permitted_locations = null)
    {
        $sales_dates = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date);

        $purchase_dates = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date);

        $expense_dates = Transaction::where('business_id', $business_id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date);

        // Apply location filters
        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $sales_dates->whereIn('location_id', $permitted_locations);
            $purchase_dates->whereIn('location_id', $permitted_locations);
            $expense_dates->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $sales_dates->where('location_id', $location_id);
            $purchase_dates->where('location_id', $location_id);
            $expense_dates->where('location_id', $location_id);
        }

        // Get unique dates from all transaction types
        $sales_dates = $sales_dates->selectRaw('DATE(transaction_date) as date')->distinct()->pluck('date');
        $purchase_dates = $purchase_dates->selectRaw('DATE(transaction_date) as date')->distinct()->pluck('date');
        $expense_dates = $expense_dates->selectRaw('DATE(transaction_date) as date')->distinct()->pluck('date');

        // Combine all dates and remove duplicates
        $all_dates = $sales_dates->merge($purchase_dates)->merge($expense_dates)->unique()->sort()->values();

        return $all_dates;
    }



    /**
     * Get daily purchases
     */
private function getDailySalesData($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::where('business_id', $business_id)
        ->where('type', 'sell')
        ->where('status', 'final')
        ->whereDate('transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('location_id', $location_id);
    }

    return [
        'total' => $query->sum('final_total'),
        'count' => $query->count()
    ];
}

/**
 * Get daily purchases data (renamed from getDailyPurchases)
 */
private function getDailyPurchasesData($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::where('business_id', $business_id)
        ->where('type', 'purchase')
        ->where('status', 'received')
        ->whereDate('transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('location_id', $location_id);
    }

    return [
        'total' => $query->sum('final_total'),
        'count' => $query->count()
    ];
}

/**
 * Get daily expenses data (renamed from getDailyExpenses)
 */
private function getDailyExpensesData($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::where('business_id', $business_id)
        ->where('type', 'expense')
        ->whereDate('transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('location_id', $location_id);
    }

    return [
        'total' => $query->sum('final_total'),
        'count' => $query->count()
    ];
}

// 2. Add this NEW PUBLIC method for the DataTable
public function getDailySales(Request $request)
{
    if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $permitted_locations = auth()->user()->permitted_locations();

        $query = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)
                ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        return Datatables::of($query)
            ->addColumn('contact_id', function ($row) {
                return $row->contact_id;
            })
            ->addColumn('customer_name', function ($row) {
                return $row->name ?: 'Walk-in Customer';
            })
            ->editColumn('transaction_date', function ($row) {
                return \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y');
            })
            ->editColumn('total_before_tax', function ($row) {
                return '<span class="display_currency total_before_tax" data-orig-value="' . $row->total_before_tax . '">' .
                    number_format($row->total_before_tax, 2) . '</span>';
            })
            ->editColumn('discount_amount', function ($row) {
                return '<span class="display_currency discount_amount" data-orig-value="' . $row->discount_amount . '">' .
                    number_format($row->discount_amount, 2) . '</span>';
            })
            ->editColumn('tax_amount', function ($row) {
                return '<span class="display_currency tax_amount" data-orig-value="' . $row->tax_amount . '">' .
                    number_format($row->tax_amount, 2) . '</span>';
            })
            ->editColumn('final_total', function ($row) {
                return '<span class="display_currency final_total" data-orig-value="' . $row->final_total . '">' .
                    number_format($row->final_total, 2) . '</span>';
            })
            ->addColumn('total_paid', function ($row) {
                $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                return '<span class="display_currency total_paid" data-orig-value="' . $paid . '">' .
                    number_format($paid, 2) . '</span>';
            })
            ->addColumn('total_due', function ($row) {
                $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                $due = $row->final_total - $paid;
                return '<span class="display_currency total_due" data-orig-value="' . $due . '">' .
                    number_format($due, 2) . '</span>';
            })
            ->rawColumns(['total_before_tax', 'discount_amount', 'tax_amount', 'final_total', 'total_paid', 'total_due'])
            ->make(true);
    } catch (\Exception $e) {
        \Log::error('Daily Sales Error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}



    /**
     * Get daily payments breakdown
     */
    private function getDailyPayments($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('transaction_payments.paid_on', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $payments = $query->select(
            'method',
            DB::raw('SUM(IF(is_return = 1, -1 * amount, amount)) as total_amount')
        )
            ->groupBy('method')
            ->pluck('total_amount', 'method');

        return [
            'cash' => $payments['cash'] ?? 0,
            'card' => $payments['card'] ?? 0,
            'bank_transfer' => $payments['bank_transfer'] ?? 0,
            'total' => $payments->sum()
        ];
    }

    /**
     * Get summary data for widgets
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date', \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'));
            $end_date = $request->get('end_date', \Carbon\Carbon::now()->format('Y-m-d'));

            // Ensure dates are in Y-m-d format
            try {
                if (!(\Carbon\Carbon::hasFormat($start_date, 'Y-m-d'))) {
                    $start_date = \Carbon\Carbon::parse($start_date)->format('Y-m-d');
                }
                if (!(\Carbon\Carbon::hasFormat($end_date, 'Y-m-d'))) {
                    $end_date = \Carbon\Carbon::parse($end_date)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::warning('Date parsing error in getSummary: ' . $e->getMessage());
                $start_date = \Carbon\Carbon::now()->subDays(30)->format('Y-m-d');
                $end_date = \Carbon\Carbon::now()->format('Y-m-d');
            }
            $permitted_locations = auth()->user()->permitted_locations();

            // Get sales total
            $sales_query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $sales_query->whereIn('location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $sales_query->where('location_id', $location_id);
            }

            $total_sales = $sales_query->sum('final_total');

            // Get purchase total
            $purchase_query = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('status', 'received')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $purchase_query->whereIn('location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $purchase_query->where('location_id', $location_id);
            }

            $total_purchases = $purchase_query->sum('final_total');

            // Get expense total
            $expense_query = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $expense_query->whereIn('location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $expense_query->where('location_id', $location_id);
            }

            $total_expenses = $expense_query->sum('final_total');

            // Calculate averages - use actual transaction days, not total days in range
            $transaction_dates = $this->getDatesWithTransactions($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            $days_count = $transaction_dates->count();

            return response()->json([
                'total_sales' => $total_sales,
                'total_purchases' => $total_purchases,
                'total_expenses' => $total_expenses,
                'net_profit' => $total_sales - $total_purchases - $total_expenses,
                'avg_daily_sales' => $days_count > 0 ? ($total_sales / $days_count) : 0,
                'avg_daily_profit' => $days_count > 0 ? (($total_sales - $total_purchases - $total_expenses) / $days_count) : 0,
                'days_count' => $days_count, // Days with actual transactions
                'profitable_days' => $this->getProfitableDaysCount($business_id, $start_date, $end_date, $location_id, $permitted_locations)
            ]);
        } catch (\Exception $e) {
            \Log::error('Daily Summary Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get count of profitable days
     */
    private function getProfitableDaysCount($business_id, $start_date, $end_date, $location_id = null, $permitted_locations = null)
{
    $profitable_days = 0;

    // Get only dates that have transactions
    $dates = $this->getDatesWithTransactions($business_id, $start_date, $end_date, $location_id, $permitted_locations);

    foreach ($dates as $date) {
    $sales = $this->getDailySalesData($business_id, $date, $location_id, $permitted_locations);
$purchases = $this->getDailyPurchasesData($business_id, $date, $location_id, $permitted_locations);
$expenses = $this->getDailyExpensesData($business_id, $date, $location_id, $permitted_locations);
        $net_profit = ($sales['total'] ?? 0) - ($purchases['total'] ?? 0) - ($expenses['total'] ?? 0);

        if ($net_profit > 0) {
            $profitable_days++;
        }
    }

    return $profitable_days;
}

   /**
 * Get detailed data for a specific date (FIXED VERSION)
 */
public function getDailyDetails(Request $request)
{
    if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $date = $request->get('date');
        $location_id = $request->get('location_id');
        $permitted_locations = auth()->user()->permitted_locations();

        // Debug information
        \Log::info('Daily Details Debug', [
            'business_id' => $business_id,
            'date' => $date,
            'location_id' => $location_id,
            'permitted_locations' => $permitted_locations
        ]);

        // FIXED: Use the detailed methods that return individual transactions
        $sales = $this->getDetailedSales($business_id, $date, $location_id, $permitted_locations);
        $purchases = $this->getDetailedPurchases($business_id, $date, $location_id, $permitted_locations);
        $expenses = $this->getDetailedExpenses($business_id, $date, $location_id, $permitted_locations);
        $payments = $this->getDetailedPayments($business_id, $date, $location_id, $permitted_locations);

        \Log::info('Detailed data counts', [
            'sales' => count($sales),
            'purchases' => count($purchases),
            'expenses' => count($expenses),
            'payments' => count($payments)
        ]);

        return response()->json([
            'date' => $date,
            'formatted_date' => \Carbon\Carbon::parse($date)->format('d M Y'),
            'sales' => $sales,
            'purchases' => $purchases,
            'expenses' => $expenses,
            'payments' => $payments,
            'debug' => [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'sales_count' => count($sales),
                'purchases_count' => count($purchases),
                'expenses_count' => count($expenses),
                'payments_count' => count($payments)
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Daily Details Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

/**
 * Get detailed sales for a date (UPDATED VERSION)
 */
private function getDetailedSales($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id') // Use leftJoin for walk-in customers
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'sell')
        ->where('transactions.status', 'final')
        ->whereDate('transactions.transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('transactions.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('transactions.location_id', $location_id);
    }

    $results = $query->select([
        'transactions.id',
        'transactions.invoice_no',
        'contacts.name as customer_name',
        'transactions.final_total',
        'transactions.total_before_tax',
        'transactions.tax_amount',
        'transactions.discount_amount',
        'transactions.transaction_date'
    ])->get();

    // Add payment calculations to each transaction
    foreach ($results as $transaction) {
        $total_paid = DB::table('transaction_payments')
            ->where('transaction_id', $transaction->id)
            ->sum('amount');
        
        $transaction->total_paid = $total_paid;
        $transaction->balance_due = $transaction->final_total - $total_paid;
    }

    return $results;
}

/**
 * Get detailed purchases for a date (UPDATED VERSION)
 */
private function getDetailedPurchases($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'purchase')
        ->where('transactions.status', 'received')
        ->whereDate('transactions.transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('transactions.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('transactions.location_id', $location_id);
    }

    $results = $query->select([
        'transactions.id',
        'transactions.ref_no',
        'contacts.name as supplier_name',
        'transactions.final_total',
        'transactions.transaction_date'
    ])->get();

    // Add payment calculations to each transaction
    foreach ($results as $transaction) {
        $total_paid = DB::table('transaction_payments')
            ->where('transaction_id', $transaction->id)
            ->sum('amount');
        
        $transaction->total_paid = $total_paid;
        $transaction->balance_due = $transaction->final_total - $total_paid;
    }

    return $results;
}
/**
 * Get detailed expenses for a date (UPDATED VERSION)
 */
private function getDetailedExpenses($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = Transaction::leftJoin('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'expense')
        ->whereDate('transactions.transaction_date', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('transactions.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('transactions.location_id', $location_id);
    }

    return $query->select([
        'transactions.ref_no',
        'expense_categories.name as category_name',
        'transactions.final_total',
        'transactions.additional_notes',
        'transactions.transaction_date'
    ])->get();
}

/**
 * Get detailed payments for a date (UPDATED VERSION)
 */
private function getDetailedPayments($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->leftJoin('contacts', 't.contact_id', '=', 'contacts.id')
        ->where('t.business_id', $business_id)
        ->whereDate('transaction_payments.paid_on', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('t.location_id', $location_id);
    }

    return $query->select([
        'transaction_payments.payment_ref_no',
        'contacts.name as contact_name',
        't.type as transaction_type',
        'transaction_payments.method',
        'transaction_payments.amount',
        'transaction_payments.note',
        'transaction_payments.paid_on'
    ])->get();
}
    /**
     * Export daily summary report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business = $this->businessUtil->getDetails($business_id);

        $filters = [
            'location_id' => $request->location_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $filename = 'daily_summary_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new DailySummaryReportExport($business_id, $filters), $filename);
    }

    /**
     * Get daily purchase data for DataTable
     */
    public function getDailyPurchase(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            $query = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            return Datatables::of($query)
                ->addColumn('supplier_name', function ($row) {
                    return $row->name;
                })
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency final_total" data-orig-value="' . $row->final_total . '">' .
                        number_format($row->final_total, 2) . '</span>';
                })
                ->addColumn('total_paid', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    return '<span class="display_currency paid_amount" data-orig-value="' . $paid . '">' .
                        number_format($paid, 2) . '</span>';
                })
                ->addColumn('total_due', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    $due = $row->final_total - $paid;
                    return '<span class="display_currency total_due" data-orig-value="' . $due . '">' .
                        number_format($due, 2) . '</span>';
                })
                ->rawColumns(['final_total', 'total_paid', 'total_due'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Purchase Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get daily purchase return data for DataTable
     */
    public function getDailyPurchaseReturn(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            $query = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_return');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            return Datatables::of($query)
                ->addColumn('supplier_name', function ($row) {
                    return $row->name;
                })
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency final_total" data-orig-value="' . $row->final_total . '">' .
                        number_format($row->final_total, 2) . '</span>';
                })
                ->addColumn('total_paid', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    return '<span class="display_currency paid_amount" data-orig-value="' . $paid . '">' .
                        number_format($paid, 2) . '</span>';
                })
                ->addColumn('total_due', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    $due = $row->final_total - $paid;
                    return '<span class="display_currency total_due" data-orig-value="' . $due . '">' .
                        number_format($due, 2) . '</span>';
                })
                ->rawColumns(['final_total', 'total_paid', 'total_due'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Purchase Return Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get daily purchase payment data for DataTable
     */
    public function getDailyPurchasePayment(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->join('contacts', 't.contact_id', '=', 'contacts.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transaction_payments.paid_on', '>=', $start_date)
                    ->whereDate('transaction_payments.paid_on', '<=', $end_date);
            }

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            return Datatables::of($query)
                ->addColumn('supplier_name', function ($row) {
                    return $row->name;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-orig-value="' . $row->amount . '">' .
                        number_format($row->amount, 2) . '</span>';
                })
                ->rawColumns(['amount'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Purchase Payment Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Get daily sale return data for DataTable
     */
    public function getDailySaleReturn(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            $query = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_return');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            return Datatables::of($query)
                ->addColumn('contact_id', function ($row) {
                    return $row->contact_id;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->name ?: 'Walk-in Customer';
                })
                ->editColumn('transaction_date', function ($row) {
                    return \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y');
                })
                ->editColumn('total_before_tax', function ($row) {
                    return '<span class="display_currency total_before_tax" data-orig-value="' . $row->total_before_tax . '">' .
                        number_format($row->total_before_tax, 2) . '</span>';
                })
                ->editColumn('discount_amount', function ($row) {
                    return '<span class="display_currency discount_amount" data-orig-value="' . $row->discount_amount . '">' .
                        number_format($row->discount_amount, 2) . '</span>';
                })
                ->editColumn('tax_amount', function ($row) {
                    return '<span class="display_currency tax_amount" data-orig-value="' . $row->tax_amount . '">' .
                        number_format($row->tax_amount, 2) . '</span>';
                })
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency final_total" data-orig-value="' . $row->final_total . '">' .
                        number_format($row->final_total, 2) . '</span>';
                })
                ->addColumn('total_paid', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    return '<span class="display_currency total_paid" data-orig-value="' . $paid . '">' .
                        number_format($paid, 2) . '</span>';
                })
                ->addColumn('total_due', function ($row) {
                    $paid = DB::table('transaction_payments')->where('transaction_id', $row->id)->sum('amount');
                    $due = $row->final_total - $paid;
                    return '<span class="display_currency total_due" data-orig-value="' . $due . '">' .
                        number_format($due, 2) . '</span>';
                })
                ->rawColumns(['total_before_tax', 'discount_amount', 'tax_amount', 'final_total', 'total_paid', 'total_due'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Sale Return Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get daily sell payment data for DataTable
     */
    public function getDailySellPayment(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $permitted_locations = auth()->user()->permitted_locations();

            $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->join('contacts', 't.contact_id', '=', 'contacts.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transaction_payments.paid_on', '>=', $start_date)
                    ->whereDate('transaction_payments.paid_on', '<=', $end_date);
            }

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            return Datatables::of($query)
                ->addColumn('customer_name', function ($row) {
                    return $row->name ?: 'Walk-in Customer';
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-orig-value="' . $row->amount . '">' .
                        number_format($row->amount, 2) . '</span>';
                })
                ->rawColumns(['amount'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daily Sell Payment Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}