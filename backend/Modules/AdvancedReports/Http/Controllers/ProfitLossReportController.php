<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProfitLossReportController extends Controller
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
     * Display enhanced profit loss report index
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.profit_loss_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get business locations
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        
        // Get customers for filtering
        $customers = Contact::customersDropdown($business_id, false);
        
        // Get current financial year
        $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
        
        // Prepare period options
        $period_options = [
            'today' => __('Today'),
            'this_week' => __('This Week'),
            'this_month' => __('This Month'),
            'this_quarter' => __('This Quarter'),
            'this_year' => __('This Year'),
            'last_week' => __('Last Week'),
            'last_month' => __('Last Month'),
            'last_quarter' => __('Last Quarter'),
            'last_year' => __('Last Year'),
            'custom' => __('Custom Range')
        ];

        return view('advancedreports::profit-loss.index')
            ->with(compact('business_locations', 'customers', 'fy', 'period_options'));
    }

    /**
     * Get profit loss data via AJAX
     */
    public function getProfitLossData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.profit_loss_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');

        // Get currency settings
        $currency_symbol = session('currency')['symbol'] ?? '$';
        $currency_precision = session('business.currency_precision') ?? 2;
        $currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';

        // Get permitted locations
        $permitted_locations = auth()->user()->permitted_locations();

        // Use TransactionUtil for accurate profit loss calculations including stock
        try {
            $profit_loss_data = $this->transactionUtil->getProfitLossDetails(
                $business_id,
                $location_id,
                $start_date,
                $end_date,
                $user_id,
                $permitted_locations
            );

            $total_revenue = $profit_loss_data['total_sell'] ?? 0;
            $total_cost = $profit_loss_data['total_purchase'] ?? 0;
            $gross_profit = $profit_loss_data['gross_profit'] ?? 0;
            $net_profit = $profit_loss_data['net_profit'] ?? 0;
            $opening_stock = $profit_loss_data['opening_stock'] ?? 0;
            $closing_stock = $profit_loss_data['closing_stock'] ?? 0;
            $total_expense = $profit_loss_data['total_expense'] ?? 0;
            $total_expense_tax = $profit_loss_data['total_expense_tax'] ?? 0;

            // Format currency helper
            $formatCurrency = function($value) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                $formatted = number_format($value, $currency_precision);
                return $currency_symbol_placement === 'after' ?
                    $formatted . $currency_symbol :
                    $currency_symbol . $formatted;
            };

            $data = [
                'total_sell_inc_tax' => round($total_revenue, 2),
                'total_sell_inc_tax_formatted' => $formatCurrency($total_revenue),
                'gross_profit' => round($gross_profit, 2),
                'gross_profit_formatted' => $formatCurrency($gross_profit),
                'net_profit' => round($net_profit, 2),
                'net_profit_formatted' => $formatCurrency($net_profit),
                'opening_stock' => round($opening_stock, 2),
                'opening_stock_formatted' => $formatCurrency($opening_stock),
                'closing_stock' => round($closing_stock, 2),
                'closing_stock_formatted' => $formatCurrency($closing_stock),
                'total_purchase' => round($total_cost, 2),
                'total_purchase_formatted' => $formatCurrency($total_cost),
                'total_expense' => round($total_expense, 2),
                'total_expense_formatted' => $formatCurrency($total_expense),
                'total_expense_tax' => round($total_expense_tax, 2),
                'total_expense_tax_formatted' => $formatCurrency($total_expense_tax),
                'total_operating_expenses' => round($total_expense + $total_expense_tax, 2),
                'total_operating_expenses_formatted' => $formatCurrency($total_expense + $total_expense_tax)
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting profit loss data for view: ' . $e->getMessage());

            $formatCurrency = function($value) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                $formatted = number_format($value, $currency_precision);
                return $currency_symbol_placement === 'after' ?
                    $formatted . $currency_symbol :
                    $currency_symbol . $formatted;
            };

            $data = [
                'total_sell_inc_tax' => 0,
                'total_sell_inc_tax_formatted' => $formatCurrency(0),
                'gross_profit' => 0,
                'gross_profit_formatted' => $formatCurrency(0),
                'net_profit' => 0,
                'net_profit_formatted' => $formatCurrency(0),
                'opening_stock' => 0,
                'opening_stock_formatted' => $formatCurrency(0),
                'closing_stock' => 0,
                'closing_stock_formatted' => $formatCurrency(0),
                'total_purchase' => 0,
                'total_purchase_formatted' => $formatCurrency(0),
                'total_expense' => 0,
                'total_expense_formatted' => $formatCurrency(0),
                'total_expense_tax' => 0,
                'total_expense_tax_formatted' => $formatCurrency(0),
                'total_operating_expenses' => 0,
                'total_operating_expenses_formatted' => $formatCurrency(0)
            ];
        }

        return view('advancedreports::profit-loss.partials.profit_loss_details', compact('data'))->render();
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.profit_loss_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');

        // Get currency settings
        $currency_symbol = session('currency')['symbol'] ?? '$';
        $currency_precision = session('business.currency_precision') ?? 2;
        $currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';

        // Format currency helper
        $formatCurrency = function($value) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
            $formatted = number_format($value, $currency_precision);
            return $currency_symbol_placement === 'after' ?
                $formatted . $currency_symbol :
                $currency_symbol . $formatted;
        };

        $permitted_locations = auth()->user()->permitted_locations();

        try {
            // Use the proven TransactionUtil method for accurate calculations
            $data = $this->transactionUtil->getProfitLossDetails(
                $business_id,
                $location_id,
                $start_date,
                $end_date,
                $user_id,
                $permitted_locations
            );

            $total_revenue = $data['total_sell'] ?? 0;
            $gross_profit = $data['gross_profit'] ?? 0;
            $net_profit = $data['net_profit'] ?? 0;
            $total_cost = $data['total_purchase'] ?? 0;
            $opening_stock = $data['opening_stock'] ?? 0;
            $closing_stock = $data['closing_stock'] ?? 0;
            $total_expense = $data['total_expense'] ?? 0;

            $gross_profit_margin = ($total_revenue > 0) ? (($gross_profit / $total_revenue) * 100) : 0;
            $net_profit_margin = ($total_revenue > 0) ? (($net_profit / $total_revenue) * 100) : 0;

            // Calculate period comparison
            $previous_period_revenue = $this->getPreviousPeriodRevenue($business_id, $start_date, $end_date, $location_id, $permitted_locations);

            $growth_rate = 0;
            $growth_rate_display = 'N/A';
            if ($previous_period_revenue && $previous_period_revenue > 0) {
                $growth_rate = (($total_revenue - $previous_period_revenue) / $previous_period_revenue) * 100;
                $growth_rate_display = number_format($growth_rate, 1) . '%';
            } elseif ($total_revenue > 0 && $previous_period_revenue == 0) {
                $growth_rate_display = '∞% (New)';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sell_inc_tax' => round($total_revenue, 2),
                    'total_sell_inc_tax_formatted' => $formatCurrency($total_revenue),
                    'gross_profit' => round($gross_profit, 2),
                    'gross_profit_formatted' => $formatCurrency($gross_profit),
                    'net_profit' => round($net_profit, 2),
                    'net_profit_formatted' => $formatCurrency($net_profit),
                    'total_cost' => round($total_cost, 2),
                    'total_cost_formatted' => $formatCurrency($total_cost)
                ],
                'metrics' => [
                    'gross_profit_margin' => round($gross_profit_margin, 2),
                    'net_profit_margin' => round($net_profit_margin, 2),
                    'growth_rate' => round($growth_rate, 2),
                    'growth_rate_display' => $growth_rate_display,
                    'return_on_sales' => round($net_profit_margin, 2)
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting profit loss summary: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'data' => [
                    'total_sell_inc_tax' => 0,
                    'total_sell_inc_tax_formatted' => $formatCurrency(0),
                    'gross_profit' => 0,
                    'gross_profit_formatted' => $formatCurrency(0),
                    'net_profit' => 0,
                    'net_profit_formatted' => $formatCurrency(0),
                    'total_cost' => 0,
                    'total_cost_formatted' => $formatCurrency(0)
                ],
                'metrics' => [
                    'gross_profit_margin' => 0,
                    'net_profit_margin' => 0,
                    'growth_rate' => 0,
                    'return_on_sales' => 0
                ]
            ]);
        }
    }

    /**
     * Get profit by different categories for enhanced analysis
     */
    public function getProfitAnalysis(Request $request)
    {
        \Log::info('getProfitAnalysis called with params: ' . json_encode($request->all()));

        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.profit_loss_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $type = $request->get('type', 'product'); // product, category, brand, location, customer, staff

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');

        $permitted_locations = auth()->user()->permitted_locations();

        \Log::info('Analysis type: ' . $type . ', Business ID: ' . $business_id);

        switch ($type) {
            case 'product':
                return $this->getProfitByProducts($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'category':
                return $this->getProfitByCategories($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'brand':
                return $this->getProfitByBrands($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'location':
                return $this->getProfitByLocations($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'customer':
                return $this->getProfitByCustomers($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'staff':
                return $this->getProfitByStaff($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            case 'invoice':
                return $this->getProfitByInvoices($business_id, $start_date, $end_date, $location_id, $permitted_locations);
            default:
                return $this->getProfitByProducts($business_id, $start_date, $end_date, $location_id, $permitted_locations);
        }
    }

    /**
     * Get profit trends data for charts
     */
    public function getProfitTrends(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.profit_loss_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $period = $request->get('period', 'daily'); // daily, weekly, monthly

        $permitted_locations = auth()->user()->permitted_locations();
        
        $trends = $this->calculateProfitTrends($business_id, $start_date, $end_date, $location_id, $period, $permitted_locations);

        return response()->json([
            'success' => true,
            'trends' => $trends
        ]);
    }

    /**
     * Export profit loss report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');
        $format = $request->get('format', 'excel'); // excel, pdf, csv

        $permitted_locations = auth()->user()->permitted_locations();
        
        // Get data
        $data = $this->transactionUtil->getProfitLossDetails($business_id, $location_id, $start_date, $end_date, $user_id, $permitted_locations);
        
        // Handle different export formats
        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($data, $start_date, $end_date);
            case 'csv':
                return $this->exportToCsv($data, $start_date, $end_date);
            default:
                return $this->exportToExcel($data, $start_date, $end_date);
        }
    }

    /**
     * Helper methods for different profit analysis types
     */
    private function getProfitByProducts($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        // Get currency settings
        $currency_symbol = session('currency')['symbol'] ?? '$';
        $currency_precision = session('business.currency_precision') ?? 2;
        $currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';

        try {
            // Use the same approach as the main application's getGrossProfit method
            $query = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('tsl.children_type', '!=', 'combo');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $products = $query->select([
                    'p.id as product_id',
                    'p.name as product',
                    DB::raw('SUM(tsl.quantity) as quantity'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(CASE
                        WHEN p.enable_stock = 0 THEN (tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax
                        WHEN tspl.id IS NOT NULL THEN (tspl.quantity - tspl.qty_returned) * (tsl.unit_price_inc_tax - pl.purchase_price_inc_tax)
                        ELSE 0
                    END) as gross_profit'),
                    DB::raw('SUM(CASE
                        WHEN p.enable_stock = 0 THEN 0
                        WHEN tspl.id IS NOT NULL THEN (tspl.quantity - tspl.qty_returned) * pl.purchase_price_inc_tax
                        ELSE 0
                    END) as cost')
                ])
                ->groupBy('p.id', 'p.name')
                ->get();

            return DataTables::of($products)
                ->addColumn('profit', function ($row) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                    // Use the pre-calculated gross_profit from SQL
                    $profit = $row->gross_profit ?? ($row->revenue - $row->cost);
                    $formatted = number_format(round($profit, 2), $currency_precision);
                    return $currency_symbol_placement === 'after' ? $formatted . $currency_symbol : $currency_symbol . $formatted;
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->gross_profit ?? ($row->revenue - $row->cost);
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-xs btn-info" onclick="viewProductDetails(' . $row->product_id . ')">View Details</button>';
                })
                ->editColumn('revenue', function ($row) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                    $formatted = number_format(round($row->revenue, 2), $currency_precision);
                    return $currency_symbol_placement === 'after' ? $formatted . $currency_symbol : $currency_symbol . $formatted;
                })
                ->editColumn('cost', function ($row) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                    $formatted = number_format(round($row->cost, 2), $currency_precision);
                    return $currency_symbol_placement === 'after' ? $formatted . $currency_symbol : $currency_symbol . $formatted;
                })
                ->editColumn('quantity', function ($row) {
                    return number_format($row->quantity, 0);
                })
                ->rawColumns(['action'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByProducts: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'draw' => request()->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to load product data: ' . $e->getMessage()
            ]);
        }
    }

    private function getProfitByCategories($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $categories = $query->select([
                    DB::raw('COALESCE(c.name, "Uncategorized") as category'),
                    DB::raw('COUNT(DISTINCT p.id) as products_count'),
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
                ])
                ->groupBy(DB::raw('COALESCE(c.id, 0)'), DB::raw('COALESCE(c.name, "Uncategorized")'));


            return DataTables::of($categories)
                ->addColumn('profit', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    return number_format(round($profit, 2), 2);
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('contribution', function ($row) {
                    return '0%'; // Placeholder
                })
                ->editColumn('revenue', function ($row) {
                    return number_format(round($row->revenue, 2), 2);
                })
                ->editColumn('cost', function ($row) {
                    return number_format(round($row->cost, 2), 2);
                })
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByCategories: ' . $e->getMessage());
            return DataTables::of([])->make(true);
        }
    }

    private function getProfitByBrands($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $brands = $query->select([
                    DB::raw('COALESCE(b.name, "No Brand") as brand'),
                    DB::raw('COUNT(DISTINCT p.id) as products_count'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
                ])
                ->groupBy('b.id', 'b.name');

            return DataTables::of($brands)
                ->addColumn('profit', function ($row) {
                    return number_format(round($row->revenue - $row->cost, 2), 2);
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('market_share', function ($row) {
                    return '0%'; // Placeholder
                })
                ->editColumn('revenue', function ($row) {
                    return number_format(round($row->revenue, 2), 2);
                })
                ->editColumn('cost', function ($row) {
                    return number_format(round($row->cost, 2), 2);
                })
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByBrands: ' . $e->getMessage());
            return DataTables::of([])->make(true);
        }
    }

    private function getProfitByLocations($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $locations = $query->select([
                    'bl.name as location',
                    DB::raw('COUNT(DISTINCT t.id) as transactions_count'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
                ])
                ->groupBy('bl.id', 'bl.name');

            return DataTables::of($locations)
                ->addColumn('profit', function ($row) {
                    return number_format(round($row->revenue - $row->cost, 2), 2);
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('performance', function ($row) {
                    return 'Good'; // Placeholder
                })
                ->editColumn('revenue', function ($row) {
                    return number_format(round($row->revenue, 2), 2);
                })
                ->editColumn('cost', function ($row) {
                    return number_format(round($row->cost, 2), 2);
                })
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByLocations: ' . $e->getMessage());
            return DataTables::of([])->make(true);
        }
    }

    private function getProfitByCustomers($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $customers = $query->select([
                    DB::raw('COALESCE(c.name, "Walk-in Customer") as customer'),
                    DB::raw('COUNT(DISTINCT t.id) as orders_count'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
                ])
                ->groupBy('c.id', 'c.name');

            return DataTables::of($customers)
                ->addColumn('profit', function ($row) {
                    return number_format(round($row->revenue - $row->cost, 2), 2);
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('avg_order_value', function ($row) {
                    $avg = $row->orders_count > 0 ? $row->revenue / $row->orders_count : 0;
                    return number_format(round($avg, 2), 2);
                })
                ->editColumn('revenue', function ($row) {
                    return number_format(round($row->revenue, 2), 2);
                })
                ->editColumn('cost', function ($row) {
                    return number_format(round($row->cost, 2), 2);
                })
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByCustomers: ' . $e->getMessage());
            return DataTables::of([])->make(true);
        }
    }

    private function getProfitByStaff($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $staff = $query->select([
                    DB::raw('COALESCE(CONCAT(u.first_name, " ", u.last_name), "System") as staff'),
                    DB::raw('COUNT(DISTINCT t.id) as sales_count'),
                    DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                    DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
                ])
                ->groupBy('u.id', 'u.first_name', 'u.last_name');

            return DataTables::of($staff)
                ->addColumn('profit', function ($row) {
                    return number_format(round($row->revenue - $row->cost, 2), 2);
                })
                ->addColumn('margin', function ($row) {
                    $profit = $row->revenue - $row->cost;
                    $margin = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0;
                    return number_format(round($margin, 2), 2) . '%';
                })
                ->addColumn('commission', function ($row) {
                    return '0.00'; // Placeholder
                })
                ->editColumn('revenue', function ($row) {
                    return number_format(round($row->revenue, 2), 2);
                })
                ->editColumn('cost', function ($row) {
                    return number_format(round($row->cost, 2), 2);
                })
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in getProfitByStaff: ' . $e->getMessage());
            return DataTables::of([])->make(true);
        }
    }

    private function applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations)
    {
        // If no date filters provided, default to last 30 days
        if (empty($start_date) && empty($end_date)) {
            $query->where('t.transaction_date', '>=', Carbon::now()->subDays(30));
        } else {
            if (!empty($start_date)) {
                $query->whereDate('t.transaction_date', '>=', $start_date);
            }
            if (!empty($end_date)) {
                $query->whereDate('t.transaction_date', '<=', $end_date);
            }
        }
        
        // Apply location filters - if specific location is selected, use that
        // Otherwise, use permitted locations if they exist and aren't "all"
        if (!empty($location_id) && $location_id !== 'all') {
            $query->where('t.location_id', $location_id);
        } elseif (!empty($permitted_locations)) {
            // Ensure $permitted_locations is an array
            if (is_string($permitted_locations)) {
                $permitted_locations = explode(',', $permitted_locations);
            }
            if (is_array($permitted_locations) && !empty($permitted_locations)) {
                // Don't apply filter if "all" is in the array
                if (!in_array('all', $permitted_locations)) {
                    $query->whereIn('t.location_id', $permitted_locations);
                }
            }
        }
    }

    private function getPreviousPeriodRevenue($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        try {
            if (empty($start_date) || empty($end_date)) {
                return 0;
            }

            $start = Carbon::parse($start_date);
            $end = Carbon::parse($end_date);
            $period_length = $start->diffInDays($end) + 1;

            // Calculate previous period dates
            $previous_end = $start->copy()->subDay();
            $previous_start = $previous_end->copy()->subDays($period_length - 1);

            $query = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereDate('t.transaction_date', '>=', $previous_start->format('Y-m-d'))
                ->whereDate('t.transaction_date', '<=', $previous_end->format('Y-m-d'));

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
            if (!empty($permitted_locations)) {
                if (is_string($permitted_locations)) {
                    $permitted_locations = explode(',', $permitted_locations);
                }
                if (is_array($permitted_locations) && !empty($permitted_locations)) {
                    $query->whereIn('t.location_id', $permitted_locations);
                }
            }

            $result = $query->selectRaw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as total_revenue')->first();
            
            return $result->total_revenue ?? 0;
        } catch (\Exception $e) {
            \Log::error('Error calculating previous period revenue: ' . $e->getMessage());
            return 0;
        }
    }

    private function getPreviousPeriodData($business_id, $start_date, $end_date, $location_id, $user_id, $permitted_locations)
    {
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);
        $diff_days = $end->diffInDays($start);
        
        $prev_end = $start->copy()->subDay();
        $prev_start = $prev_end->copy()->subDays($diff_days);
        
        return $this->transactionUtil->getProfitLossDetails(
            $business_id, 
            $location_id, 
            $prev_start->format('Y-m-d'), 
            $prev_end->format('Y-m-d'), 
            $user_id, 
            $permitted_locations
        );
    }

    private function calculateProfitTrends($business_id, $start_date, $end_date, $location_id, $period, $permitted_locations)
    {
        // Implementation for profit trends calculation
        $trends = [];
        
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);
        
        switch ($period) {
            case 'daily':
                while ($start <= $end) {
                    $data = $this->transactionUtil->getProfitLossDetails(
                        $business_id, 
                        $location_id, 
                        $start->format('Y-m-d'), 
                        $start->format('Y-m-d'), 
                        null, 
                        $permitted_locations
                    );
                    
                    $trends[] = [
                        'date' => $start->format('Y-m-d'),
                        'label' => $start->format('M d'),
                        'profit' => isset($data['gross_profit']) ? (float)$data['gross_profit'] : 0,
                        'revenue' => isset($data['total_sell_inc_tax']) ? (float)$data['total_sell_inc_tax'] : 0
                    ];
                    
                    $start->addDay();
                }
                break;
                
            case 'weekly':
                while ($start <= $end) {
                    $week_end = $start->copy()->endOfWeek();
                    if ($week_end > $end) $week_end = $end;
                    
                    $data = $this->transactionUtil->getProfitLossDetails(
                        $business_id, 
                        $location_id, 
                        $start->format('Y-m-d'), 
                        $week_end->format('Y-m-d'), 
                        null, 
                        $permitted_locations
                    );
                    
                    $trends[] = [
                        'date' => $start->format('Y-m-d'),
                        'label' => 'Week of ' . $start->format('M d'),
                        'profit' => isset($data['gross_profit']) ? (float)$data['gross_profit'] : 0,
                        'revenue' => isset($data['total_sell_inc_tax']) ? (float)$data['total_sell_inc_tax'] : 0
                    ];
                    
                    $start->addWeek();
                }
                break;
                
            case 'monthly':
                while ($start <= $end) {
                    $month_end = $start->copy()->endOfMonth();
                    if ($month_end > $end) $month_end = $end;
                    
                    $data = $this->transactionUtil->getProfitLossDetails(
                        $business_id, 
                        $location_id, 
                        $start->format('Y-m-d'), 
                        $month_end->format('Y-m-d'), 
                        null, 
                        $permitted_locations
                    );
                    
                    $trends[] = [
                        'date' => $start->format('Y-m-d'),
                        'label' => $start->format('M Y'),
                        'profit' => isset($data['gross_profit']) ? (float)$data['gross_profit'] : 0,
                        'revenue' => isset($data['total_sell_inc_tax']) ? (float)$data['total_sell_inc_tax'] : 0
                    ];
                    
                    $start->addMonth();
                }
                break;
        }
        
        return $trends;
    }

    private function exportToExcel($data, $start_date, $end_date)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Get comprehensive profit loss data
        $export_data = $this->getExportData($business_id, $start_date, $end_date, request()->get('location_id'));
        
        // Create CSV content (Excel can open CSV files)
        $filename = 'profit-loss-report-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($export_data) {
            $file = fopen('php://output', 'w');
            
            // Add summary section
            fputcsv($file, ['PROFIT & LOSS REPORT']);
            fputcsv($file, ['Period:', $export_data['period']]);
            fputcsv($file, ['Generated:', date('Y-m-d H:i:s')]);
            fputcsv($file, []); // Empty row
            
            // Summary metrics
            fputcsv($file, ['SUMMARY']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Revenue', $export_data['summary']['total_revenue']]);
            fputcsv($file, ['Total Cost', $export_data['summary']['total_cost']]);
            fputcsv($file, ['Gross Profit', $export_data['summary']['gross_profit']]);
            fputcsv($file, ['Net Profit', $export_data['summary']['net_profit']]);
            fputcsv($file, ['Gross Profit Margin', $export_data['summary']['gross_profit_margin'] . '%']);
            fputcsv($file, ['Net Profit Margin', $export_data['summary']['net_profit_margin'] . '%']);
            fputcsv($file, []); // Empty row
            
            // Product breakdown
            if (!empty($export_data['products'])) {
                fputcsv($file, ['PRODUCT BREAKDOWN']);
                fputcsv($file, ['Product', 'Quantity', 'Revenue', 'Cost', 'Profit', 'Margin %']);
                foreach ($export_data['products'] as $product) {
                    fputcsv($file, [
                        $product['product'],
                        $product['quantity'],
                        number_format($product['revenue'], 2),
                        number_format($product['cost'], 2),
                        number_format($product['profit'], 2),
                        number_format($product['margin'], 2) . '%'
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($data, $start_date, $end_date)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Get comprehensive profit loss data
        $export_data = $this->getExportData($business_id, $start_date, $end_date, request()->get('location_id'));
        
        // For now, return as HTML that can be converted to PDF
        $html = view('advancedreports::profit-loss.partials.pdf_export', compact('export_data'))->render();
        
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="profit-loss-report-' . date('Y-m-d') . '.html"',
        ];
        
        return response($html, 200, $headers);
    }

    private function exportToCsv($data, $start_date, $end_date)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Get comprehensive profit loss data
        $export_data = $this->getExportData($business_id, $start_date, $end_date, request()->get('location_id'));
        
        $filename = 'profit-loss-report-' . date('Y-m-d') . '.csv';
        
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
            fputcsv($file, ['Category', 'Product', 'Quantity', 'Revenue', 'Cost', 'Profit', 'Margin %']);
            
            // Add data
            foreach ($export_data['products'] as $product) {
                fputcsv($file, [
                    $product['category'] ?? 'Uncategorized',
                    $product['product'],
                    $product['quantity'],
                    number_format($product['revenue'], 2),
                    number_format($product['cost'], 2),
                    number_format($product['profit'], 2),
                    number_format($product['margin'], 2)
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getExportData($business_id, $start_date, $end_date, $location_id)
    {
        $permitted_locations = auth()->user()->permitted_locations();
        
        // Get summary data
        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

        $summary = $query->selectRaw('
            SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as total_revenue,
            SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as total_cost,
            COUNT(DISTINCT t.id) as transaction_count
        ')->first();

        $total_revenue = $summary->total_revenue ?? 0;
        $total_cost = $summary->total_cost ?? 0;
        $gross_profit = $total_revenue - $total_cost;
        $net_profit = $gross_profit; // Simplified
        
        $gross_profit_margin = ($total_revenue > 0) ? (($gross_profit / $total_revenue) * 100) : 0;
        $net_profit_margin = ($total_revenue > 0) ? (($net_profit / $total_revenue) * 100) : 0;
        
        // Get product breakdown
        $products_query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        $this->applyDateLocationFilters($products_query, $start_date, $end_date, $location_id, $permitted_locations);

        $products = $products_query->select([
                'p.name as product',
                DB::raw('COALESCE(c.name, "Uncategorized") as category'),
                DB::raw('SUM(tsl.quantity) as quantity'),
                DB::raw('SUM((tsl.unit_price_inc_tax * tsl.quantity) - COALESCE(tsl.line_discount_amount, 0)) as revenue'),
                DB::raw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as cost')
            ])
            ->groupBy('p.id', 'p.name', 'c.name')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(function($product) {
                $profit = $product->revenue - $product->cost;
                $margin = $product->revenue > 0 ? ($profit / $product->revenue) * 100 : 0;
                
                return [
                    'product' => $product->product,
                    'category' => $product->category,
                    'quantity' => $product->quantity,
                    'revenue' => $product->revenue,
                    'cost' => $product->cost,
                    'profit' => $profit,
                    'margin' => $margin
                ];
            })->toArray();
        
        return [
            'period' => ($start_date && $end_date) ? "$start_date to $end_date" : "All Time",
            'summary' => [
                'total_revenue' => number_format($total_revenue, 2),
                'total_cost' => number_format($total_cost, 2),
                'gross_profit' => number_format($gross_profit, 2),
                'net_profit' => number_format($net_profit, 2),
                'gross_profit_margin' => round($gross_profit_margin, 2),
                'net_profit_margin' => round($net_profit_margin, 2),
                'transaction_count' => $summary->transaction_count ?? 0
            ],
            'products' => $products
        ];
    }

    /**
     * Get profit breakdown by invoices (transactions)
     */
    private function getProfitByInvoices($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        // Get currency settings
        $currency_symbol = session('currency')['symbol'] ?? '$';
        $currency_precision = session('business.currency_precision') ?? 2;
        $currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';

        try {
            $query = DB::table('transactions as t')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');

            $this->applyDateLocationFilters($query, $start_date, $end_date, $location_id, $permitted_locations);

            $invoices = $query->select([
                    't.id',
                    't.invoice_no',
                    't.transaction_date',
                    DB::raw('COALESCE(c.name, "Walk-in Customer") as customer_name'),
                    'bl.name as location_name',
                    't.final_total as revenue',
                    't.payment_status'
                ])
                ->orderBy('t.transaction_date', 'desc')
                ->get()
                ->map(function($invoice) use ($business_id, $currency_symbol, $currency_precision, $currency_symbol_placement) {
                    // Calculate cost for this invoice
                    $cost_query = DB::table('transaction_sell_lines as tsl')
                        ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                        ->where('tsl.transaction_id', $invoice->id)
                        ->selectRaw('SUM(COALESCE(v.default_purchase_price, tsl.unit_price_inc_tax * 0.7) * tsl.quantity) as total_cost')
                        ->first();

                    $cost = $cost_query->total_cost ?? ($invoice->revenue * 0.7); // Fallback to 70% cost
                    $profit = $invoice->revenue - $cost;
                    $margin = $invoice->revenue > 0 ? ($profit / $invoice->revenue) * 100 : 0;

                    // Format payment status
                    $payment_status_display = ucfirst(str_replace('_', ' ', $invoice->payment_status));

                    // Currency formatter
                    $formatCurrency = function($value) use ($currency_symbol, $currency_precision, $currency_symbol_placement) {
                        $formatted = number_format($value, $currency_precision);
                        return $currency_symbol_placement === 'after' ? $formatted . $currency_symbol : $currency_symbol . $formatted;
                    };

                    return [
                        'invoice_no' => $invoice->invoice_no ?: '#' . $invoice->id,
                        'transaction_date' => date('Y-m-d', strtotime($invoice->transaction_date)),
                        'customer_name' => $invoice->customer_name,
                        'location_name' => $invoice->location_name ?: 'Main Location',
                        'revenue' => $formatCurrency($invoice->revenue),
                        'cost' => $formatCurrency($cost),
                        'profit' => $formatCurrency($profit),
                        'margin' => number_format($margin, 2) . '%',
                        'payment_status' => $payment_status_display,
                        'action' => '<a href="' . url('/pos/' . $invoice->id) . '" class="btn btn-xs btn-info" target="_blank"><i class="fa fa-eye"></i> View</a>'
                    ];
                });

            return response()->json([
                'draw' => request()->get('draw'),
                'recordsTotal' => $invoices->count(),
                'recordsFiltered' => $invoices->count(),
                'data' => $invoices->values()->all()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting profit by invoices: ' . $e->getMessage());
            return response()->json([
                'draw' => request()->get('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }
    }
}