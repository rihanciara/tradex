<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\TransactionSellLine;
use App\Transaction;
use App\BusinessLocation;
use App\User;
use DB;
use Carbon\Carbon;

class SalesDetailReportController extends Controller
{
    /**
     * Display sales detail report index page
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $users = User::forDropdown($business_id, false);

        // Convert collections to arrays to avoid htmlspecialchars error
        if (is_object($business_locations)) {
            $business_locations = $business_locations->toArray();
        }

        if (is_object($users)) {
            $users = $users->toArray();
        }

        return view('advancedreports::sales-detail.index')
            ->with(compact('business_locations', 'users'));
    }

    /**
     * Get sales detail data
     */
    public function getSalesDetailData(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Parse date range if provided
        if (!empty($request->date_range)) {
            $date_range = explode(' ~ ', $request->date_range);
            if (count($date_range) == 2) {
                $request->merge([
                    'start_date' => Carbon::createFromFormat('m/d/Y', $date_range[0])->format('Y-m-d'),
                    'end_date' => Carbon::createFromFormat('m/d/Y', $date_range[1])->format('Y-m-d')
                ]);
            }
        }

        // Build comprehensive query
        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftjoin('users as user', 't.created_by', '=', 'user.id')
            ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'transaction_sell_lines.id', '=', 'tspl.sell_line_id')
            ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id');

        // Apply filters
        $this->applyFilters($query, $request, $business_id);

        // Calculate week numbers and day numbers
        $start_date = $request->start_date ?: now()->startOfMonth()->format('Y-m-d');

        $query->select([
            // Product information
            DB::raw("CASE
                WHEN p.type = 'variable'
                THEN CONCAT(p.name, ' - ', pv.name, ' - ', v.name)
                ELSE p.name
            END as product_name"),

            // Transaction information
            't.invoice_no',
            't.transaction_date as sales_date',
            't.id as transaction_id',

            // Week calculation (relative to start date)
            DB::raw("CASE
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 0 AND 6 THEN 1
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 7 AND 13 THEN 2
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 14 AND 20 THEN 3
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 21 AND 27 THEN 4
                WHEN DATEDIFF(t.transaction_date, '$start_date') >= 28 THEN 5
                ELSE 1
            END as week_number"),

            // Day number calculation
            DB::raw("DAYOFWEEK(t.transaction_date) as day_number"),

            // Unit information
            DB::raw("CASE
                WHEN COALESCE(u.short_name, 'unit') LIKE '%(s)'
                THEN COALESCE(u.short_name, 'unit')
                ELSE CONCAT(COALESCE(u.short_name, 'unit'), '(s)')
            END as sales_unit"),

            // Quantities and prices
            DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as qty_sold'),
            'transaction_sell_lines.unit_price_inc_tax as selling_price',

            // Purchase price with fallback
            DB::raw('COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7) as purchase_price'),

            // Calculated totals
            DB::raw('((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) as total_sales_amt'),
            DB::raw('((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7)) as total_purchase_amt'),

            // Staff information
            DB::raw("CASE
                WHEN TRIM(CONCAT(COALESCE(user.first_name, ''), ' ', COALESCE(user.last_name, ''))) != ''
                THEN TRIM(CONCAT(COALESCE(user.first_name, ''), ' ', COALESCE(user.last_name, '')))
                WHEN TRIM(user.username) != ''
                THEN TRIM(user.username)
                ELSE 'Unknown User'
            END as staff"),

            // Additional fields for sorting
            'transaction_sell_lines.id as sell_line_id'
        ]);

        return DataTables::of($query)
            ->editColumn('sales_date', function ($row) {
                return Carbon::parse($row->sales_date)->format('d/m/Y H:i');
            })
            ->addColumn('profit_earned', function ($row) {
                return $row->total_sales_amt - $row->total_purchase_amt;
            })
            ->addColumn('margin_percent', function ($row) {
                $profit = $row->total_sales_amt - $row->total_purchase_amt;
                return $row->total_sales_amt > 0 ?
                    round(($profit / $row->total_sales_amt) * 100, 2) : 0;
            })
            ->orderColumn('sales_date', function ($query, $order) {
                $query->orderBy('t.transaction_date', $order);
            })
            ->make(true);
    }

    /**
     * Get weekly summary totals
     */
    public function getWeeklySummary(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Parse date range if provided
        if (!empty($request->date_range)) {
            $date_range = explode(' ~ ', $request->date_range);
            if (count($date_range) == 2) {
                $request->merge([
                    'start_date' => Carbon::createFromFormat('m/d/Y', $date_range[0])->format('Y-m-d'),
                    'end_date' => Carbon::createFromFormat('m/d/Y', $date_range[1])->format('Y-m-d')
                ]);
            }
        }

        try {
            $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'transaction_sell_lines.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->leftjoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('transaction_sell_lines.parent_sell_line_id');

            // Apply same filters
            $this->applyFilters($query, $request, $business_id);

            $summary = $query->select([
                DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) as total_sales'),
                DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7)) as total_purchase'),
                DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
                DB::raw('SUM(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_qty')
            ])->first();

            $total_profit = $summary->total_sales - $summary->total_purchase;
            $profit_margin = $summary->total_sales > 0 ?
                round(($total_profit / $summary->total_sales) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'totals' => [
                    'total_sales' => round($summary->total_sales ?? 0, 2),
                    'total_purchase' => round($summary->total_purchase ?? 0, 2),
                    'total_profit' => round($total_profit, 2),
                    'profit_margin' => $profit_margin,
                    'total_transactions' => $summary->total_transactions ?? 0,
                    'total_qty' => round($summary->total_qty ?? 0, 2)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'totals' => [
                    'total_sales' => 0,
                    'total_purchase' => 0,
                    'total_profit' => 0,
                    'profit_margin' => 0,
                    'total_transactions' => 0,
                    'total_qty' => 0
                ]
            ]);
        }
    }

/**
 * Apply filters to query
 */
private function applyFilters($query, $request, $business_id)
{
    // Date range filter - updated to handle both old and new format
    $start_date = $request->start_date;
    $end_date = $request->end_date;

    if (!empty($start_date) && !empty($end_date)) {
        $query->whereBetween('t.transaction_date', [$start_date, $end_date]);
    } else {
        // Default to current month if no dates provided
        $query->whereMonth('t.transaction_date', now()->month)
            ->whereYear('t.transaction_date', now()->year);
    }

    // Location filter
    $permitted_locations = auth()->user()->permitted_locations();
    if ($permitted_locations != 'all') {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($request->location_id)) {
        $query->where('t.location_id', $request->location_id);
    }

    // User filter
    if (!empty($request->user_id)) {
        $query->where('t.created_by', $request->user_id);
    }

    // Week filter
    if (!empty($request->week_number)) {
        $filter_start_date = $start_date ?: now()->startOfMonth()->format('Y-m-d');
        $week_start = Carbon::parse($filter_start_date)->addDays(($request->week_number - 1) * 7);
        $week_end = $week_start->copy()->addDays(6);

        $query->whereBetween('t.transaction_date', [
            $week_start->format('Y-m-d'),
            $week_end->format('Y-m-d')
        ]);
    }

    // Product filter (if needed)
    if (!empty($request->product_id)) {
        $query->whereHas('product', function ($q) use ($request) {
            $q->where('id', $request->product_id);
        });
    }
}

    /**
     * Export sales detail report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('advanced_reports.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Implementation for Excel export would go here
        // Similar to ProductReportController export method

        return response()->json([
            'message' => 'Export functionality to be implemented'
        ]);
    }
}