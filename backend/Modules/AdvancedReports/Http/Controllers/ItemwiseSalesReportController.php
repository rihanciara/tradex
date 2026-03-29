<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\TransactionSellLine;
use App\TaxRate;
use App\Contact;
use App\BusinessLocation;
use App\Category;
use App\Brands;
use App\Unit;
use App\User;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\ItemwiseSalesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ItemwiseSalesReportController extends Controller
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
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // Enhanced supplier dropdown with "All" option
        $customers = Contact::customersDropdown($business_id, false);
        $customers->prepend(__('lang_v1.all'), 'all');

        // Get categories for advanced filtering
        $categories = Category::where('business_id', $business_id)
            ->select(['id', 'name'])
            ->get()
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all'), 'all');

        // Get brands for filtering
        $brands = Brands::where('business_id', $business_id)
            ->select(['id', 'name'])
            ->get()
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all'), 'all');

        // Get units for filtering
        $units = Unit::where('business_id', $business_id)
            ->select(['id', 'short_name'])
            ->get()
            ->pluck('short_name', 'id')
            ->prepend(__('lang_v1.all'), 'all');

        // Get users for filtering
        $users = User::forDropdown($business_id, false);
        if (is_object($users)) {
            $users = $users->prepend(__('lang_v1.all'), 'all');
        }

        // Get tax rates for filtering
        $tax_rates = TaxRate::where('business_id', $business_id)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->amount . '%)'];
            })
            ->prepend(__('lang_v1.all'), 'all');

        // Get payment types
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $payment_types = collect($payment_types)->prepend(__('lang_v1.all'), 'all');

        return view('advancedreports::itemwise-sales.index')
            ->with(compact(
                'business_locations',
                'customers',
                'categories',
                'brands',
                'units',
                'users',
                'tax_rates',
                'payment_types'
            ));
    }

    /**
     * Get itemwise sales data for DataTables
     */
    public function getItemwiseSalesData(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Handle summary requests
        if ($request->has('summary')) {
            return $this->getSummaryData($request);
        }

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftjoin('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
            ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftjoin('users as created_by', 't.created_by', '=', 'created_by.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id');

        // Apply filters
        $this->applyFilters($query, $request);

        $query->select([
            't.id as transaction_id',
            't.invoice_no',
            't.transaction_date',
            'c.name as customer_name',
            'c.supplier_business_name',
            'c.mobile as customer_mobile',
            'c.tax_number as customer_gstin',
            'bl.name as location_name',

            // Product information
            'p.name as product_name',
            'p.sku',
            DB::raw("CASE WHEN p.type = 'variable' THEN CONCAT(pv.name, ' - ', v.name) ELSE '' END as variation_name"),
            'cat.name as category_name',
            'cat.short_code as hsn_code',
            'b.name as brand_name',
            'u.short_name as unit_name',

            // Quantity and pricing
            DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as sold_qty'),
            'transaction_sell_lines.unit_price_before_discount as unit_price',
            'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax',

            // Discounts
            'transaction_sell_lines.line_discount_amount',
            'transaction_sell_lines.line_discount_type',
            DB::raw('CASE 
                WHEN transaction_sell_lines.line_discount_type = "percentage" THEN 
                    (transaction_sell_lines.unit_price_before_discount * transaction_sell_lines.line_discount_amount / 100) * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
                ELSE 
                    transaction_sell_lines.line_discount_amount * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
            END as total_discount'),

            // Tax information
            'tr.name as tax_name',
            'tr.amount as tax_rate',
            'transaction_sell_lines.item_tax',
            DB::raw('transaction_sell_lines.item_tax * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_tax'),

            // Totals
            DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_before_discount as subtotal'),
            DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax as line_total'),

            // User information
            DB::raw("CONCAT(COALESCE(created_by.first_name, ''), ' ', COALESCE(created_by.last_name, '')) as created_by_name"),
        ]);

        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                return '<div class="btn-group">
                    <button type="button" class="btn btn-info btn-xs btn-modal" 
                        data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '" 
                        data-container=".view_modal">
                        <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </button>
                </div>';
            })
            ->editColumn('transaction_date', function ($row) {
                return \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y');
            })
            ->editColumn('invoice_no', function ($row) {
                return '<a href="#" class="btn-modal text-primary" 
                    data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '" 
                    data-container=".view_modal">
                    <strong>' . $row->invoice_no . '</strong>
                </a>';
            })
            ->editColumn('customer_name', function ($row) {
                $customer_display = '';
                if (!empty($row->supplier_business_name)) {
                    $customer_display .= '<strong>' . $row->supplier_business_name . '</strong><br>';
                }
                $customer_display .= $row->customer_name ?: __('advancedreports::lang.walk_in_customer');
                if (!empty($row->customer_mobile)) {
                    $customer_display .= '<br><small class="text-muted">' . $row->customer_mobile . '</small>';
                }
                if (!empty($row->customer_gstin)) {
                    $customer_display .= '<br><small class="text-muted">GSTIN: ' . $row->customer_gstin . '</small>';
                }
                return $customer_display;
            })
            ->editColumn('product_name', function ($row) {
                $product = $row->product_name;
                if (!empty($row->variation_name)) {
                    $product .= '<br><small class="text-muted">' . $row->variation_name . '</small>';
                }
                if (!empty($row->sku)) {
                    $product .= '<br><small class="text-info">SKU: ' . $row->sku . '</small>';
                }
                return $product;
            })
            ->editColumn('sold_qty', function ($row) {
                return '<span class="badge badge-info">' .
                    $this->transactionUtil->num_f($row->sold_qty, false, null, true) .
                    ' ' . ($row->unit_name ?? '') . '</span>';
            })
            ->editColumn('unit_price', function ($row) {
                return '<span data-orig-value="' . $row->unit_price . '">' .
                    $this->transactionUtil->num_f($row->unit_price) . '</span>';
            })
            ->editColumn('total_discount', function ($row) {
                if ($row->total_discount > 0) {
                    return '<span class="text-warning">' .
                        $this->transactionUtil->num_f($row->total_discount) . '</span>';
                }
                return '-';
            })
            ->editColumn('tax_rate', function ($row) {
                if (!empty($row->tax_rate)) {
                    return '<span class="badge badge-primary">' .
                        $this->transactionUtil->num_f($row->tax_rate) . '%</span>';
                }
                return '-';
            })
            ->editColumn('total_tax', function ($row) {
                return '<span data-orig-value="' . $row->total_tax . '">' .
                    $this->transactionUtil->num_f($row->total_tax) . '</span>';
            })
            ->editColumn('subtotal', function ($row) {
                return '<span data-orig-value="' . $row->subtotal . '">' .
                    $this->transactionUtil->num_f($row->subtotal) . '</span>';
            })
            ->editColumn('line_total', function ($row) {
                return '<span data-orig-value="' . $row->line_total . '" class="text-success">
                    <strong>' . $this->transactionUtil->num_f($row->line_total) . '</strong></span>';
            })
            ->rawColumns([
                'actions',
                'invoice_no',
                'customer_name',
                'product_name',
                'sold_qty',
                'unit_price',
                'total_discount',
                'tax_rate',
                'total_tax',
                'subtotal',
                'line_total'
            ])
            ->make(true);
    }

    /**
     * Get summary data for widgets
     */
    private function getSummaryData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id');

        // Apply filters
        $this->applyFilters($query, $request);

        $summary = $query->select([
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('COUNT(DISTINCT c.id) as total_customers'),
            DB::raw('COUNT(DISTINCT p.id) as total_products'),
            DB::raw('SUM(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_qty_sold'),
            DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) as total_sales'),
            DB::raw('SUM(transaction_sell_lines.item_tax * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))) as total_tax'),
            DB::raw('SUM(CASE 
                WHEN transaction_sell_lines.line_discount_type = "percentage" THEN 
                    (transaction_sell_lines.unit_price_before_discount * transaction_sell_lines.line_discount_amount / 100) * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
                ELSE 
                    transaction_sell_lines.line_discount_amount * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
            END) as total_discount'),
            DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_before_discount) as subtotal_amount'),
        ])->first();

        return response()->json([
            'summary' => [
                'total_transactions' => $summary->total_transactions ?? 0,
                'total_customers' => $summary->total_customers ?? 0,
                'total_products' => $summary->total_products ?? 0,
                'total_qty_sold' => $summary->total_qty_sold ?? 0,
                'total_sales' => $summary->total_sales ?? 0,
                'total_tax' => $summary->total_tax ?? 0,
                'total_discount' => $summary->total_discount ?? 0,
                'subtotal_amount' => $summary->subtotal_amount ?? 0,
                'average_transaction' => $summary->total_transactions > 0 ?
                    ($summary->total_sales / $summary->total_transactions) : 0,
            ]
        ]);
    }

    /**
     * Print itemwise sales report
     */
    public function print(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $business = $this->businessUtil->getDetails($business_id);

            // Get filters for display
            $filters = $this->getFiltersForDisplay($request);

            // Get data for print
            $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftjoin('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
                ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('transaction_sell_lines.parent_sell_line_id');

            // Apply filters
            $this->applyFilters($query, $request);

            $salesData = $query->select([
                't.invoice_no',
                't.transaction_date',
                'c.name as customer_name',
                'c.supplier_business_name',
                'c.mobile as customer_mobile',
                'p.name as product_name',
                'p.sku',
                DB::raw("CASE WHEN p.type = 'variable' THEN CONCAT(pv.name, ' - ', v.name) ELSE '' END as variation_name"),
                'cat.name as category_name',
                'cat.short_code as hsn_code',
                'b.name as brand_name',
                'u.short_name as unit_name',
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as sold_qty'),
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax',
                'tr.amount as tax_rate',
                'transaction_sell_lines.item_tax',
                DB::raw('transaction_sell_lines.item_tax * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_tax'),
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_before_discount as subtotal'),
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax as line_total'),
                'bl.name as location_name'
            ])
                ->orderBy('t.transaction_date', 'desc')
                ->orderBy('c.name')
                ->get();

            // Calculate summary
            $summary = [
                'total_transactions' => $salesData->unique('invoice_no')->count(),
                'total_customers' => $salesData->unique('customer_name')->count(),
                'total_products' => $salesData->count(),
                'total_qty_sold' => $salesData->sum('sold_qty'),
                'total_subtotal' => $salesData->sum('subtotal'),
                'total_tax' => $salesData->sum('total_tax'),
                'total_amount' => $salesData->sum('line_total'),
                'date_range' => $this->getDateRangeText($request)
            ];

            return view('advancedreports::itemwise-sales.print', compact(
                'salesData',
                'summary',
                'business',
                'filters'
            ));
        } catch (\Exception $e) {
            \Log::error('Itemwise Sales Print Error: ' . $e->getMessage());
            return response()->view('errors.500', ['error' => 'Print failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export itemwise sales report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $filters = [
            'location_id' => $request->location_id,
            'customer_id' => $request->customer_id != 'all' ? $request->customer_id : null,
            'category_id' => $request->category_id != 'all' ? $request->category_id : null,
            'brand_id' => $request->brand_id != 'all' ? $request->brand_id : null,
            'unit_id' => $request->unit_id != 'all' ? $request->unit_id : null,
            'tax_rate_id' => $request->tax_rate_id != 'all' ? $request->tax_rate_id : null,
            'user_id' => $request->user_id != 'all' ? $request->user_id : null,
            'payment_method' => $request->payment_method != 'all' ? $request->payment_method : null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount,
            'product_filter' => $request->product_filter,
            'customer_filter' => $request->customer_filter,
        ];

        $filename = 'itemwise_sales_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new ItemwiseSalesReportExport($business_id, $filters), $filename);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('t.transaction_date', [$start_date, $end_date]);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $customer_id = $request->get('customer_id');
        if (!empty($customer_id) && $customer_id != 'all') {
            $query->where('t.contact_id', $customer_id);
        }

        $category_id = $request->get('category_id');
        if (!empty($category_id) && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        $brand_id = $request->get('brand_id');
        if (!empty($brand_id) && $brand_id != 'all') {
            $query->where('p.brand_id', $brand_id);
        }

        $unit_id = $request->get('unit_id');
        if (!empty($unit_id) && $unit_id != 'all') {
            $query->where('p.unit_id', $unit_id);
        }

        $tax_rate_id = $request->get('tax_rate_id');
        if (!empty($tax_rate_id) && $tax_rate_id != 'all') {
            $query->where('transaction_sell_lines.tax_id', $tax_rate_id);
        }

        $user_id = $request->get('user_id');
        if (!empty($user_id) && $user_id != 'all') {
            $query->where('t.created_by', $user_id);
        }

        $payment_method = $request->get('payment_method');
        if (!empty($payment_method) && $payment_method != 'all') {
            $query->whereHas('transaction.payment_lines', function ($q) use ($payment_method) {
                $q->where('method', $payment_method);
            });
        }

        $min_amount = $request->get('min_amount');
        if (!empty($min_amount)) {
            $query->havingRaw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) >= ?', [$min_amount]);
        }

        $max_amount = $request->get('max_amount');
        if (!empty($max_amount)) {
            $query->havingRaw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) <= ?', [$max_amount]);
        }

        // Advanced filters
        $product_filter = $request->get('product_filter');
        if (!empty($product_filter)) {
            $query->where(function ($q) use ($product_filter) {
                $q->where('p.name', 'like', '%' . $product_filter . '%')
                    ->orWhere('p.sku', 'like', '%' . $product_filter . '%');
            });
        }

        $customer_filter = $request->get('customer_filter');
        if (!empty($customer_filter)) {
            $query->where(function ($q) use ($customer_filter) {
                $q->where('c.name', 'like', '%' . $customer_filter . '%')
                    ->orWhere('c.supplier_business_name', 'like', '%' . $customer_filter . '%')
                    ->orWhere('c.mobile', 'like', '%' . $customer_filter . '%');
            });
        }
    }

    /**
     * Get filters for display
     */
    private function getFiltersForDisplay($request)
    {
        $filters = [];

        // Location
        if ($request->location_id) {
            $location = BusinessLocation::find($request->location_id);
            $filters['location_name'] = $location ? $location->name : '';
        } else {
            $filters['location_name'] = 'All';
        }

        // Customer
        if ($request->customer_id && $request->customer_id != 'all') {
            $customer = Contact::find($request->customer_id);
            $filters['customer_name'] = $customer ? $customer->name : '';
        } else {
            $filters['customer_name'] = 'All';
        }

        // Category
        if ($request->category_id && $request->category_id != 'all') {
            $category = Category::find($request->category_id);
            $filters['category_name'] = $category ? $category->name : '';
        } else {
            $filters['category_name'] = 'All';
        }

        // Brand
        if ($request->brand_id && $request->brand_id != 'all') {
            $brand = Brands::find($request->brand_id);
            $filters['brand_name'] = $brand ? $brand->name : '';
        } else {
            $filters['brand_name'] = 'All';
        }

        // Unit
        if ($request->unit_id && $request->unit_id != 'all') {
            $unit = Unit::find($request->unit_id);
            $filters['unit_name'] = $unit ? $unit->actual_name : '';
        } else {
            $filters['unit_name'] = 'All';
        }

        // Tax Rate
        if ($request->tax_rate_id && $request->tax_rate_id != 'all') {
            $taxRate = TaxRate::find($request->tax_rate_id);
            $filters['tax_rate_name'] = $taxRate ? $taxRate->name . ' (' . $taxRate->amount . '%)' : '';
        } else {
            $filters['tax_rate_name'] = 'All';
        }

        // User/Created By
        if ($request->user_id && $request->user_id != 'all') {
            $user = User::find($request->user_id);
            $filters['user_name'] = $user ? $user->user_full_name : '';
        } else {
            $filters['user_name'] = 'All';
        }

        // Payment Method
        if ($request->payment_method && $request->payment_method != 'all') {
            $filters['payment_method'] = ucwords(str_replace('_', ' ', $request->payment_method));
        } else {
            $filters['payment_method'] = 'All';
        }

        // Amount Range
        if ($request->min_amount || $request->max_amount) {
            $minAmount = $request->min_amount ? number_format((float)$request->min_amount, 2) : '0.00';
            $maxAmount = $request->max_amount ? number_format((float)$request->max_amount, 2) : 'No Limit';
            $filters['amount_range'] = $minAmount . ' - ' . $maxAmount;
        } else {
            $filters['amount_range'] = 'All';
        }

        // Product Filter
        if ($request->product_filter) {
            $filters['product_filter'] = $request->product_filter;
        } else {
            $filters['product_filter'] = 'All';
        }

        // Customer Filter
        if ($request->customer_filter) {
            $filters['customer_filter'] = $request->customer_filter;
        } else {
            $filters['customer_filter'] = 'All';
        }

        return $filters;
    }

    /**
     * Get date range text for display
     */
    private function getDateRangeText($request)
    {
        if ($request->start_date && $request->end_date) {
            return \Carbon\Carbon::parse($request->start_date)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($request->end_date)->format('d M Y');
        }
        return 'All Dates';
    }
}