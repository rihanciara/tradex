<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\User;
use App\Brands;
use App\Transaction;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use App\TransactionSellLine;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class BrandMonthlySalesController extends Controller
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
     * Display brand monthly sales report
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $brands = Brands::forDropdown($business_id);
        $users = User::forDropdown($business_id, false, false, true);

        // Ensure brands is always an array
        if (is_object($brands)) {
            $brands = $brands->toArray();
        }

        // Get payment types for filters
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        if (is_object($payment_types)) {
            $payment_types = $payment_types->toArray();
        }
        $payment_types = ['' => __('lang_v1.all')] + $payment_types;

        return view('advancedreports::brand-monthly.index')
            ->with(compact(
                'business_locations',
                'brands',
                'users',
                'payment_types'
            ));
    }

    /**
     * Get brand monthly sales data for DataTables
     */
    public function getBrandMonthlyData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));

        try {
            // Build base query for brand sales data using standard line-item approach
            $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            // Apply filters
            $this->applyFilters($query, $request);

            // Group by brand and get monthly data using simple line-item calculation
            $monthlyData = $query->select([
                'b.id as brand_id',
                'b.name as brand_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as monthly_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as monthly_purchase_cost'),
                DB::raw('SUM(tsl.quantity) as monthly_quantity')
            ])
                ->groupBy('b.id', 'b.name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data into the required format
            $brandData = [];
            foreach ($monthlyData as $data) {
                $brandId = $data->brand_id ?? 0; // Handle NULL brand_id
                $brandName = $data->brand_name ?? 'No Brand';

                if (!isset($brandData[$brandId])) {
                    $brandData[$brandId] = [
                        'brand_id' => $brandId,
                        'brand_name' => $brandName,
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
                        'jan_qty' => 0,
                        'feb_qty' => 0,
                        'mar_qty' => 0,
                        'apr_qty' => 0,
                        'may_qty' => 0,
                        'jun_qty' => 0,
                        'jul_qty' => 0,
                        'aug_qty' => 0,
                        'sep_qty' => 0,
                        'oct_qty' => 0,
                        'nov_qty' => 0,
                        'dec_qty' => 0,
                        'total_sales' => 0,
                        'total_cost' => 0,
                        'total_quantity' => 0
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
                $brandData[$brandId][$monthKey] = $data->monthly_sales;
                $brandData[$brandId][$monthKey . '_cost'] = $data->monthly_purchase_cost;
                $brandData[$brandId][$monthKey . '_qty'] = $data->monthly_quantity;
                $brandData[$brandId]['total_sales'] += $data->monthly_sales;
                $brandData[$brandId]['total_cost'] += $data->monthly_purchase_cost;
                $brandData[$brandId]['total_quantity'] += $data->monthly_quantity;
            }

            // Convert to collection for DataTables
            $collection = collect(array_values($brandData));

            return DataTables::of($collection)
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-info btn-xs view-brand-details" 
                            data-brand-id="' . $row['brand_id'] . '">
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
                ->addColumn('total_quantity', function ($row) {
                    return '<span class="text-primary"><strong>' .
                        number_format($row['total_quantity'], 2) . '</strong></span>';
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
                    'total_quantity',
                    'gross_profit',
                    'gross_profit_percent'
                ])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Brand Monthly Sales Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading brand monthly sales data'], 500);
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

            $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            // Apply same filters
            $this->applyFilters($query, $request);

            $summary = $query->select([
                DB::raw('COUNT(DISTINCT COALESCE(b.id, 0)) as total_brands'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as total_cost'),
                DB::raw('SUM(tsl.quantity) as total_quantity')
            ])->first();

            $total_profit = $summary->total_sales - $summary->total_cost;
            $profit_margin = $summary->total_sales > 0 ? ($total_profit / $summary->total_sales) * 100 : 0;

            return response()->json([
                'total_brands' => (int)$summary->total_brands,
                'total_transactions' => (int)$summary->total_transactions,
                'total_sales' => $summary->total_sales,
                'total_cost' => $summary->total_cost,
                'total_profit' => $total_profit,
                'profit_margin' => $profit_margin,
                'total_quantity' => $summary->total_quantity,
                'average_per_brand' => $summary->total_brands > 0 ? ($summary->total_sales / $summary->total_brands) : 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Brand Monthly Sales Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    /**
     * Export brand monthly sales report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            // Get data directly without Excel processing
            $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            $this->applyFilters($query, $request);

            $monthlyData = $query->select([
                'b.id as brand_id',
                'b.name as brand_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as monthly_sales'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax * 0.7) as monthly_purchase_cost'),
                DB::raw('SUM(tsl.quantity) as monthly_quantity')
            ])
                ->groupBy('b.id', 'b.name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data
            $brandData = [];
            foreach ($monthlyData as $data) {
                $brandId = $data->brand_id ?? 0;
                $brandName = $data->brand_name ?? 'No Brand';

                if (!isset($brandData[$brandId])) {
                    $brandData[$brandId] = [
                        'brand_name' => $brandName,
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
                        'total_cost' => 0,
                        'total_quantity' => 0
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
                $quantity = (float)($data->monthly_quantity ?? 0);

                $brandData[$brandId][$monthKey] = $sales;
                $brandData[$brandId]['total_sales'] += $sales;
                $brandData[$brandId]['total_cost'] += $cost;
                $brandData[$brandId]['total_quantity'] += $quantity;
            }

            // Create CSV content
            $filename = 'brand_monthly_sales_' . $year . '_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($brandData) {
                $file = fopen('php://output', 'w');

                // Add headers
                fputcsv($file, [
                    'Brand/Supplier',
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
                    'Total Sales ($)',
                    'Total Quantity',
                    'Gross Profit ($)',
                    'Gross Profit (%)'
                ]);

                // Add data
                foreach ($brandData as $row) {
                    $total_sales = (float)($row['total_sales'] ?? 0);
                    $total_cost = (float)($row['total_cost'] ?? 0);
                    $gross_profit = $total_sales - $total_cost;
                    $profit_percentage = $total_sales > 0 ? ($gross_profit / $total_sales) * 100 : 0;

                    fputcsv($file, [
                        $row['brand_name'],
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
                        number_format($row['total_quantity'], 2),
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
     * Get brand details for a specific brand
     */
    public function getBrandDetails(Request $request, $brandId)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            // Get brand info
            if ($brandId == 0) {
                $brand = (object)[
                    'id' => 0,
                    'name' => 'No Brand',
                    'description' => 'Products without assigned brand'
                ];
            } else {
                $brand = Brands::where('business_id', $business_id)
                    ->where('id', $brandId)
                    ->first();

                if (!$brand) {
                    return response()->json(['error' => 'Brand not found'], 404);
                }
            }

            // Get products and sales for this brand
            $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereYear('transactions.transaction_date', $year)
                ->whereNull('tsl.parent_sell_line_id');

            if ($brandId == 0) {
                $query->whereNull('p.brand_id');
            } else {
                $query->where('p.brand_id', $brandId);
            }

            $products = $query->select([
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
                ->limit(100)
                ->get();

            // Calculate summary data
            $totalAmount = $products->sum('line_total');
            $totalQuantity = $products->sum('quantity');
            $totalTransactions = $products->groupBy('transaction_id')->count();
            $averagePerTransaction = $totalTransactions > 0 ? ($totalAmount / $totalTransactions) : 0;

            return response()->json([
                'success' => true,
                'brand' => $brand,
                'products' => $products,
                'overall_summary' => [
                    'total_transactions' => $totalTransactions,
                    'total_amount' => $totalAmount,
                    'total_quantity' => $totalQuantity,
                    'average_per_transaction' => $averagePerTransaction
                ],
                'year' => $year
            ]);
        } catch (\Exception $e) {
            \Log::error('Brand Details Error: ' . $e->getMessage(), [
                'brand_id' => $brandId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error loading brand details: ' . $e->getMessage()
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

        // Brand filter
        if (!empty($request->brand_id)) {
            if ($request->brand_id == 0) {
                $query->whereNull('p.brand_id');
            } else {
                $query->where('p.brand_id', $request->brand_id);
            }
        }

        // Brand name search
        if (!empty($request->brand_name)) {
            $query->where('b.name', 'like', '%' . $request->brand_name . '%');
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

    /**
     * Shows brand-wise product sales report with purchase price analysis
     * Add this method to your existing ReportController.php
     *
     * @return \Illuminate\Http\Response
     */
    public function getBrandWiseReport(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'transaction_sell_lines.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('transaction_sell_lines.parent_sell_line_id');

            // Apply date filter
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('t.transaction_date', [$start_date, $end_date]);
            }

            // Apply location filter
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            // Apply brand filter
            $brand_id = $request->get('brand_id', null);
            if (!empty($brand_id)) {
                if ($brand_id == '0') {
                    // Filter for products with no brand
                    $query->whereNull('p.brand_id');
                } else {
                    $query->where('p.brand_id', $brand_id);
                }
            }

            // Group by brand and get totals
            $brandData = $query->select([
                DB::raw('COALESCE(b.name, "No Brand") as brand_name'),
                DB::raw('COALESCE(b.id, 0) as brand_id'),
                DB::raw('SUM(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_qty_sold'),
                DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) as total_sales_amount'),
                DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7)) as total_purchase_cost'),
                DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
                DB::raw('COUNT(DISTINCT p.id) as total_products')
            ])
                ->groupBy('b.id', 'b.name')
                ->orderBy('total_sales_amount', 'desc');

            return DataTables::of($brandData)
                ->addColumn('action', function ($row) {
                    return '<a href="#" class="btn btn-xs btn-info view-brand-products" data-brand-id="' . $row->brand_id . '">' . __('messages.view') . '</a>';
                })
                ->editColumn('brand_name', function ($row) {
                    return $row->brand_name ?: 'No Brand';
                })
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol="false">' . $this->transactionUtil->num_f($row->total_qty_sold, false, null, true) . '</span>';
                })
                ->editColumn('total_sales_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->transactionUtil->num_f($row->total_sales_amount, true) . '</span>';
                })
                ->addColumn('total_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->transactionUtil->num_f($row->total_purchase_cost, true) . '</span>';
                })
                ->addColumn('gross_profit', function ($row) {
                    $profit = $row->total_sales_amount - $row->total_purchase_cost;
                    $class = $profit >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="display_currency ' . $class . '" data-currency_symbol="true">' . $this->transactionUtil->num_f($profit, true) . '</span>';
                })
                ->addColumn('profit_margin', function ($row) {
                    $profit = $row->total_sales_amount - $row->total_purchase_cost;
                    $margin = $row->total_sales_amount > 0 ? ($profit / $row->total_sales_amount) * 100 : 0;
                    $class = $margin >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $class . '">' . $this->transactionUtil->num_f($margin, false) . '%</span>';
                })
                ->editColumn('total_transactions', function ($row) {
                    return number_format($row->total_transactions);
                })
                ->editColumn('total_products', function ($row) {
                    return number_format($row->total_products);
                })
                // Add raw values for footer calculations
                ->addColumn('total_qty_sold_raw', function ($row) {
                    return (float)$row->total_qty_sold;
                })
                ->addColumn('total_sales_amount_raw', function ($row) {
                    return (float)$row->total_sales_amount;
                })
                ->addColumn('total_purchase_cost_raw', function ($row) {
                    return (float)$row->total_purchase_cost;
                })
                ->rawColumns(['action', 'total_qty_sold', 'total_sales_amount', 'total_purchase_price', 'gross_profit', 'profit_margin'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $brands = Brands::forDropdown($business_id);

        // Ensure brands is an array and add "No Brand" option
        if (is_object($brands)) {
            $brands = $brands->toArray();
        }
        $brands = [0 => 'No Brand'] + $brands;

        return view('advancedreports::brand-monthly.brand_wise_report')
            ->with(compact('business_locations', 'brands'));
    }

    /**
     * Get brand products for modal view
     */
    public function getBrandProducts(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $brand_id = $request->get('brand_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            // Get brand info
            if ($brand_id == 0) {
                $brand = (object)[
                    'id' => 0,
                    'name' => 'No Brand',
                    'description' => 'Products without assigned brand'
                ];
            } else {
                $brand = Brands::where('business_id', $business_id)
                    ->where('id', $brand_id)
                    ->first();

                if (!$brand) {
                    return response()->json(['success' => false, 'message' => 'Brand not found'], 404);
                }
            }

            // Build query for brand products
            $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereNull('tsl.parent_sell_line_id');

            // Apply date filter
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            }

            // Apply location filter
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            // Apply brand filter
            if ($brand_id == 0) {
                $query->whereNull('p.brand_id');
            } else {
                $query->where('p.brand_id', $brand_id);
            }

            // Get products with transaction details
            $products = $query->select([
                'transactions.id as transaction_id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.payment_status',
                'p.name as product_name',
                'p.id as product_id',
                'v.name as variation_name',
                'tsl.quantity',
                'tsl.quantity_returned',
                'tsl.unit_price_inc_tax',
                DB::raw('(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) as net_quantity'),
                DB::raw('((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as line_total'),
                DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%m-%d") as formatted_date')
            ])
                ->orderBy('transactions.transaction_date', 'desc')
                ->limit(500) // Limit to avoid memory issues
                ->get();

            // Calculate summary data
            $summary = [
                'total_products' => $products->groupBy('product_id')->count(),
                'total_quantity' => $products->sum('net_quantity'),
                'total_amount' => $products->sum('line_total'),
                'total_transactions' => $products->groupBy('transaction_id')->count(),
            ];

            // Format products for display
            $formattedProducts = $products->map(function ($product) {
                return [
                    'transaction_id' => $product->transaction_id,
                    'invoice_no' => $product->invoice_no,
                    'transaction_date' => $product->formatted_date,
                    'payment_status' => $product->payment_status,
                    'product_name' => $product->product_name,
                    'variation_name' => $product->variation_name,
                    'quantity' => $product->net_quantity,
                    'unit_price_inc_tax' => $product->unit_price_inc_tax,
                    'line_total' => $product->line_total
                ];
            });

            return response()->json([
                'success' => true,
                'brand' => $brand,
                'products' => $formattedProducts,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            \Log::error('Brand Products Error: ' . $e->getMessage(), [
                'brand_id' => $request->get('brand_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading brand products: ' . $e->getMessage()
            ], 500);
        }
    }
}
