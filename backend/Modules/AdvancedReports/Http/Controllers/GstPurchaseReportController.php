<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\PurchaseLine;
use App\TaxRate;
use App\Contact;
use App\BusinessLocation;
use App\Category;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\GstPurchaseReportExport;
use Maatwebsite\Excel\Facades\Excel;

class GstPurchaseReportController extends Controller
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
        if (!auth()->user()->can('AdvancedReports.gst_purchase_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        
        // Enhanced supplier dropdown with "All" option
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $suppliers->prepend(__('lang_v1.all'), 'all');

        // Get categories for advanced filtering
        $categories = Category::where('business_id', $business_id)
            ->select(['id', 'name'])
            ->get()
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all'), 'all');

        // Get tax rates for filtering
        $tax_rates = TaxRate::where('business_id', $business_id)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->amount . '%)'];
            })
            ->prepend(__('lang_v1.all'), 'all');

        // Get taxes for the view
        $taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();

        return view('advancedreports::gst.purchase.index')
            ->with(compact('business_locations', 'suppliers', 'taxes', 'categories', 'tax_rates'));
    }

    /**
     * Get GST purchase data for DataTables
     */
    public function getGstPurchaseData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_purchase_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Handle summary requests
        if ($request->has('summary')) {
            return $this->getSummaryData($request);
        }

        $taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();

        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'purchase_lines.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('tax_rates as tr', 'purchase_lines.tax_id', '=', 'tr.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select(
                'c.name as supplier',
                'c.supplier_business_name',
                'c.contact_id',
                'c.tax_number',
                'cat.short_code',
                'cat.name as category_name',
                't.id as transaction_id',
                't.ref_no',
                't.transaction_date as transaction_date',
                'purchase_lines.pp_without_discount as unit_price',
                'purchase_lines.purchase_price as unit_price_after_discount',
                DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                'purchase_lines.discount_percent',
                'purchase_lines.item_tax',
                'tr.amount as tax_percent',
                'tr.name as tax_name',
                'tr.is_tax_group',
                'purchase_lines.tax_id',
                'u.short_name as unit',
                'bl.name as location_name',
                DB::raw('((purchase_lines.quantity- purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) as line_total'),
                'p.name as product_name',
                'p.sku'
            )
            ->groupBy('purchase_lines.id');

        // Apply filters
        $this->applyFilters($query, $request);

        $datatable = Datatables::of($query);

        // Add tax columns dynamically
        $raw_cols = ['ref_no', 'taxable_value', 'purchase_qty', 'discount_amount', 'unit_price', 'tax', 'supplier', 'line_total', 'actions'];
        $group_taxes_array = TaxRate::groupTaxes($business_id);
        $group_taxes = [];

        foreach ($group_taxes_array as $group_tax) {
            foreach ($group_tax['sub_taxes'] as $sub_tax) {
                $group_taxes[$group_tax->id]['sub_taxes'][$sub_tax->id] = $sub_tax;
            }
        }

        foreach ($taxes as $tax) {
            $col = 'tax_' . $tax['id'];
            $raw_cols[] = $col;
            $datatable->addColumn($col, function ($row) use ($tax, $group_taxes) {
                $sub_tax_share = 0;
                if ($row->is_tax_group == 1 && array_key_exists($tax['id'], $group_taxes[$row->tax_id]['sub_taxes'])) {
                    $sub_tax_share = $this->transactionUtil->calc_percentage($row->unit_price_after_discount, $group_taxes[$row->tax_id]['sub_taxes'][$tax['id']]->amount) * $row->purchase_qty;
                }

                if ($sub_tax_share > 0) {
                    return '<span data-orig-value="' . $sub_tax_share . '">' . $this->transactionUtil->num_f($sub_tax_share) . '</span>';
                } else {
                    return '';
                }
            });
        }

        return $datatable->addColumn('actions', function ($row) {
                return '<div class="btn-group">
                    <button type="button" class="btn btn-info btn-xs btn-modal" 
                        data-href="' . action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->transaction_id]) . '" 
                        data-container=".view_modal">
                        <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </button>
                </div>';
            })
            ->addColumn('taxable_value', function ($row) {
                $taxable_value = $row->unit_price_after_discount * $row->purchase_qty;
                return '<span class="taxable_value" data-orig-value="' . $taxable_value . '">' . $this->transactionUtil->num_f($taxable_value) . '</span>';
            })
            ->editColumn('ref_no', function ($row) {
                return '<a href="#" class="btn-modal text-primary" data-href="' . action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->transaction_id]) . '" data-container=".view_modal"><strong>' . $row->ref_no . '</strong></a>';
            })
            ->editColumn('transaction_date', function ($row) {
                return \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y');
            })
            ->editColumn('purchase_qty', function ($row) {
                $stock = $this->transactionUtil->num_f($row->purchase_qty, false, null, true);
                return '<span class="badge badge-info">' . $stock . ' ' . $row->unit . '</span>';
            })
            ->editColumn('unit_price', function ($row) {
                return '<span data-orig-value="' . $row->unit_price . '">' . $this->transactionUtil->num_f($row->unit_price) . '</span>';
            })
            ->editColumn('line_total', function ($row) {
                return '<span data-orig-value="' . $row->line_total . '" class="text-success"><strong>' . $this->transactionUtil->num_f($row->line_total) . '</strong></span>';
            })
            ->addColumn('discount_amount', function ($row) {
                $discount = !empty($row->discount_percent) ? $row->discount_percent : 0;
                if (!empty($discount)) {
                    $discount = $row->unit_price * ($discount / 100);
                }
                return '<span class="text-warning">' . $this->transactionUtil->num_f($discount) . '</span>';
            })
            ->editColumn('tax_percent', function ($row) {
                if (!empty($row->tax_percent)) {
                    return '<span class="badge badge-primary">' . $this->transactionUtil->num_f($row->tax_percent) . '%</span>';
                }
                return '';
            })
            ->editColumn('supplier', function ($row) {
                $supplier_display = '';
                if (!empty($row->supplier_business_name)) {
                    $supplier_display .= '<strong>' . $row->supplier_business_name . '</strong><br>';
                }
                $supplier_display .= $row->supplier;
                if (!empty($row->tax_number)) {
                    $supplier_display .= '<br><small class="text-muted">GSTIN: ' . $row->tax_number . '</small>';
                }
                return $supplier_display;
            })
            ->addColumn('category', function ($row) {
                return $row->category_name ?? '<span class="text-muted">No Category</span>';
            })
            ->addColumn('location', function ($row) {
                return $row->location_name ?? '<span class="text-muted">-</span>';
            })
            ->rawColumns($raw_cols)
            ->make(true);
    }

    /**
     * Get summary data for widgets
     */
    private function getSummaryData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftjoin('products as p', 'purchase_lines.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received');

        // Apply filters
        $this->applyFilters($query, $request);

        $summary = $query->select(
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('COUNT(DISTINCT c.id) as total_suppliers'),
            DB::raw('COUNT(DISTINCT cat.id) as total_categories'),
            DB::raw('SUM((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) as total_purchases'),
            DB::raw('SUM(purchase_lines.item_tax * (purchase_lines.quantity - purchase_lines.quantity_returned)) as total_tax'),
            DB::raw('SUM((purchase_lines.quantity - purchase_lines.quantity_returned) * (purchase_lines.pp_without_discount - purchase_lines.purchase_price)) as total_discount'),
            DB::raw('SUM((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price) as taxable_amount'),
            DB::raw('SUM(purchase_lines.quantity - purchase_lines.quantity_returned) as total_quantity')
        )->first();

        return response()->json([
            'summary' => [
                'total_transactions' => $summary->total_transactions ?? 0,
                'total_suppliers' => $summary->total_suppliers ?? 0,
                'total_categories' => $summary->total_categories ?? 0,
                'total_purchases' => $summary->total_purchases ?? 0,
                'total_tax' => $summary->total_tax ?? 0,
                'total_discount' => $summary->total_discount ?? 0,
                'taxable_amount' => $summary->taxable_amount ?? 0,
                'total_quantity' => $summary->total_quantity ?? 0,
                'average_transaction' => $summary->total_transactions > 0 ? ($summary->total_purchases / $summary->total_transactions) : 0,
                'average_tax_rate' => $summary->taxable_amount > 0 ? (($summary->total_tax / $summary->taxable_amount) * 100) : 0
            ]
        ]);
    }

    /**
     * Print GST purchase report
     */
    public function print(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_purchase_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $business = $this->businessUtil->getDetails($business_id);

            // Get filters
            $filters = $this->getFiltersForDisplay($request);

            // Get data for print
            $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'purchase_lines.product_id', '=', 'p.id')
                ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftjoin('tax_rates as tr', 'purchase_lines.tax_id', '=', 'tr.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase')
                ->where('t.status', 'received');

            // Apply filters
            $this->applyFilters($query, $request);

            $purchaseData = $query->select(
                'c.name as supplier',
                'c.supplier_business_name',
                'c.tax_number',
                't.ref_no',
                't.transaction_date',
                'p.name as product_name',
                'p.sku',
                'cat.short_code as hsn_code',
                'purchase_lines.purchase_price as unit_price',
                DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as quantity'),
                'u.short_name as unit',
                'tr.amount as tax_rate',
                'purchase_lines.item_tax',
                DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price) as taxable_amount'),
                DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) as total_amount'),
                'bl.name as location_name'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->orderBy('c.name')
            ->get();

            // Calculate summary
            $summary = [
                'total_transactions' => $purchaseData->unique('ref_no')->count(),
                'total_suppliers' => $purchaseData->unique('supplier')->count(),
                'total_taxable_amount' => $purchaseData->sum('taxable_amount'),
                'total_tax_amount' => $purchaseData->sum(function($item) {
                    return $item->item_tax * $item->quantity;
                }),
                'total_amount' => $purchaseData->sum('total_amount'),
                'total_quantity' => $purchaseData->sum('quantity'),
                'date_range' => $this->getDateRangeText($request)
            ];

            return view('advancedreports::gst.purchase.print', compact(
                'purchaseData',
                'summary',
                'business',
                'filters'
            ));

        } catch (\Exception $e) {
            \Log::error('GST Purchase Print Error: ' . $e->getMessage());
            return response()->view('errors.500', ['error' => 'Print failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export GST purchase report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_purchase_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $filters = [
            'location_id' => $request->location_id,
            'supplier_id' => $request->supplier_id != 'all' ? $request->supplier_id : null,
            'category_id' => $request->category_id != 'all' ? $request->category_id : null,
            'tax_rate_id' => $request->tax_rate_id != 'all' ? $request->tax_rate_id : null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount,
        ];

        $filename = 'gst_purchase_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new GstPurchaseReportExport($business_id, $filters), $filename);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        if (!empty($start_date) && !empty($end_date)) {
            $query->where('t.transaction_date', '>=', $start_date)
                ->where('t.transaction_date', '<=', $end_date);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $supplier_id = $request->get('supplier_id');
        if (!empty($supplier_id) && $supplier_id != 'all') {
            $query->where('t.contact_id', $supplier_id);
        }

        $category_id = $request->get('category_id');
        if (!empty($category_id) && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        $tax_rate_id = $request->get('tax_rate_id');
        if (!empty($tax_rate_id) && $tax_rate_id != 'all') {
            $query->where('purchase_lines.tax_id', $tax_rate_id);
        }

        $min_amount = $request->get('min_amount');
        if (!empty($min_amount)) {
            $query->havingRaw('SUM((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) >= ?', [$min_amount]);
        }

        $max_amount = $request->get('max_amount');
        if (!empty($max_amount)) {
            $query->havingRaw('SUM((purchase_lines.quantity - purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) <= ?', [$max_amount]);
        }

        // Advanced filters
        $gstin_filter = $request->get('gstin_filter');
        if (!empty($gstin_filter)) {
            $query->where('c.tax_number', 'like', '%' . $gstin_filter . '%');
        }

        $product_filter = $request->get('product_filter');
        if (!empty($product_filter)) {
            $query->where(function($q) use ($product_filter) {
                $q->where('p.name', 'like', '%' . $product_filter . '%')
                  ->orWhere('p.sku', 'like', '%' . $product_filter . '%');
            });
        }
    }

    /**
     * Get filters for display
     */
    private function getFiltersForDisplay($request)
    {
        $filters = [];

        if ($request->location_id) {
            $location = BusinessLocation::find($request->location_id);
            $filters['location_name'] = $location ? $location->name : '';
        }

        if ($request->supplier_id && $request->supplier_id != 'all') {
            $supplier = Contact::find($request->supplier_id);
            $filters['supplier_name'] = $supplier ? $supplier->name : '';
        }

        if ($request->category_id && $request->category_id != 'all') {
            $category = Category::find($request->category_id);
            $filters['category_name'] = $category ? $category->name : '';
        }

        if ($request->tax_rate_id && $request->tax_rate_id != 'all') {
            $tax_rate = TaxRate::find($request->tax_rate_id);
            $filters['tax_rate_name'] = $tax_rate ? $tax_rate->name : '';
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