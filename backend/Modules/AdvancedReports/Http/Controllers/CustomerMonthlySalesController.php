<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Contact;
use App\Transaction;
use App\BusinessLocation;
use App\User;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdvancedReports\Exports\CustomerMonthlySalesExport;

class CustomerMonthlySalesController extends Controller
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
     * Display customer monthly sales report
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $users = User::forDropdown($business_id, false, false, true);

        // Get payment types for filters
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $payment_types = collect($payment_types)->prepend(__('lang_v1.all'), '');

        return view('advancedreports::customer-monthly.index')
            ->with(compact(
                'business_locations',
                'customers',
                'users',
                'payment_types'
            ));
    }

    /**
     * Get customer monthly sales data for DataTables
     */
    public function getCustomerMonthlyData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));

        try {
            // Build base query for customer sales data
            $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            // Apply filters
            $this->applyFilters($query, $request);

            // Group by customer and get monthly data
            $monthlyData = $query->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.supplier_business_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as monthly_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as monthly_purchase_cost')
            ])
                ->groupBy('c.id', 'c.name', 'c.supplier_business_name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data into the required format
            $customerData = [];
            foreach ($monthlyData as $data) {
                $customerId = $data->customer_id;

                if (!isset($customerData[$customerId])) {
                    $customerName = $data->customer_name;
                    if (!empty($data->supplier_business_name)) {
                        $customerName = $data->supplier_business_name . ' - ' . $customerName;
                    }

                    $customerData[$customerId] = [
                        'customer_id' => $customerId,
                        'customer_name' => $customerName,
                        'jan' => 0,
                        'feb' => 0,
                        'mar' => 0,
                        'apr' => 0,
                        'may' => 0,
                        'jun' => 0,
                        'jul' => 0,
                        'aug' => 0,
                        'sep' => 0,
                        'oct' => 0,
                        'nov' => 0,
                        'dec' => 0,
                        'jan_cost' => 0,
                        'feb_cost' => 0,
                        'mar_cost' => 0,
                        'apr_cost' => 0,
                        'may_cost' => 0,
                        'jun_cost' => 0,
                        'jul_cost' => 0,
                        'aug_cost' => 0,
                        'sep_cost' => 0,
                        'oct_cost' => 0,
                        'nov_cost' => 0,
                        'dec_cost' => 0,
                        'total_sales' => 0,
                        'total_cost' => 0
                    ];
                }

                $monthNames = [
                    1 => 'jan',
                    2 => 'feb',
                    3 => 'mar',
                    4 => 'apr',
                    5 => 'may',
                    6 => 'jun',
                    7 => 'jul',
                    8 => 'aug',
                    9 => 'sep',
                    10 => 'oct',
                    11 => 'nov',
                    12 => 'dec'
                ];

                $monthKey = $monthNames[$data->month];
                $customerData[$customerId][$monthKey] = $data->monthly_sales;
                $customerData[$customerId][$monthKey . '_cost'] = $data->monthly_purchase_cost;
                $customerData[$customerId]['total_sales'] += $data->monthly_sales;
                $customerData[$customerId]['total_cost'] += $data->monthly_purchase_cost;
            }

            // Convert to collection for DataTables
            $collection = collect(array_values($customerData));

            return DataTables::of($collection)
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-info btn-xs view-customer-details" 
                            data-customer-id="' . $row['customer_id'] . '">
                        <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </button>';
                })
                ->editColumn('jan', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jan'], 2) . '</span>';
                })
                ->editColumn('feb', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['feb'], 2) . '</span>';
                })
                ->editColumn('mar', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['mar'], 2) . '</span>';
                })
                ->editColumn('apr', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['apr'], 2) . '</span>';
                })
                ->editColumn('may', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['may'], 2) . '</span>';
                })
                ->editColumn('jun', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jun'], 2) . '</span>';
                })
                ->editColumn('jul', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jul'], 2) . '</span>';
                })
                ->editColumn('aug', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['aug'], 2) . '</span>';
                })
                ->editColumn('sep', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['sep'], 2) . '</span>';
                })
                ->editColumn('oct', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['oct'], 2) . '</span>';
                })
                ->editColumn('nov', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['nov'], 2) . '</span>';
                })
                ->editColumn('dec', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['dec'], 2) . '</span>';
                })
                ->editColumn('total_sales', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true"><strong>' .
                        number_format($row['total_sales'], 2) . '</strong></span>';
                })
                ->addColumn('gross_profit', function ($row) {
                    $profit = $row['total_sales'] - $row['total_cost'];
                    $class = $profit >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="display_currency ' . $class . '" data-currency_symbol="true"><strong>' .
                        number_format($profit, 2) . '</strong></span>';
                })
                ->addColumn('gross_profit_percent', function ($row) {
                    $profit = $row['total_sales'] - $row['total_cost'];
                    $percentage = $row['total_sales'] > 0 ? ($profit / $row['total_sales']) * 100 : 0;
                    $class = $percentage >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $class . '"><strong>' .
                        number_format($percentage, 2) . '%</strong></span>';
                })
                ->rawColumns([
                    'action',
                    'jan',
                    'feb',
                    'mar',
                    'apr',
                    'may',
                    'jun',
                    'jul',
                    'aug',
                    'sep',
                    'oct',
                    'nov',
                    'dec',
                    'total_sales',
                    'gross_profit',
                    'gross_profit_percent'
                ])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Customer Monthly Sales Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading customer monthly sales data'], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            // Apply same filters
            $this->applyFilters($query, $request);

            $summary = $query->select([
                DB::raw('COUNT(DISTINCT c.id) as total_customers'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as total_cost')
            ])->first();

            $total_profit = $summary->total_sales - $summary->total_cost;
            $profit_margin = $summary->total_sales > 0 ? ($total_profit / $summary->total_sales) * 100 : 0;

            return response()->json([
                'total_customers' => (int)$summary->total_customers,
                'total_transactions' => (int)$summary->total_transactions,
                'total_sales' => $summary->total_sales,
                'total_cost' => $summary->total_cost,
                'total_profit' => $total_profit,
                'profit_margin' => $profit_margin,
                'average_per_customer' => $summary->total_customers > 0 ? ($summary->total_sales / $summary->total_customers) : 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer Monthly Sales Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            // Get data directly without Excel processing
            $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            $monthlyData = $query->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.supplier_business_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as monthly_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as monthly_purchase_cost')
            ])
                ->groupBy('c.id', 'c.name', 'c.supplier_business_name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data
            $customerData = [];
            foreach ($monthlyData as $data) {
                $customerId = $data->customer_id;

                if (!isset($customerData[$customerId])) {
                    $customerName = $data->customer_name ?: 'Unknown';
                    if ($data->supplier_business_name) {
                        $customerName = $data->supplier_business_name . ' - ' . $customerName;
                    }

                    $customerData[$customerId] = [
                        'customer_name' => $customerName,
                        'jan' => 0,
                        'feb' => 0,
                        'mar' => 0,
                        'apr' => 0,
                        'may' => 0,
                        'jun' => 0,
                        'jul' => 0,
                        'aug' => 0,
                        'sep' => 0,
                        'oct' => 0,
                        'nov' => 0,
                        'dec' => 0,
                        'total_sales' => 0,
                        'total_cost' => 0
                    ];
                }

                $months = [
                    1 => 'jan',
                    2 => 'feb',
                    3 => 'mar',
                    4 => 'apr',
                    5 => 'may',
                    6 => 'jun',
                    7 => 'jul',
                    8 => 'aug',
                    9 => 'sep',
                    10 => 'oct',
                    11 => 'nov',
                    12 => 'dec'
                ];

                $monthKey = $months[$data->month] ?? 'jan';
                $sales = (float)($data->monthly_sales ?? 0);
                $cost = (float)($data->monthly_purchase_cost ?? 0);

                $customerData[$customerId][$monthKey] = $sales;
                $customerData[$customerId]['total_sales'] += $sales;
                $customerData[$customerId]['total_cost'] += $cost;
            }

            // Create CSV content
            $filename = 'customer_monthly_sales_' . $year . '_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($customerData) {
                $file = fopen('php://output', 'w');

                // Add headers
                fputcsv($file, [
                    'Customer',
                    'Jan',
                    'Feb',
                    'Mar',
                    'Apr',
                    'May',
                    'Jun',
                    'Jul',
                    'Aug',
                    'Sep',
                    'Oct',
                    'Nov',
                    'Dec',
                    'Total Sales',
                    'Gross Profit ($)',
                    'Gross Profit (%)'
                ]);

                // Add data
                foreach ($customerData as $row) {
                    $total_sales = (float)($row['total_sales'] ?? 0);
                    $total_cost = (float)($row['total_cost'] ?? 0);
                    $gross_profit = $total_sales - $total_cost;
                    $profit_percentage = $total_sales > 0 ? ($gross_profit / $total_sales) * 100 : 0;

                    fputcsv($file, [
                        $row['customer_name'],
                        number_format($row['jan'], 2),
                        number_format($row['feb'], 2),
                        number_format($row['mar'], 2),
                        number_format($row['apr'], 2),
                        number_format($row['may'], 2),
                        number_format($row['jun'], 2),
                        number_format($row['jul'], 2),
                        number_format($row['aug'], 2),
                        number_format($row['sep'], 2),
                        number_format($row['oct'], 2),
                        number_format($row['nov'], 2),
                        number_format($row['dec'], 2),
                        number_format($total_sales, 2),
                        number_format($gross_profit, 2),
                        number_format($profit_percentage, 2) . '%'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('CSV Export error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Get customer details for a specific customer
     */
    public function getCustomerDetails(Request $request, $customerId)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            \Log::info('Getting customer details', ['customer_id' => $customerId, 'year' => $year]);

            // Get customer info
            $customer = Contact::where('business_id', $business_id)
                ->where('id', $customerId)
                ->first();

            if (!$customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            // Get transactions for this customer and year
            $transactions = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.contact_id', $customerId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id')
                ->select([
                    'transactions.id as transaction_id',
                    'transactions.invoice_no',
                    'transactions.transaction_date',
                    'transactions.payment_status',
                    'p.name as product_name',
                    'v.name as variation_name',
                    'tsl.quantity',
                    'tsl.unit_price_inc_tax',
                    DB::raw('(tsl.quantity * tsl.unit_price_inc_tax) as line_total'),
                    DB::raw('MONTH(transactions.transaction_date) as month'),
                    DB::raw('MONTHNAME(transactions.transaction_date) as month_name')
                ])
                ->orderBy('transactions.transaction_date', 'desc')
                ->limit(50) // Limit to last 50 transactions
                ->get();

            \Log::info('Found transactions', ['count' => $transactions->count()]);

            // Calculate summary data
            $totalAmount = $transactions->sum('line_total');
            $totalQuantity = $transactions->sum('quantity');
            $totalTransactions = $transactions->groupBy('transaction_id')->count();
            $averagePerTransaction = $totalTransactions > 0 ? ($totalAmount / $totalTransactions) : 0;

            // Group by month for monthly summary
            $monthlySummary = $transactions->groupBy('month')->map(function ($monthTransactions, $month) {
                return [
                    'month' => $month,
                    'month_name' => $monthTransactions->first()->month_name,
                    'total_transactions' => $monthTransactions->groupBy('transaction_id')->count(),
                    'total_amount' => $monthTransactions->sum('line_total'),
                    'total_quantity' => $monthTransactions->sum('quantity')
                ];
            })->values();

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'supplier_business_name' => $customer->supplier_business_name,
                    'contact_id' => $customer->contact_id,
                    'mobile' => $customer->mobile,
                    'email' => $customer->email,
                    'address_line_1' => $customer->address_line_1,
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'country' => $customer->country,
                ],
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'transaction_id' => $transaction->transaction_id,
                        'invoice_no' => $transaction->invoice_no,
                        'transaction_date' => $transaction->transaction_date,
                        'payment_status' => $transaction->payment_status,
                        'product_name' => $transaction->product_name .
                            ($transaction->variation_name ? ' - ' . $transaction->variation_name : ''),
                        'quantity' => $transaction->quantity,
                        'unit_price_inc_tax' => $transaction->unit_price_inc_tax,
                        'line_total' => $transaction->line_total,
                        'month' => $transaction->month,
                        'month_name' => $transaction->month_name
                    ];
                }),
                'monthly_summary' => $monthlySummary,
                'overall_summary' => [
                    'total_transactions' => $totalTransactions,
                    'total_amount' => $totalAmount,
                    'total_quantity' => $totalQuantity,
                    'average_per_transaction' => $averagePerTransaction
                ],
                'year' => $year
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer Details Error: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error loading customer details: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        // Location filter
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($request->location_id)) {
            $query->where('transactions.location_id', $request->location_id);
        }

        // Customer filter - Handle both ID and name search
        if (!empty($request->customer_name)) {
            // Check if it's a numeric ID (from Select2) or text search
            if (is_numeric($request->customer_name)) {
                // It's a customer ID from Select2
                $query->where('transactions.contact_id', $request->customer_name);
            } else {
                // It's a text search
                $query->where(function ($q) use ($request) {
                    $q->where('c.name', 'like', '%' . $request->customer_name . '%')
                        ->orWhere('c.supplier_business_name', 'like', '%' . $request->customer_name . '%');
                });
            }
        }

        // Legacy customer_id filter (if still used elsewhere)
        if (!empty($request->customer_id)) {
            $query->where('transactions.contact_id', $request->customer_id);
        }

        // Payment status filter
        if (!empty($request->payment_status)) {
            $query->where('transactions.payment_status', $request->payment_status);
        }

        // Payment method filter
        if (!empty($request->payment_method)) {
            $query->whereHas('payment_lines', function ($q) use ($request) {
                $q->where('method', $request->payment_method);
            });
        }

        // User filter
        if (!empty($request->user_id)) {
            $query->where('transactions.created_by', $request->user_id);
        }
    }
}