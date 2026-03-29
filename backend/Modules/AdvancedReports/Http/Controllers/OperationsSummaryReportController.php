<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\OperationsSummaryReportExport;
use Maatwebsite\Excel\Facades\Excel;

class OperationsSummaryReportController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('AdvancedReports.operations_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('advancedreports::operations.summary.index')
            ->with(compact('business_locations'));
    }

    /**
     * Get dashboard summary for quick overview - FIXED VERSION
     */
public function getDashboardSummary(Request $request)
{
    if (!auth()->user()->can('AdvancedReports.operations_summary_report')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        $permitted_locations = auth()->user()->permitted_locations();

        // Get date range from request, default to today
        $start_date = $request->get('start_date', \Carbon\Carbon::now()->format('Y-m-d'));
        $end_date = $request->get('end_date', \Carbon\Carbon::now()->format('Y-m-d'));

        \Log::info('Dashboard Summary - Business ID: ' . $business_id . ', Start: ' . $start_date . ', End: ' . $end_date . ', Location: ' . $location_id);

        // Get sales with discounts for the selected period
        $sales_details = $this->getSalesDetailsWithDiscounts($business_id, $start_date, $end_date, $location_id, $permitted_locations);

        // Get purchases for the selected period
        $purchase_details = $this->getPurchaseDetails($business_id, $start_date, $end_date, $location_id, $permitted_locations);

        // Get expenses for the selected period
        $expense_details = $this->getExpenseDetails($business_id, $start_date, $end_date, $location_id);

        // Get cash in hand (this should probably be cumulative, not just for the period)
        $cash_in_hand = $this->getCashInHand($business_id, $location_id);

        // Get total transaction count for the selected period
        $total_transactions = $this->getTransactionCountForPeriod($business_id, $start_date, $end_date, $location_id, $permitted_locations);

        $response_data = [
            'today_sales' => $sales_details['total_sell_inc_tax'] ?? 0,
            'today_purchases' => $purchase_details['total_purchase_inc_tax'] ?? 0,
            'today_expenses' => $expense_details['total_expense'] ?? 0,
            'cash_in_hand' => $cash_in_hand,
            'total_transactions' => $total_transactions,
            'net_profit' => ($sales_details['total_sell_inc_tax'] ?? 0) - ($purchase_details['total_purchase_inc_tax'] ?? 0) - ($expense_details['total_expense'] ?? 0)
        ];

        \Log::info('Dashboard Summary Response: ', $response_data);

        return response()->json($response_data);
    } catch (\Exception $e) {
        \Log::error('Dashboard Summary Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

/**
 * Get transaction count for a specific period - UPDATED METHOD
 */
private function getTransactionCountForPeriod($business_id, $start_date, $end_date, $location_id = null, $permitted_locations = null)
{
    try {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $count = $query->count();
        \Log::info('Transaction Count for period ' . $start_date . ' to ' . $end_date . ': ' . $count);
        
        return $count;
    } catch (\Exception $e) {
        \Log::error('Transaction Count Error: ' . $e->getMessage());
        return 0;
    }
}


    /**
     * Get sales details with proper discount calculations - ENHANCED WITH LOGGING
     */
    private function getSalesDetailsWithDiscounts($business_id, $start_date, $end_date, $location_id = null, $permitted_locations = null)
    {
        try {
            \Log::info('Getting sales details - Business: ' . $business_id . ', Start: ' . $start_date . ', End: ' . $end_date);

            // Base sales query
            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('location_id', $permitted_locations);
                \Log::info('Filtering by permitted locations: ', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
                \Log::info('Filtering by location_id: ' . $location_id);
            }

            // Debug: Check if any sales exist
            $count = $query->count();
            \Log::info('Sales transactions found: ' . $count);

            // Get basic sales data
            $sales = $query->select(
                DB::raw('SUM(final_total) as total_sell_inc_tax'),
                DB::raw('SUM(total_before_tax) as total_sell_exc_tax'),
                DB::raw('SUM(tax_amount) as total_tax'),
                // Calculate INVOICE-level discounts
                DB::raw('SUM(CASE 
                    WHEN discount_type = "percentage" THEN (total_before_tax * discount_amount / 100)
                    ELSE discount_amount 
                END) as invoice_discount'),
                DB::raw('SUM(final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id)) as invoice_due')
            )->first();

            \Log::info('Sales query result: ', $sales ? $sales->toArray() : ['null']);

            // Calculate LINE-level discounts separately
            $line_discount_query = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereDate('t.transaction_date', '>=', $start_date)
                ->whereDate('t.transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $line_discount_query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $line_discount_query->where('t.location_id', $location_id);
            }

            $line_discount_result = $line_discount_query->select([
                DB::raw('SUM(CASE 
                    WHEN tsl.line_discount_type = "percentage" THEN 
                        (tsl.unit_price_before_discount * tsl.quantity * tsl.line_discount_amount / 100)
                    ELSE (tsl.line_discount_amount * tsl.quantity)
                END) as line_discount')
            ])->first();

            $line_discount = $line_discount_result->line_discount ?? 0;
            $invoice_discount = $sales->invoice_discount ?? 0;
            $total_discount = $line_discount + $invoice_discount;

            $result = [
                'total_sell_inc_tax' => $sales->total_sell_inc_tax ?? 0,
                'total_sell_exc_tax' => $sales->total_sell_exc_tax ?? 0,
                'total_tax' => $sales->total_tax ?? 0,
                'line_discount' => $line_discount,
                'invoice_discount' => $invoice_discount,
                'total_discount' => $total_discount,
                'invoice_due' => $sales->invoice_due ?? 0
            ];

            \Log::info('Sales details result: ', $result);
            return $result;

        } catch (\Exception $e) {
            \Log::error('Sales Details Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return [
                'total_sell_inc_tax' => 0,
                'total_sell_exc_tax' => 0,
                'total_tax' => 0,
                'line_discount' => 0,
                'invoice_discount' => 0,
                'total_discount' => 0,
                'invoice_due' => 0
            ];
        }
    }

    /**
     * Get purchase details - ENHANCED WITH LOGGING
     */
    private function getPurchaseDetails($business_id, $start_date, $end_date, $location_id = null, $permitted_locations = null)
    {
        try {
            \Log::info('Getting purchase details - Business: ' . $business_id);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('status', 'received')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $count = $query->count();
            \Log::info('Purchase transactions found: ' . $count);

            $purchases = $query->select(
                DB::raw('SUM(final_total) as total_purchase_inc_tax'),
                DB::raw('SUM(total_before_tax) as total_purchase_exc_tax'),
                DB::raw('SUM(final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id)) as purchase_due')
            )->first();

            $result = [
                'total_purchase_inc_tax' => $purchases->total_purchase_inc_tax ?? 0,
                'total_purchase_exc_tax' => $purchases->total_purchase_exc_tax ?? 0,
                'purchase_due' => $purchases->purchase_due ?? 0
            ];

            \Log::info('Purchase details result: ', $result);
            return $result;

        } catch (\Exception $e) {
            \Log::error('Purchase Details Error: ' . $e->getMessage());
            return [
                'total_purchase_inc_tax' => 0,
                'total_purchase_exc_tax' => 0,
                'purchase_due' => 0
            ];
        }
    }

    /**
     * Get expense details - ENHANCED WITH LOGGING
     */
    private function getExpenseDetails($business_id, $start_date, $end_date, $location_id = null)
    {
        try {
            \Log::info('Getting expense details - Business: ' . $business_id);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $count = $query->count();
            \Log::info('Expense transactions found: ' . $count);

            $expenses = $query->leftJoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->select(
                    'ec.name as category',
                    DB::raw('SUM(final_total) as total_expense')
                )
                ->groupBy('ec.id', 'ec.name')
                ->get();

            $total_expense = $expenses->sum('total_expense');

            $result = [
                'expenses' => $expenses,
                'total_expense' => $total_expense
            ];

            \Log::info('Expense details result - Total: ' . $total_expense);
            return $result;

        } catch (\Exception $e) {
            \Log::error('Expense Details Error: ' . $e->getMessage());
            return [
                'expenses' => collect([]),
                'total_expense' => 0
            ];
        }
    }

    /**
     * Get cash in hand - ENHANCED WITH LOGGING
     */
    private function getCashInHand($business_id, $location_id = null)
    {
        try {
            \Log::info('Getting cash in hand - Business: ' . $business_id);

            $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('transaction_payments.method', 'cash');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $cash_total = $query->sum(DB::raw('IF(transaction_payments.is_return = 1, -1 * transaction_payments.amount, transaction_payments.amount)'));

            \Log::info('Cash in hand: ' . $cash_total);
            return $cash_total ?? 0;

        } catch (\Exception $e) {
            \Log::error('Cash in Hand Error: ' . $e->getMessage());
            return 0;
        }
    }

    // ... rest of your existing methods remain the same ...
    
    public function getSummaryData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.operations_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');
            $permitted_locations = auth()->user()->permitted_locations();

            // Parse date range
            $start_date = \Carbon\Carbon::now()->format('Y-m-d');
            $end_date = \Carbon\Carbon::now()->format('Y-m-d');

            $date_range = $request->input('date_range');
            if (!empty($date_range)) {
                try {
                    if (strpos($date_range, ' - ') !== false) {
                        list($start, $end) = explode(' - ', $date_range);
                        $start_date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($start))->format('Y-m-d');
                        $end_date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($end))->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    \Log::warning('Date parsing failed, using today: ' . $e->getMessage());
                }
            }

            // Get sales details with proper discount calculations
            $sales_details = $this->getSalesDetailsWithDiscounts($business_id, $start_date, $end_date, $location_id, $permitted_locations);

            // Get purchase details
            $purchase_details = $this->getPurchaseDetails($business_id, $start_date, $end_date, $location_id, $permitted_locations);

            // Get expense details
            $expense_details = $this->getExpenseDetails($business_id, $start_date, $end_date, $location_id);

            // Get payment methods breakdown
            $payment_methods = $this->getPaymentMethodsBreakdown($business_id, $start_date, $end_date, $location_id);

            return response()->json([
                'sell_details' => $sales_details,
                'purchase_details' => $purchase_details,
                'expense_details' => $expense_details,
                'payment_methods' => $payment_methods,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        } catch (\Exception $e) {
            \Log::error('Operations Summary Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getPaymentMethodsBreakdown($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $payment_methods = $query->select(
            'method',
            DB::raw('SUM(IF(is_return = 1, -1 * amount, amount)) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->groupBy('method')
            ->get();

        return $payment_methods;
    }

    public function getRegistersData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.operations_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $permitted_locations = auth()->user()->permitted_locations();

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $registers = $this->transactionUtil->registerReport($business_id, $permitted_locations, $start_date, $end_date);

        return response()->json($registers->get());
    }

    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.operations_summary_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business = $this->businessUtil->getDetails($business_id);

        $filters = [
            'location_id' => $request->location_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $filename = 'operations_summary_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new OperationsSummaryReportExport($business_id, $filters), $filename);
    }

  


}