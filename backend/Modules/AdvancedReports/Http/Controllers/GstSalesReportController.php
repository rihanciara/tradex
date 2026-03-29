<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\TransactionSellLine;
use App\Transaction;
use App\TaxRate;
use App\Contact;
use App\BusinessLocation;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\GstSalesReportExport;
use Maatwebsite\Excel\Facades\Excel;

class GstSalesReportController extends Controller
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
        if (!auth()->user()->can('AdvancedReports.gst_sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id);

        // Get taxes for dynamic columns
        $taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();

        return view('advancedreports::gst.sales.index')
            ->with(compact('business_locations', 'customers', 'taxes'));
    }

    /**
     * Get GST sales data for DataTables (Per Product)
     */
    public function getGstSalesData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select(
                'c.name as customer',
                'c.supplier_business_name',
                'c.contact_id',
                'c.tax_number',
                'cat.short_code',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price as unit_price_after_discount',
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tr.amount as tax_percent',
                'tr.is_tax_group',
                'transaction_sell_lines.tax_id',
                'u.short_name as unit',
                'transaction_sell_lines.parent_sell_line_id',
                DB::raw('((transaction_sell_lines.quantity- transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as line_total'),
                'p.name as product_name'
            )
            ->groupBy('transaction_sell_lines.id');

        // Apply filters
        $this->applyFilters($query, $request);

        $datatable = Datatables::of($query);

        // Add tax columns dynamically
        $raw_cols = ['invoice_no', 'sell_qty', 'taxable_value', 'discount_amount', 'unit_price', 'tax', 'customer', 'line_total'];
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
            $datatable->addColumn($col, function ($row) use ($tax, $col, $group_taxes) {
                $sub_tax_share = 0;
                if ($row->is_tax_group == 1 && array_key_exists($tax['id'], $group_taxes[$row->tax_id]['sub_taxes'])) {
                    $sub_tax_share = $this->transactionUtil->calc_percentage($row->unit_price_after_discount, $group_taxes[$row->tax_id]['sub_taxes'][$tax['id']]->amount) * $row->sell_qty;
                }

                if ($sub_tax_share > 0) {
                    $class = is_null($row->parent_sell_line_id) ? $col : '';
                    return '<span class="' . $class . '" data-orig-value="' . $sub_tax_share . '">' . $this->transactionUtil->num_f($sub_tax_share) . '</span>';
                } else {
                    return '';
                }
            });
        }

        return $datatable->addColumn('taxable_value', function ($row) {
            $taxable_value = $row->unit_price_after_discount * $row->sell_qty;
            $class = is_null($row->parent_sell_line_id) ? 'taxable_value' : '';
            return '<span class="' . $class . '"data-orig-value="' . $taxable_value . '">' . $this->transactionUtil->num_f($taxable_value) . '</span>';
        })
            ->editColumn('invoice_no', function ($row) {
                return '<a href="#" class="btn-modal" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '" data-container=".view_modal">' . $row->invoice_no . '</a>';
            })
            ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
            ->editColumn('sell_qty', function ($row) {
                return $this->transactionUtil->num_f($row->sell_qty, false, null, true) . ' ' . $row->unit;
            })
            ->editColumn('unit_price', function ($row) {
                return '<span data-orig-value="' . $row->unit_price . '">' . $this->transactionUtil->num_f($row->unit_price) . '</span>';
            })
            ->editColumn('line_total', function ($row) {
                return '<span data-orig-value="' . $row->line_total . '">' . $this->transactionUtil->num_f($row->line_total) . '</span>';
            })
            ->editColumn('discount_amount', function ($row) {
                $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;
                if (!empty($discount) && $row->discount_type == 'percentage') {
                    $discount = $row->unit_price * ($discount / 100);
                }
                return $this->transactionUtil->num_f($discount);
            })
            ->editColumn('tax_percent', '@if(!empty($tax_percent)){{@num_format($tax_percent)}}% @endif')
            ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$customer}}')
            ->rawColumns($raw_cols)
            ->make(true);
    }

    /**
     * Get GST sales data for DataTables (Per Invoice)
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    public function getGstSalesDataPerInvoice(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();

        $query = Transaction::from('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->leftjoin('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select(
                'c.name as customer',
                'c.supplier_business_name',
                'c.contact_id',
                'c.tax_number',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date',
                DB::raw('SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price) as total_taxable_value'),
                DB::raw('SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax) as total_amount'),
                DB::raw('SUM(tsl.item_tax * (tsl.quantity - tsl.quantity_returned)) as total_tax'),
                DB::raw('SUM(CASE 
                WHEN tsl.line_discount_type = "percentage" 
                THEN (tsl.quantity - tsl.quantity_returned) * tsl.unit_price_before_discount * (tsl.line_discount_amount / 100)
                ELSE (tsl.quantity - tsl.quantity_returned) * tsl.line_discount_amount 
            END) as total_discount'),
                DB::raw('COUNT(DISTINCT tsl.product_id) as total_products')
            )
            ->groupBy('t.id', 'c.name', 'c.supplier_business_name', 'c.contact_id', 'c.tax_number', 't.invoice_no', 't.transaction_date');

        // Apply filters
        $this->applyFilters($query, $request);

        $datatable = Datatables::of($query);

        // Add tax columns dynamically for per-invoice view
        $raw_cols = ['invoice_no', 'total_taxable_value', 'total_discount', 'customer', 'total_amount', 'total_tax'];
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
            $datatable->addColumn($col, function ($row) use ($tax, $col, $group_taxes, $business_id) {
                // Calculate tax per invoice by getting detailed transaction data
                $tax_amount = TransactionSellLine::join('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
                    ->where('transaction_sell_lines.transaction_id', $row->transaction_id)
                    ->where('tr.is_tax_group', 1)
                    ->where('transaction_sell_lines.parent_sell_line_id', null)
                    ->sum(DB::raw('CASE 
                    WHEN tr.is_tax_group = 1 
                    THEN (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price * (' . $tax['amount'] . ' / 100)
                    ELSE 0 
                END'));

                if ($tax_amount > 0) {
                    return '<span class="' . $col . '" data-orig-value="' . $tax_amount . '">' . $this->transactionUtil->num_f($tax_amount) . '</span>';
                } else {
                    return '<span class="' . $col . '" data-orig-value="0">-</span>';
                }
            });
        }

        return $datatable->editColumn('invoice_no', function ($row) {
            return '<a href="#" class="btn-modal" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '" data-container=".view_modal">' . $row->invoice_no . '</a>';
        })
            ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
            ->editColumn('total_taxable_value', function ($row) {
                return '<span data-orig-value="' . $row->total_taxable_value . '">' . $this->transactionUtil->num_f($row->total_taxable_value) . '</span>';
            })
            ->editColumn('total_amount', function ($row) {
                return '<span data-orig-value="' . $row->total_amount . '">' . $this->transactionUtil->num_f($row->total_amount) . '</span>';
            })
            ->editColumn('total_tax', function ($row) {
                return '<span data-orig-value="' . $row->total_tax . '">' . $this->transactionUtil->num_f($row->total_tax) . '</span>';
            })
            ->editColumn('total_discount', function ($row) {
                return '<span data-orig-value="' . $row->total_discount . '">' . $this->transactionUtil->num_f($row->total_discount) . '</span>';
            })
            ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$customer}}')
            ->addColumn('total_products', function ($row) {
                return $row->total_products . ' ' . __('sale.products');
            })
            ->rawColumns($raw_cols)
            ->make(true);
    }

    /**
     * Get summary data for widgets
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        // Apply filters
        $this->applyFilters($query, $request);

        $summary = $query->select(
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('COUNT(DISTINCT c.id) as total_customers'),
            DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as total_sales'),
            DB::raw('SUM(transaction_sell_lines.item_tax * (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)) as total_tax'),
            DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * (transaction_sell_lines.unit_price_before_discount - transaction_sell_lines.unit_price)) as total_discount'),
            DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price) as taxable_amount')
        )->first();

        return response()->json([
            'total_transactions' => $summary->total_transactions ?? 0,
            'total_customers' => $summary->total_customers ?? 0,
            'total_sales' => $summary->total_sales ?? 0,
            'total_tax' => $summary->total_tax ?? 0,
            'total_discount' => $summary->total_discount ?? 0,
            'taxable_amount' => $summary->taxable_amount ?? 0,
            'average_transaction' => $summary->total_transactions > 0 ? ($summary->total_sales / $summary->total_transactions) : 0,
            'average_tax_rate' => $summary->taxable_amount > 0 ? (($summary->total_tax / $summary->taxable_amount) * 100) : 0
        ]);
    }

    /**
     * Export GST sales report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.gst_sales_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business = $this->businessUtil->getDetails($business_id);

        $filters = [
            'location_id' => $request->location_id,
            'customer_id' => $request->customer_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $filename = 'gst_sales_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new GstSalesReportExport($business_id, $filters), $filename);
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

        $customer_id = $request->get('customer_id');
        if (!empty($customer_id)) {
            $query->where('t.contact_id', $customer_id);
        }
    }
}
