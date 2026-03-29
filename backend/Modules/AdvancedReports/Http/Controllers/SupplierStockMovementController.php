<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Product;
use App\Contact;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionSellLine;
use App\PurchaseLine;
use App\VariationLocationDetails;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\SupplierStockMovementExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SupplierStockMovementController extends Controller
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
    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // Get suppliers who have products assigned
        $suppliers = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->whereHas('products') // Only suppliers who have products
            ->select(['id', 'name', 'supplier_business_name'])
            ->get()
            ->mapWithKeys(function ($supplier) {
                $display_name = !empty($supplier->supplier_business_name)
                    ? $supplier->supplier_business_name . ' (' . $supplier->name . ')'
                    : $supplier->name;
                return [$supplier->id => $display_name];
            })
            ->prepend(__('lang_v1.all'), '');

        return view('advancedreports::supplier-stock-movement.index')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Get supplier stock movement data for DataTables
     */
    public function getSupplierStockData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Handle summary requests
        if ($request->has('summary')) {
            return $this->getSummaryData($request, $business_id);
        }

        // Base query for suppliers with products
        $query = Contact::where('contacts.business_id', $business_id)
            ->where('contacts.type', 'supplier')
            ->whereHas('products', function ($q) {
                $q->where('products.enable_stock', 1); // Only track stock products
            })
            ->select([
                'contacts.id as supplier_id',
                'contacts.name as supplier_name',
                'contacts.supplier_business_name',
                'contacts.contact_id',
                'contacts.mobile',
                'contacts.created_at as supplier_since'
            ]);

        // Apply filters
        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return '<div class="btn-group">
                    <button type="button" class="btn btn-info btn-xs view-supplier-details" 
                        data-supplier-id="' . $row->supplier_id . '"
                        title="' . __('messages.view') . '">
                        <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </button>
                </div>';
            })
            ->addColumn('supplier_display', function ($row) {
                $name = $row->supplier_business_name
                    ? $row->supplier_business_name . '<br><small>' . $row->supplier_name . '</small>'
                    : $row->supplier_name;

                if ($row->mobile) {
                    $name .= '<br><small class="text-muted">' . $row->mobile . '</small>';
                }

                return $name;
            })
            ->addColumn('today_stock_qty', function ($row) use ($business_id) {
                $qty = $this->getTodayStockQty($row->supplier_id, $business_id);
                return $this->transactionUtil->num_f($qty, false, null, true);
            })
            ->addColumn('today_stock_purchase_value', function ($row) use ($business_id) {
                $value = $this->getTodayStockPurchaseValue($row->supplier_id, $business_id);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('today_stock_sale_value', function ($row) use ($business_id) {
                $value = $this->getTodayStockSaleValue($row->supplier_id, $business_id);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('total_sale_qty', function ($row) use ($business_id, $request) {
                $qty = $this->getTotalSaleQty($row->supplier_id, $business_id, $request);
                return $this->transactionUtil->num_f($qty, false, null, true);
            })
            ->addColumn('total_sale_purchase_value', function ($row) use ($business_id, $request) {
                $value = $this->getTotalSalePurchaseValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('total_sale_sale_value', function ($row) use ($business_id, $request) {
                $value = $this->getTotalSaleSaleValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('total_purchase_qty', function ($row) use ($business_id, $request) {
                $qty = $this->getTotalPurchaseQty($row->supplier_id, $business_id, $request);
                return $this->transactionUtil->num_f($qty, false, null, true);
            })
            ->addColumn('total_purchase_purchase_value', function ($row) use ($business_id, $request) {
                $value = $this->getTotalPurchasePurchaseValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('total_purchase_sale_value', function ($row) use ($business_id, $request) {
                $value = $this->getTotalPurchaseSaleValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('balance_qty', function ($row) use ($business_id, $request) {
                $qty = $this->getBalanceQty($row->supplier_id, $business_id, $request);
                return $this->transactionUtil->num_f($qty, false, null, true);
            })
            ->addColumn('balance_purchase_value', function ($row) use ($business_id, $request) {
                $value = $this->getBalancePurchaseValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('balance_value', function ($row) use ($business_id, $request) {
                $value = $this->getBalanceValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->addColumn('profit_value', function ($row) use ($business_id, $request) {
                $value = $this->getProfitValue($row->supplier_id, $business_id, $request);
                return '<span data-orig-value="' . $value . '">' . $this->transactionUtil->num_f($value, true) . '</span>';
            })
            ->editColumn('supplier_since', function ($row) {
                return $this->transactionUtil->format_date($row->supplier_since, true);
            })
            ->rawColumns(['action', 'supplier_display', 'today_stock_purchase_value', 'today_stock_sale_value', 'total_sale_purchase_value', 'total_sale_sale_value', 'total_purchase_purchase_value', 'total_purchase_sale_value', 'balance_purchase_value', 'balance_value', 'profit_value'])
            ->make(true);
    }

    /**
     * Get summary data
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        return $this->getSummaryData($request, $business_id);
    }

    /**
     * Helper method to get summary data
     */
    private function getSummaryData($request, $business_id)
    {
        // Get all suppliers with products
        $supplier_query = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->whereHas('products');

        $this->applyFilters($supplier_query, $request);
        $supplier_ids = $supplier_query->pluck('id');

        $summary = [
            'total_suppliers' => $supplier_ids->count(),
            'total_today_stock_qty' => 0,
            'total_today_stock_purchase_value' => 0,
            'total_today_stock_sale_value' => 0,
            'total_sale_qty' => 0,
            'total_sale_purchase_value' => 0,
            'total_sale_sale_value' => 0,
            'total_purchase_qty' => 0,
            'total_purchase_purchase_value' => 0,
            'total_purchase_sale_value' => 0,
            'total_balance_qty' => 0,
            'total_balance_purchase_value' => 0,
            'total_balance_value' => 0,
            'total_profit_value' => 0,
        ];

        foreach ($supplier_ids as $supplier_id) {
            $summary['total_today_stock_qty'] += $this->getTodayStockQty($supplier_id, $business_id);
            $summary['total_today_stock_purchase_value'] += $this->getTodayStockPurchaseValue($supplier_id, $business_id);
            $summary['total_today_stock_sale_value'] += $this->getTodayStockSaleValue($supplier_id, $business_id);

            $summary['total_sale_qty'] += $this->getTotalSaleQty($supplier_id, $business_id, $request);
            $summary['total_sale_purchase_value'] += $this->getTotalSalePurchaseValue($supplier_id, $business_id, $request);
            $summary['total_sale_sale_value'] += $this->getTotalSaleSaleValue($supplier_id, $business_id, $request);

            $summary['total_purchase_qty'] += $this->getTotalPurchaseQty($supplier_id, $business_id, $request);
            $summary['total_purchase_purchase_value'] += $this->getTotalPurchasePurchaseValue($supplier_id, $business_id, $request);
            $summary['total_purchase_sale_value'] += $this->getTotalPurchaseSaleValue($supplier_id, $business_id, $request);

            $summary['total_balance_qty'] += $this->getBalanceQty($supplier_id, $business_id, $request);
            $summary['total_balance_purchase_value'] += $this->getBalancePurchaseValue($supplier_id, $business_id, $request);
            $summary['total_balance_value'] += $this->getBalanceValue($supplier_id, $business_id, $request);

            $summary['total_profit_value'] += $this->getProfitValue($supplier_id, $business_id, $request);
        }

        // Calculate averages
        $summary['avg_profit_per_supplier'] = $summary['total_suppliers'] > 0
            ? $summary['total_profit_value'] / $summary['total_suppliers']
            : 0;

        $summary['profit_margin_percent'] = $summary['total_sale_sale_value'] > 0
            ? ($summary['total_profit_value'] / $summary['total_sale_sale_value']) * 100
            : 0;

        return response()->json($summary);
    }

    /**
     * Export supplier stock movement report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.export')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business = $this->businessUtil->getDetails($business_id);

        $filters = [
            'location_id' => $request->location_id,
            'supplier_id' => $request->supplier_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $filename = 'supplier_stock_movement_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new SupplierStockMovementExport($business_id, $filters), $filename);
    }

    /**
     * Get supplier details with stock movements
     */
    public function getSupplierDetails(Request $request, $supplierId)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            // Get supplier details
            $supplier = Contact::where('business_id', $business_id)
                ->where('id', $supplierId)
                ->where('type', 'supplier')
                ->first();

            if (!$supplier) {
                return response()->json(['error' => 'Supplier not found'], 404);
            }

            // Get supplier's products with current stock
            $products = Product::where('business_id', $business_id)
                ->where('supplier_id', $supplierId)
                ->where('enable_stock', 1)
                ->with(['variations.variation_location_details'])
                ->get()
                ->map(function ($product) use ($business_id, $request) {
                    $current_stock = 0;
                    $stock_value = 0;

                    foreach ($product->variations as $variation) {
                        foreach ($variation->variation_location_details as $location_detail) {
                            $current_stock += $location_detail->qty_available;
                            $stock_value += $location_detail->qty_available * $variation->default_purchase_price;
                        }
                    }

                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'current_stock' => $current_stock,
                        'stock_value' => $stock_value,
                        'unit' => $product->unit->short_name ?? '',
                    ];
                });

            // Get recent transactions
            $recent_transactions = $this->getSupplierRecentTransactions($supplierId, $business_id, $request);

            return response()->json([
                'supplier' => $supplier,
                'products' => $products,
                'transactions' => $recent_transactions,
                'summary' => [
                    'total_products' => $products->count(),
                    'total_stock_qty' => $products->sum('current_stock'),
                    'total_stock_value' => $products->sum('stock_value'),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Supplier details error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading supplier details'], 500);
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        // Date range filter (affects transactions)
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        // Location filter
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            // This affects product locations
        }

        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            // This affects product locations
        }

        // Supplier filter
        $supplier_id = $request->get('supplier_id');
        if (!empty($supplier_id)) {
            $query->where('contacts.id', $supplier_id);
        }
    }

    // Stock calculation methods
    private function getTodayStockQty($supplier_id, $business_id)
    {
        $result = DB::table('products')
            ->join('variations', 'products.id', '=', 'variations.product_id')
            ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
            ->where('products.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('products.enable_stock', 1)
            ->sum('variation_location_details.qty_available');

        return (float) $result ?: 0;
    }

    private function getTodayStockPurchaseValue($supplier_id, $business_id)
    {
        $result = DB::table('products')
            ->join('variations', 'products.id', '=', 'variations.product_id')
            ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
            ->where('products.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('products.enable_stock', 1)
            ->sum(DB::raw('variation_location_details.qty_available * variations.default_purchase_price'));

        return round((float) $result ?: 0, 2);
    }

    private function getTodayStockSaleValue($supplier_id, $business_id)
    {
        $result = DB::table('products')
            ->join('variations', 'products.id', '=', 'variations.product_id')
            ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
            ->where('products.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('products.enable_stock', 1)
            ->sum(DB::raw('variation_location_details.qty_available * variations.sell_price_inc_tax'));

        return round((float) $result ?: 0, 2);
    }

    private function getTotalSaleQty($supplier_id, $business_id, $request)
    {
        $query = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)'));
        return (float) $result ?: 0;
    }

    private function getTotalSalePurchaseValue($supplier_id, $business_id, $request)
    {
        $query = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * variations.default_purchase_price'));
        return round((float) $result ?: 0, 2);
    }

    private function getTotalSaleSaleValue($supplier_id, $business_id, $request)
    {
        $query = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax'));
        return round((float) $result ?: 0, 2);
    }

    private function getTotalPurchaseQty($supplier_id, $business_id, $request)
    {
        $query = DB::table('purchase_lines')
            ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('purchase_lines.quantity - COALESCE(purchase_lines.quantity_returned, 0)'));
        return (float) $result ?: 0;
    }

    private function getTotalPurchasePurchaseValue($supplier_id, $business_id, $request)
    {
        $query = DB::table('purchase_lines')
            ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('(purchase_lines.quantity - COALESCE(purchase_lines.quantity_returned, 0)) * purchase_lines.purchase_price_inc_tax'));
        return round((float) $result ?: 0, 2);
    }

    private function getTotalPurchaseSaleValue($supplier_id, $business_id, $request)
    {
        $query = DB::table('purchase_lines')
            ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received');

        // Apply date filter if provided
        if ($request->get('start_date') && $request->get('end_date')) {
            $query->whereBetween('transactions.transaction_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $result = $query->sum(DB::raw('(purchase_lines.quantity - COALESCE(purchase_lines.quantity_returned, 0)) * variations.sell_price_inc_tax'));
        return round((float) $result ?: 0, 2);
    }

    private function getBalanceQty($supplier_id, $business_id, $request)
    {
        // Balance = Today Stock + Total Purchase - Total Sale
        $balance = $this->getTodayStockQty($supplier_id, $business_id)
            + $this->getTotalPurchaseQty($supplier_id, $business_id, $request)
            - $this->getTotalSaleQty($supplier_id, $business_id, $request);

        return (float) $balance;
    }

    private function getBalancePurchaseValue($supplier_id, $business_id, $request)
    {
        // Balance purchase value = Today Stock Purchase Value + Total Purchase Purchase Value - Total Sale Purchase Value
        $balance = $this->getTodayStockPurchaseValue($supplier_id, $business_id)
            + $this->getTotalPurchasePurchaseValue($supplier_id, $business_id, $request)
            - $this->getTotalSalePurchaseValue($supplier_id, $business_id, $request);

        return round((float) $balance, 2);
    }

    private function getBalanceValue($supplier_id, $business_id, $request)
    {
        // Balance value = Today Stock Sale Value + Total Purchase Sale Value - Total Sale Sale Value
        $balance = $this->getTodayStockSaleValue($supplier_id, $business_id)
            + $this->getTotalPurchaseSaleValue($supplier_id, $business_id, $request)
            - $this->getTotalSaleSaleValue($supplier_id, $business_id, $request);

        return round((float) $balance, 2);
    }

    private function getProfitValue($supplier_id, $business_id, $request)
    {
        // Profit = Total Sale Sale Value - Total Sale Purchase Value
        $profit = $this->getTotalSaleSaleValue($supplier_id, $business_id, $request)
            - $this->getTotalSalePurchaseValue($supplier_id, $business_id, $request);

        return round((float) $profit, 2);
    }

    private function getSupplierRecentTransactions($supplier_id, $business_id, $request)
    {
        // Get recent purchase and sale transactions for the supplier
        $purchases = DB::table('transactions')
            ->join('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->select([
                'transactions.transaction_date',
                'transactions.ref_no',
                'products.name as product_name',
                'purchase_lines.quantity',
                'purchase_lines.purchase_price_inc_tax as unit_price',
                DB::raw('"Purchase" as transaction_type'),
                DB::raw('purchase_lines.quantity * purchase_lines.purchase_price_inc_tax as line_total')
            ])
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(50);

        $sales = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('products.supplier_id', $supplier_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select([
                'transactions.transaction_date',
                'transactions.invoice_no as ref_no',
                'products.name as product_name',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price_inc_tax as unit_price',
                DB::raw('"Sale" as transaction_type'),
                DB::raw('transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax as line_total')
            ])
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(50);

        // Combine and return recent transactions
        return $purchases->union($sales)
            ->orderBy('transaction_date', 'desc')
            ->limit(100)
            ->get();
    }
}
