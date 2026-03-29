<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use App\Product;
use App\Variation;
use App\VariationLocationDetails;
use App\BusinessLocation;
use App\Category;
use App\Transaction;
use App\TransactionSellLine;
use App\PurchaseLine;
use App\Unit;
use App\Utils\ProductUtil;
use DB;
use Excel;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class StockReportController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Display stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.stock_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get business locations - Safe array handling
        try {
            $business_locations = BusinessLocation::forDropdown($business_id, false, false);

            // Convert to Collection if it's an array, then prepend
            if (is_array($business_locations)) {
                $business_locations = collect($business_locations);
            }

            // Add "All Locations" option at the beginning
            $business_locations = $business_locations->prepend(__('advancedreports::lang.all_locations'), '');
        } catch (\Exception $e) {
            \Log::error('AdvancedReports: Error getting business locations: ' . $e->getMessage());
            $business_locations = collect(['' => __('advancedreports::lang.all_locations')]);
        }

        // Get categories - Safe array handling
        try {
            $categories = Category::forDropdown($business_id, 'product');

            // Convert to Collection if it's an array
            if (is_array($categories)) {
                $categories = collect($categories);
            }

            // Ensure we have a Collection for the view
            if (!$categories instanceof \Illuminate\Support\Collection) {
                $categories = collect($categories);
            }
        } catch (\Exception $e) {
            \Log::error('AdvancedReports: Error getting categories: ' . $e->getMessage());
            $categories = collect();
        }

        // Get all locations for stock alert dropdown
        $all_locations = BusinessLocation::forDropdown($business_id, true);

        return view('advancedreports::stock.index')
            ->with(compact('business_locations', 'categories', 'all_locations'));
    }

    /**
     * Get stock report data for DataTables - Matches your Excel structure
     */
    public function getStockData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.stock_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $location_id = $request->get('location_id');
        $category_id = $request->get('category_id');
        $show_zero_stock = $request->get('show_zero_stock', false);
        $exp_date_filter = $request->get('exp_date_filter');
        $stock_need_only = $request->get('stock_need_only', false);

        try {
            $query = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftJoin('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->leftJoin('units', 'products.unit_id', '=', 'units.id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            // Apply filters
            if (!empty($location_id)) {
                $query->where('vld.location_id', $location_id);
            }

            if (!empty($category_id)) {
                $query->where('products.category_id', $category_id);
            }

            // Hide zero stock if requested - use calculated stock instead of qty_available
            if (!$show_zero_stock) {
                $query->havingRaw('current_stock > 0');
            }

            // Stock need filter - show only products that need restocking
            if ($stock_need_only) {
                $query->whereNotNull('products.alert_quantity')
                    ->whereRaw('vld.qty_available <= products.alert_quantity');
            }

            // Expiry date filter
            if (!empty($exp_date_filter)) {
                $query->leftJoin('purchase_lines as pl', function ($join) {
                    $join->on('variations.id', '=', 'pl.variation_id')
                        ->whereRaw('pl.quantity > pl.quantity_sold + COALESCE(pl.quantity_adjusted, 0) + COALESCE(pl.quantity_returned, 0)');
                })
                    ->whereDate('pl.exp_date', '<=', $exp_date_filter);
            }

            $query->select([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'products.alert_quantity',
                'categories.name as category_name',
                'bl.name as location_name',
                'variations.id as variation_id',
                'variations.name as variation_name',
                'variations.sub_sku',
                'variations.default_purchase_price',
                'variations.default_sell_price',
                'units.short_name as unit_name',
                DB::raw('COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) as current_stock'),
                DB::raw('COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_purchase_price, 0) as stock_value_purchase'),
                DB::raw('COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_sell_price, 0) as stock_value_sale'),
                DB::raw('(COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_sell_price, 0)) - (COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_purchase_price, 0)) as potential_profit'),
                // Get total sold quantity
                DB::raw('(SELECT COALESCE(SUM(tsl.quantity), 0) 
                         FROM transaction_sell_lines tsl 
                         LEFT JOIN transactions t ON tsl.transaction_id = t.id 
                         WHERE tsl.variation_id = variations.id 
                         AND t.business_id = ' . $business_id . '
                         AND t.status = "final"
                         AND t.type = "sell") as total_sold')
            ]);

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info btn-xs btn-modal" 
                        data-href="' . action([\Modules\AdvancedReports\Http\Controllers\StockReportController::class, 'show'], [$row->product_id]) . '"
                        data-container=".stock_modal">
                        <i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '
                    </button>
                </div>';
                    return $html;
                })
                ->editColumn('sku', function ($row) {
                    return $row->sub_sku ?: $row->sku;
                })
                ->editColumn('product_name', function ($row) {
                    $name = $row->product_name;
                    if ($row->variation_name && $row->variation_name != 'DUMMY') {
                        $name .= ' - ' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('current_stock', function ($row) {
                    $stock_class = '';
                    if ($row->current_stock <= 0) {
                        $stock_class = 'text-danger';
                    } elseif ($row->alert_quantity && $row->current_stock <= $row->alert_quantity) {
                        $stock_class = 'text-warning';
                    }
                    return '<span class="' . $stock_class . '">' . number_format($row->current_stock, 2) . ' ' . ($row->unit_name ?: 'units') . '</span>';
                })
                ->editColumn('stock_value_purchase', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->stock_value_purchase, 2) . '</span>';
                })
                ->editColumn('stock_value_sale', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->stock_value_sale, 2) . '</span>';
                })
                ->editColumn('potential_profit', function ($row) {
                    $class = $row->potential_profit >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="display_currency ' . $class . '" data-currency_symbol="true">' . number_format($row->potential_profit, 2) . '</span>';
                })
                ->editColumn('total_sold', function ($row) {
                    return '<span class="badge bg-blue">' . number_format($row->total_sold, 2) . ' ' . ($row->unit_name ?: 'units') . '</span>';
                })
                ->editColumn('default_sell_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->default_sell_price, 2) . '</span>';
                })
                ->editColumn('default_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->default_purchase_price, 2) . '</span>';
                })
                ->filterColumn('product_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('products.name', 'like', "%{$keyword}%")
                            ->orWhere('variations.name', 'like', "%{$keyword}%")
                            ->orWhere('products.sku', 'like', "%{$keyword}%")
                            ->orWhere('variations.sub_sku', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'current_stock', 'default_sell_price', 'default_purchase_price', 'stock_value_purchase', 'stock_value_sale', 'potential_profit', 'total_sold'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Stock Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading stock data'], 500);
        }
    }

    /**
     * Get enhanced stock summary data - For dashboard cards
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.stock_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $location_id = $request->get('location_id');
            $category_id = $request->get('category_id');

            $query = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            if (!empty($location_id)) {
                $query->where('vld.location_id', $location_id);
            }

            if (!empty($category_id)) {
                $query->where('products.category_id', $category_id);
            }

            $summary = $query->select([
                DB::raw('COUNT(DISTINCT products.id) as total_products'),
                DB::raw('SUM(COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0)) as total_stock_qty'),
                DB::raw('SUM(COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_purchase_price, 0)) as total_stock_value_purchase'),
                DB::raw('SUM(COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_sell_price, 0)) as total_stock_value_sale'),
                DB::raw('SUM((COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_sell_price, 0)) - (COALESCE((
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "opening_stock"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "purchase"
                             AND t.status = "received"), 0) +
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell_return"
                             AND t.status = "received"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM transaction_sell_lines tsl
                             JOIN transactions t ON t.id = tsl.transaction_id
                             WHERE tsl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "sell"
                             AND t.status = "final"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_adjustment_lines sal
                             JOIN transactions t ON t.id = sal.transaction_id
                             WHERE sal.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND t.type = "stock_adjustment"), 0) -
                    COALESCE((SELECT SUM(quantity_returned) FROM purchase_lines pl
                             JOIN transactions t ON t.id = pl.transaction_id
                             WHERE pl.variation_id = variations.id
                             AND t.location_id = vld.location_id
                             AND t.business_id = ' . $business_id . '
                             AND (t.type = "purchase" OR t.type = "purchase_return")), 0)
                ), 0) * COALESCE(variations.default_purchase_price, 0))) as total_potential_profit'),
            ])->first();

            // Get expired stock count
            $expiredStockQuery = PurchaseLine::leftJoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->leftJoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
                ->where('t.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->whereNotNull('purchase_lines.exp_date')
                ->whereDate('purchase_lines.exp_date', '<', Carbon::now())
                ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)');

            if (!empty($location_id)) {
                $expiredStockQuery->where('t.location_id', $location_id);
            }

            if (!empty($category_id)) {
                $expiredStockQuery->where('p.category_id', $category_id);
            }

            $expired_stock = $expiredStockQuery->count();

            // Add expired stock to summary
            $summary->expired_stock = $expired_stock;

            return response()->json($summary);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Stock Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    /**
     * Get product stock alert data (with stock need functionality)
     */
    public function getProductStockAlert()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $permitted_locations = auth()->user()->permitted_locations();

            $products = $this->getProductAlert($business_id, $permitted_locations);

            return Datatables::of($products)
                ->editColumn('product', function ($row) {
                    if ($row->type == 'single') {
                        return $row->product . ' (' . $row->sku . ')';
                    } else {
                        return $row->product . ' - ' . $row->product_variation . ' - ' . $row->variation . ' (' . $row->sub_sku . ')';
                    }
                })
                ->editColumn('location', function ($row) {
                    return $row->location ?? __('advancedreports::lang.all_locations');
                })
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $stock . '</span> ' . $row->unit;
                })
                ->editColumn('alert_quantity', function ($row) {
                    $alert_qty = $row->alert_quantity ?? 0;
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $alert_qty . '</span> ' . $row->unit;
                })
                ->addColumn('expected_stock', function ($row) {
                    $current_stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $current_stock . '</span> ' . $row->unit;
                })
                ->addColumn('stock_need', function ($row) {
                    $current_stock = $row->stock ? $row->stock : 0;
                    $alert_quantity = $row->alert_quantity ?? 0;
                    $stock_need = max(0, $alert_quantity - $current_stock);

                    $class = $stock_need > 0 ? 'text-danger font-weight-bold' : 'text-success';
                    return '<span class="' . $class . '" data-is_quantity="true" data-currency_symbol=false>' . (float) $stock_need . '</span> ' . $row->unit;
                })
                ->removeColumn('product_id')
                ->removeColumn('type')
                ->removeColumn('sku')
                ->removeColumn('product_variation')
                ->removeColumn('variation')
                ->removeColumn('sub_sku')
                ->removeColumn('unit')
                ->rawColumns([2, 3, 4, 5])
                ->make(false);
        }
    }

    /**
     * Get enhanced stock expiry alert data (matches screenshot format)
     */
    public function getStockExpiryAlert(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->leftjoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
                ->leftjoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->whereNotNull('purchase_lines.exp_date')
                ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            // Dynamic expiry days filter
            $expiry_days = $request->input('expiry_days', session('business.stock_expiry_alert_days', 30));
            $expiry_date = \Carbon::now()->addDays($expiry_days)->format('Y-m-d');
            $query->whereDate('purchase_lines.exp_date', '<=', $expiry_date);

            // Location filter
            if (!empty($request->input('location_id'))) {
                $query->where('t.location_id', $request->input('location_id'));
            }

            $report = $query->select(
                'p.name as product',
                'p.sku',
                'p.type as product_type',
                'v.name as variation',
                'v.sub_sku',
                'pv.name as product_variation',
                'l.name as location',
                'purchase_lines.mfg_date',
                'purchase_lines.exp_date',
                'purchase_lines.lot_number',
                'u.short_name as unit',
                DB::raw('COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0) as stock_left'),
                DB::raw('DATEDIFF(purchase_lines.exp_date, NOW()) as days_to_expire')
            )
                ->having('stock_left', '>', 0)
                ->orderBy('days_to_expire', 'asc');

            return DataTables::of($report)
                ->editColumn('product', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' . $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('sku', function ($row) {
                    return $row->sub_sku ?: $row->sku;
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        return $this->productUtil->format_date($row->exp_date);
                    } else {
                        return '--';
                    }
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency stock_left" data-currency_symbol=false data-orig-value="' . $row->stock_left . '" data-unit="' . $row->unit . '" >' . number_format($row->stock_left, 2) . '</span> ' . $row->unit;
                })
                ->addColumn('days_left', function ($row) {
                    $days = $row->days_to_expire;
                    if ($days < 0) {
                        return '<span class="label label-danger"><strong>' . abs($days) . '</strong> ' . __('advancedreports::lang.days_overdue') . '</span>';
                    } elseif ($days == 0) {
                        return '<span class="label label-danger"><strong>' . __('advancedreports::lang.expires_today') . '</strong></span>';
                    } elseif ($days <= 3) {
                        return '<span class="label label-danger"><strong>' . $days . '</strong> ' . __('advancedreports::lang.days_left') . '</span>';
                    } elseif ($days <= 7) {
                        return '<span class="label label-warning"><strong>' . $days . '</strong> ' . __('advancedreports::lang.days_left') . '</span>';
                    } else {
                        return '<span class="label label-success"><strong>' . $days . '</strong> ' . __('advancedreports::lang.days_left') . '</span>';
                    }
                })
                ->addColumn('status', function ($row) {
                    $days = $row->days_to_expire;
                    if ($days < 0) {
                        return '<span class="label label-danger"><i class="fa fa-times-circle"></i> ' . __('advancedreports::lang.expired') . '</span>';
                    } elseif ($days == 0) {
                        return '<span class="label label-danger"><i class="fa fa-exclamation-circle"></i> ' . __('advancedreports::lang.expires_today') . '</span>';
                    } elseif ($days <= 3) {
                        return '<span class="label label-danger"><i class="fa fa-exclamation-triangle"></i> ' . __('advancedreports::lang.critical') . '</span>';
                    } elseif ($days <= 7) {
                        return '<span class="label label-warning"><i class="fa fa-clock-o"></i> ' . __('advancedreports::lang.warning') . '</span>';
                    } else {
                        return '<span class="label label-success"><i class="fa fa-check-circle"></i> ' . __('advancedreports::lang.good') . '</span>';
                    }
                })
                ->rawColumns(['stock_left', 'days_left', 'status'])
                ->make(true);
        }
    }

    /**
     * Get expiry alert summary for dashboard cards
     */
    public function getExpiryAlertSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $expiry_days = $request->get('expiry_days', session('business.stock_expiry_alert_days', 30));

            $baseQuery = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->whereNotNull('purchase_lines.exp_date')
                ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)')
                ->whereDate('purchase_lines.exp_date', '<=', \Carbon::now()->addDays($expiry_days));

            if (!empty($location_id)) {
                $baseQuery->where('t.location_id', $location_id);
            }

            $now = \Carbon::now();

            // Count expired products
            $expired_count = $baseQuery->clone()
                ->whereDate('purchase_lines.exp_date', '<', $now)
                ->count();

            // Count products expiring soon (within 7 days)
            $expiring_soon_count = $baseQuery->clone()
                ->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(7)])
                ->count();

            // Total items in alert
            $total_items = $baseQuery->clone()->count();

            // Calculate total value at risk
            $total_value = $baseQuery->clone()
                ->select(DB::raw('SUM((COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(purchase_lines.purchase_price_inc_tax, 0)) as total_value'))
                ->first()
                ->total_value ?? 0;

            return response()->json([
                'expired_count' => $expired_count,
                'expiring_soon_count' => $expiring_soon_count,
                'total_items' => $total_items,
                'total_value' => $total_value
            ]);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Expiry Alert Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

/**
 * Show detailed stock information
 */
public function show($id)
{
    if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.stock_report')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    try {
        // Get the product with proper relationships
        $product = Product::with([
            'category',
            'unit',
            'variations.product_variation'
        ])->findOrFail($id);

        // Get the base unit name
        $unit_name = $product->unit->short_name ?? 'units';
        
        // Prepare stock data with manual joins to avoid relationship issues
        $stockData = [];
        
        foreach ($product->variations as $variation) {
            // Use variation's unit if available, otherwise product's unit
            $variation_unit = $product->unit->short_name ?? $unit_name;
            
            $variationData = [
                'variation' => $variation,
                'locations' => []
            ];

            // Get location details with business location info using manual query
            $locationDetails = DB::table('variation_location_details as vld')
                ->leftJoin('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->where('vld.variation_id', $variation->id)
                ->where('bl.business_id', $business_id)
                ->select([
                    'vld.*',
                    'bl.name as location_name',
                    'bl.id as location_id'
                ])
                ->get();

            foreach ($locationDetails as $locationDetail) {
                $current_stock = $locationDetail->qty_available ?? 0;

                // Get prices - modify based on your actual price storage
                $purchase_price = $variation->default_purchase_price ??
                    $variation->purchase_price ??
                    $product->purchase_price ?? 0;

                $selling_price = $variation->default_sell_price ??
                    $variation->sell_price ??
                    $product->sell_price ?? 0;

                // Create a business location object
                $businessLocation = (object) [
                    'id' => $locationDetail->location_id,
                    'name' => $locationDetail->location_name
                ];

                $variationData['locations'][] = [
                    'business_location' => $businessLocation,
                    'current_stock' => $current_stock,
                    'purchase_price' => $purchase_price,
                    'selling_price' => $selling_price,
                    'unit_name' => $variation_unit
                ];
            }
            
            $stockData[] = $variationData;
        }

        return view('advancedreports::stock.show', compact('product', 'stockData', 'unit_name'));
        
    } catch (\Exception $e) {
        \Log::error('Stock Report Error: ' . $e->getMessage());
        return response()->json(['error' => 'Error generating report'], 500);
    }
}

    /**
     * Optimized export method - Replace in StockReportController.php
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        // Aggressive optimization settings
        ini_set('max_execution_time', 600); // 10 minutes
        ini_set('memory_limit', '2G');
        set_time_limit(600);

        try {
            $business_id = request()->session()->get('user.business_id');

            $filters = [
                'location_id' => $request->get('location_id'),
                'category_id' => $request->get('category_id'),
                'show_zero_stock' => $request->get('show_zero_stock', false),
                'business_id' => $business_id
            ];

            \Log::info('AdvancedReports Export: Starting optimized export', $filters);

            // Always use CSV for better performance and reliability
            $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.csv';

            return $this->exportOptimizedCSV($filters, $filename);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Stock Export Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage(),
                'suggestion' => 'Try filtering by location or category to reduce data size'
            ], 500);
        }
    }

    /**
     * Super optimized CSV export with minimal memory usage
     */
    private function exportOptimizedCSV($filters, $filename)
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

            // Add BOM for proper UTF-8 handling in Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add CSV headers
            fputcsv($file, [
                'SKU',
                'Product Name',
                'Variation',
                'Category',
                'Location',
                'Unit',
                'Current Stock',
                'Purchase Price',
                'Selling Price',
                'Stock Value (Purchase)',
                'Stock Value (Sale)',
                'Potential Profit'
            ]);

            $business_id = $filters['business_id'];
            $location_id = $filters['location_id'];
            $category_id = $filters['category_id'];
            $show_zero_stock = $filters['show_zero_stock'];

            // Build optimized query with minimal joins
            $baseQuery = "
            SELECT 
                COALESCE(v.sub_sku, p.sku) as sku,
                p.name as product_name,
                CASE WHEN v.name != 'DUMMY' THEN v.name ELSE NULL END as variation_name,
                c.name as category_name,
                bl.name as location_name,
                u.short_name as unit_name,
                COALESCE(vld.qty_available, 0) as current_stock,
                COALESCE(v.default_purchase_price, 0) as purchase_price,
                COALESCE(v.default_sell_price, 0) as selling_price,
                COALESCE(vld.qty_available, 0) * COALESCE(v.default_purchase_price, 0) as stock_value_purchase,
                COALESCE(vld.qty_available, 0) * COALESCE(v.default_sell_price, 0) as stock_value_sale,
                (COALESCE(vld.qty_available, 0) * COALESCE(v.default_sell_price, 0)) - (COALESCE(vld.qty_available, 0) * COALESCE(v.default_purchase_price, 0)) as potential_profit
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN variations v ON p.id = v.product_id  
            LEFT JOIN variation_location_details vld ON v.id = vld.variation_id
            LEFT JOIN business_locations bl ON vld.location_id = bl.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.business_id = ? 
            AND p.type != 'modifier'
        ";

            $params = [$business_id];

            // Add filters
            if (!empty($location_id)) {
                $baseQuery .= " AND vld.location_id = ?";
                $params[] = $location_id;
            }

            if (!empty($category_id)) {
                $baseQuery .= " AND p.category_id = ?";
                $params[] = $category_id;
            }

            if (!$show_zero_stock) {
                $baseQuery .= " AND vld.qty_available > 0";
            }

            $baseQuery .= " ORDER BY p.name LIMIT 0, 100";

            $offset = 0;
            $limit = 100; // Process 100 records at a time
            $hasMoreData = true;

            while ($hasMoreData) {
                // Update offset in query
                $query = str_replace("LIMIT 0, 100", "LIMIT $offset, $limit", $baseQuery);

                $results = \DB::select($query, $params);
                $resultCount = count($results);

                foreach ($results as $row) {
                    fputcsv($file, [
                        $row->sku ?: '-',
                        $row->product_name ?: '-',
                        $row->variation_name ?: '-',
                        $row->category_name ?: '-',
                        $row->location_name ?: '-',
                        $row->unit_name ?: 'units',
                        number_format((float)$row->current_stock, 2),
                        number_format((float)$row->purchase_price, 2),
                        number_format((float)$row->selling_price, 2),
                        number_format((float)$row->stock_value_purchase, 2),
                        number_format((float)$row->stock_value_sale, 2),
                        number_format((float)$row->potential_profit, 2),
                    ]);
                }

                $offset += $limit;

                // Check if we have more data
                $hasMoreData = ($resultCount == $limit);

                // Free memory
                unset($results);

                // Flush output buffer
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get comprehensive stock expiry report data
     */
    public function getStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->leftjoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
                ->leftjoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                ->leftjoin('categories as c', 'p.category_id', '=', 'c.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->whereNotNull('purchase_lines.exp_date')
                ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            // Apply filters
            if (!empty($request->input('location_id'))) {
                $query->where('t.location_id', $request->input('location_id'));
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }

            // Expiry status filter
            if (!empty($request->input('expiry_status'))) {
                $status = $request->input('expiry_status');
                $now = Carbon::now();

                switch ($status) {
                    case 'expired':
                        $query->whereDate('purchase_lines.exp_date', '<', $now);
                        break;
                    case 'expiring_7_days':
                        $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(7)]);
                        break;
                    case 'expiring_30_days':
                        $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(30)]);
                        break;
                    case 'expiring_90_days':
                        $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(90)]);
                        break;
                }
            }

            $report = $query->select(
                'purchase_lines.id as purchase_line_id',
                'p.sku',
                'p.name as product',
                'p.type as product_type',
                'v.name as variation',
                'v.sub_sku',
                'pv.name as product_variation',
                'c.name as category',
                'l.name as location',
                'purchase_lines.lot_number',
                'purchase_lines.mfg_date',
                'purchase_lines.exp_date',
                'purchase_lines.purchase_price_inc_tax as purchase_price',
                'u.short_name as unit',
                't.ref_no',
                't.id as transaction_id',
                DB::raw('COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0) as stock_left'),
                DB::raw('DATEDIFF(purchase_lines.exp_date, NOW()) as days_to_expire'),
                DB::raw('(COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(purchase_lines.purchase_price_inc_tax, 0) as stock_value')
            )
                ->having('stock_left', '>', 0)
                ->orderBy('days_to_expire', 'asc');

            return DataTables::of($report)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info btn-xs stock_expiry_edit_btn" 
                            data-transaction_id="' . $row->transaction_id . '" 
                            data-purchase_line_id="' . $row->purchase_line_id . '">
                            <i class="fa fa-edit"></i> ' . __('messages.edit') . '
                        </button>';

                    if ($row->days_to_expire < 0) {
                        $html .= ' <button type="button" class="btn btn-warning btn-xs remove_from_stock_btn" 
                            data-href="' . action([\App\Http\Controllers\StockAdjustmentController::class, 'removeExpiredStock'], [$row->purchase_line_id]) . '">
                            <i class="fa fa-trash"></i> ' . __('lang_v1.remove_from_stock') . '
                        </button>';
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->editColumn('sku', function ($row) {
                    return $row->sub_sku ?: $row->sku;
                })
                ->editColumn('product', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' . $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        return $this->productUtil->format_date($row->exp_date);
                    } else {
                        return '--';
                    }
                })
                ->editColumn('days_to_expire', function ($row) {
                    $days = $row->days_to_expire;
                    if ($days < 0) {
                        return '<span class="label label-danger">' . abs($days) . ' ' . __('lang_v1.days_expired') . '</span>';
                    } elseif ($days <= 7) {
                        return '<span class="label label-warning">' . $days . ' ' . __('lang_v1.days_left') . '</span>';
                    } else {
                        return '<span class="label label-success">' . $days . ' ' . __('lang_v1.days_left') . '</span>';
                    }
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . number_format($row->stock_left, 2) . '</span> ' . $row->unit;
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->purchase_price, 2) . '</span>';
                })
                ->editColumn('stock_value', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . number_format($row->stock_value, 2) . '</span>';
                })
                ->addColumn('expiry_status', function ($row) {
                    $days = $row->days_to_expire;
                    if ($days < 0) {
                        return '<span class="label label-danger"><i class="fa fa-times"></i> ' . __('lang_v1.expired') . '</span>';
                    } elseif ($days <= 7) {
                        return '<span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> ' . __('lang_v1.critical') . '</span>';
                    } elseif ($days <= 30) {
                        return '<span class="label label-info"><i class="fa fa-clock-o"></i> ' . __('lang_v1.warning') . '</span>';
                    } else {
                        return '<span class="label label-success"><i class="fa fa-check"></i> ' . __('lang_v1.good') . '</span>';
                    }
                })
                ->rawColumns(['action', 'days_to_expire', 'stock_left', 'purchase_price', 'stock_value', 'expiry_status'])
                ->make(true);
        }
    }

    /**
     * Get expiry summary statistics
     */
    public function getExpirySummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $category_id = $request->get('category_id');

            $baseQuery = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->whereNotNull('purchase_lines.exp_date')
                ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)');

            if (!empty($location_id)) {
                $baseQuery->where('t.location_id', $location_id);
            }

            if (!empty($category_id)) {
                $baseQuery->where('p.category_id', $category_id);
            }

            $now = Carbon::now();

            // Count expired products
            $expired_count = $baseQuery->clone()
                ->whereDate('purchase_lines.exp_date', '<', $now)
                ->count();

            // Count products expiring in 7 days
            $expiring_7_days = $baseQuery->clone()
                ->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(7)])
                ->count();

            // Count products expiring in 30 days
            $expiring_30_days = $baseQuery->clone()
                ->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(30)])
                ->count();

            // Calculate total value of expiring/expired stock
            $total_expiry_value = $baseQuery->clone()
                ->whereDate('purchase_lines.exp_date', '<=', $now->copy()->addDays(30))
                ->select(DB::raw('SUM((COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(purchase_lines.purchase_price_inc_tax, 0)) as total_value'))
                ->first()
                ->total_value ?? 0;

            return response()->json([
                'expired_count' => $expired_count,
                'expiring_7_days' => $expiring_7_days,
                'expiring_30_days' => $expiring_30_days,
                'total_expiry_value' => $total_expiry_value
            ]);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Expiry Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    /**
     * Export expiry report
     */
    public function exportExpiryReport(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $category_id = $request->get('category_id');
            $expiry_status = $request->get('expiry_status');

            $filename = 'stock_expiry_report_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($business_id, $location_id, $category_id, $expiry_status) {
                $file = fopen('php://output', 'w');

                // Add BOM for proper UTF-8 handling in Excel
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Add CSV headers
                fputcsv($file, [
                    'SKU',
                    'Product Name',
                    'Category',
                    'Location',
                    'Lot Number',
                    'Manufacturing Date',
                    'Expiry Date',
                    'Days to Expire',
                    'Current Stock',
                    'Purchase Price',
                    'Stock Value',
                    'Expiry Status'
                ]);

                // Build query
                $query = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                    ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                    ->leftjoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
                    ->leftjoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                    ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                    ->leftjoin('categories as c', 'p.category_id', '=', 'c.id')
                    ->where('t.business_id', $business_id)
                    ->where('p.enable_stock', 1)
                    ->whereNotNull('purchase_lines.exp_date')
                    ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)');

                if (!empty($location_id)) {
                    $query->where('t.location_id', $location_id);
                }

                if (!empty($category_id)) {
                    $query->where('p.category_id', $category_id);
                }

                // Apply expiry status filter
                if (!empty($expiry_status)) {
                    $now = Carbon::now();
                    switch ($expiry_status) {
                        case 'expired':
                            $query->whereDate('purchase_lines.exp_date', '<', $now);
                            break;
                        case 'expiring_7_days':
                            $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(7)]);
                            break;
                        case 'expiring_30_days':
                            $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(30)]);
                            break;
                        case 'expiring_90_days':
                            $query->whereBetween('purchase_lines.exp_date', [$now, $now->copy()->addDays(90)]);
                            break;
                    }
                }

                $query->select(
                    DB::raw('COALESCE(v.sub_sku, p.sku) as sku'),
                    DB::raw('CASE WHEN p.type = "variable" THEN CONCAT(p.name, " - ", pv.name, " - ", v.name) ELSE p.name END as product_name'),
                    'c.name as category_name',
                    'l.name as location_name',
                    'purchase_lines.lot_number',
                    'purchase_lines.mfg_date',
                    'purchase_lines.exp_date',
                    DB::raw('DATEDIFF(purchase_lines.exp_date, NOW()) as days_to_expire'),
                    DB::raw('COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0) as stock_left'),
                    'purchase_lines.purchase_price_inc_tax as purchase_price',
                    DB::raw('(COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(purchase_lines.purchase_price_inc_tax, 0) as stock_value')
                )
                    ->havingRaw('stock_left > 0')
                    ->orderBy('days_to_expire')
                    ->chunk(100, function ($results) use ($file) {
                        foreach ($results as $row) {
                            $days = $row->days_to_expire;
                            $status = 'Good';
                            if ($days < 0) {
                                $status = 'Expired';
                            } elseif ($days <= 7) {
                                $status = 'Critical';
                            } elseif ($days <= 30) {
                                $status = 'Warning';
                            }

                            fputcsv($file, [
                                $row->sku ?: '-',
                                $row->product_name ?: '-',
                                $row->category_name ?: '-',
                                $row->location_name ?: '-',
                                $row->lot_number ?: '-',
                                $row->mfg_date ? date('Y-m-d', strtotime($row->mfg_date)) : '-',
                                $row->exp_date ? date('Y-m-d', strtotime($row->exp_date)) : '-',
                                $days,
                                number_format($row->stock_left, 2),
                                number_format($row->purchase_price, 2),
                                number_format($row->stock_value, 2),
                                $status
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Expiry Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    /**
     * Export enhanced expiry alert report
     */
    public function exportExpiryAlert(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $expiry_days = $request->get('expiry_days', 30);

            $filename = 'stock_expiry_alert_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($business_id, $location_id, $expiry_days) {
                $file = fopen('php://output', 'w');

                // Add BOM for proper UTF-8 handling in Excel
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Add CSV headers
                fputcsv($file, [
                    'Product',
                    'SKU',
                    'Location',
                    'Stock Left',
                    'Lot Number',
                    'Expiry Date',
                    'Manufacturing Date',
                    'Days Left',
                    'Status'
                ]);

                // Build query
                $query = PurchaseLine::leftjoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                    ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
                    ->leftjoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
                    ->leftjoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                    ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                    ->where('t.business_id', $business_id)
                    ->where('p.enable_stock', 1)
                    ->whereNotNull('purchase_lines.exp_date')
                    ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + COALESCE(purchase_lines.quantity_adjusted, 0) + COALESCE(purchase_lines.quantity_returned, 0)')
                    ->whereDate('purchase_lines.exp_date', '<=', \Carbon::now()->addDays($expiry_days));

                if (!empty($location_id)) {
                    $query->where('t.location_id', $location_id);
                }

                $query->select(
                    DB::raw('CASE WHEN p.type = "variable" THEN CONCAT(p.name, " - ", pv.name, " - ", v.name) ELSE p.name END as product_name'),
                    DB::raw('COALESCE(v.sub_sku, p.sku) as sku'),
                    'l.name as location_name',
                    'purchase_lines.lot_number',
                    'purchase_lines.mfg_date',
                    'purchase_lines.exp_date',
                    DB::raw('DATEDIFF(purchase_lines.exp_date, NOW()) as days_to_expire'),
                    DB::raw('COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_sold, 0) - COALESCE(purchase_lines.quantity_adjusted, 0) - COALESCE(purchase_lines.quantity_returned, 0) as stock_left')
                )
                    ->havingRaw('stock_left > 0')
                    ->orderBy('days_to_expire')
                    ->chunk(100, function ($results) use ($file) {
                        foreach ($results as $row) {
                            $days = $row->days_to_expire;
                            $status = 'Good';
                            if ($days < 0) {
                                $status = 'Expired';
                            } elseif ($days == 0) {
                                $status = 'Expires Today';
                            } elseif ($days <= 3) {
                                $status = 'Critical';
                            } elseif ($days <= 7) {
                                $status = 'Warning';
                            }

                            fputcsv($file, [
                                $row->product_name ?: '-',
                                $row->sku ?: '-',
                                $row->location_name ?: '-',
                                number_format($row->stock_left, 2),
                                $row->lot_number ?: '-',
                                $row->exp_date ? date('Y-m-d', strtotime($row->exp_date)) : '-',
                                $row->mfg_date ? date('Y-m-d', strtotime($row->mfg_date)) : '-',
                                $days,
                                $status
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('AdvancedReports Expiry Alert Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }


    /**
     * Get products that need stock alert (below alert quantity)
     *
     * @param int $business_id
     * @param mixed $permitted_locations
     * @return \Illuminate\Database\Query\Builder
     */
    public function getProductAlert($business_id, $permitted_locations = null)
    {
        $query = VariationLocationDetails::join(
            'product_variations as pv',
            'variation_location_details.product_variation_id',
            '=',
            'pv.id'
        )
            ->join(
                'variations as v',
                'variation_location_details.variation_id',
                '=',
                'v.id'
            )
            ->join(
                'products as p',
                'variation_location_details.product_id',
                '=',
                'p.id'
            )
            ->leftjoin(
                'business_locations as l',
                'variation_location_details.location_id',
                '=',
                'l.id'
            )
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at')
            ->whereNotNull('p.alert_quantity')
            ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');

        //Check for permitted locations of a user
        if (!empty($permitted_locations)) {
            if ($permitted_locations != 'all') {
                $query->whereIn('variation_location_details.location_id', $permitted_locations);
            }
        }

        if (!empty(request()->input('location_id'))) {
            $query->where('variation_location_details.location_id', request()->input('location_id'));
        }

        $products = $query->select(
            'p.name as product',
            'l.name as location',
            'variation_location_details.qty_available as stock',
            'p.alert_quantity',
            'p.type',
            'p.sku',
            'pv.name as product_variation',
            'v.name as variation',
            'v.sub_sku',
            'u.short_name as unit'
        )
            ->groupBy('variation_location_details.id')
            ->orderBy('stock', 'asc');

        return $products;
    }
}