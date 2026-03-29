<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use App\Account;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Modules\AdvancedReports\Exports\DailyReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DailyReportController extends Controller
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
        if (!auth()->user()->can('AdvancedReports.daily_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('advancedreports::daily.report.index')
            ->with(compact('business_locations'));
    }

    /**
     * Get summary widgets data
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_report')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            // Handle date conversion more robustly
            $end_date = $request->input('end_date');
            if (!empty($end_date)) {
                // \Log::info("Original date input: " . $end_date);
                
                try {
                    // Check if already in Y-m-d format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                        $end_date = $end_date;
                    } 
                    // Handle DD/MM/YYYY format (most common)
                    elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
                        $end_date = Carbon::createFromFormat('d/m/Y', $end_date)->format('Y-m-d');
                    }
                    // Handle MM/DD/YYYY format
                    elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
                        $end_date = Carbon::createFromFormat('m/d/Y', $end_date)->format('Y-m-d');
                    }
                    // Handle DD-MM-YYYY format
                    elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $end_date)) {
                        $end_date = Carbon::createFromFormat('d-m-Y', $end_date)->format('Y-m-d');
                    }
                    // Use transaction util as fallback
                    else {
                        $end_date = $this->transactionUtil->uf_date($end_date);
                    }
                    
                    // \Log::info("Converted date: " . $end_date);
                } catch (\Exception $e) {
                    \Log::warning('Date conversion error: ' . $e->getMessage() . ' for input: ' . $end_date);
                    $end_date = Carbon::now()->format('Y-m-d');
                    \Log::info("Using fallback date: " . $end_date);
                }
            } else {
                $end_date = Carbon::now()->format('Y-m-d');
                \Log::info("Using today's date: " . $end_date);
            }
            
            $location_id = !empty($request->input('location_id')) ? $request->input('location_id') : null;

            // Apply location permissions
            $permitted_locations = auth()->user()->permitted_locations();
            
            // \Log::info("=== DAILY REPORT DEBUG START ===");
            // \Log::info("Request parameters", [
            //     'original_end_date' => $request->input('end_date'),
            //     'processed_end_date' => $end_date,
            //     'location_id' => $location_id,
            //     'permitted_locations' => $permitted_locations,
            //     'business_id' => $business_id
            // ]);
            
            // Quick data existence check
            $total_transactions = Transaction::where('business_id', $business_id)->count();
            $transactions_today = Transaction::where('business_id', $business_id)
                ->whereDate('transaction_date', $end_date)
                ->count();
                
            // \Log::info("Data availability check", [
            //     'total_transactions_in_system' => $total_transactions,
            //     'transactions_for_date' => $transactions_today
            // ]);
            
            if ($total_transactions == 0) {
                \Log::warning("No transactions found in system for business_id: $business_id");
                return response()->json([
                    'today_sales' => 0,
                    'today_purchases' => 0,
                    'today_expenses' => 0,
                    'today_profit' => 0,
                    'cash_in_hand' => 0,
                    'bank_balance' => 0,
                    'customer_due' => 0,
                    'supplier_due' => 0,
                    'today_collections' => 0,
                    'net_worth' => 0,
                    'transactions_count' => 0,
                    'new_customers' => 0,
                    'avg_transaction' => 0,
                    'profit_margin' => 0,
                    'opening_balance' => 0,
                    'closing_balance' => 0,
                    'cash_flow' => 0,
                    'liquidity_ratio' => 0,
                    'debug_message' => 'No transactions found in system'
                ]);
            }
            
            if ($transactions_today == 0) {
                // \Log::info("No transactions found for date: $end_date");
                // Check nearby dates
                $yesterday = Carbon::parse($end_date)->subDay()->format('Y-m-d');
                $tomorrow = Carbon::parse($end_date)->addDay()->format('Y-m-d');
                
                $yesterday_count = Transaction::where('business_id', $business_id)
                    ->whereDate('transaction_date', $yesterday)
                    ->count();
                $tomorrow_count = Transaction::where('business_id', $business_id)
                    ->whereDate('transaction_date', $tomorrow)
                    ->count();
                    
                // \Log::info("Nearby dates check", [
                //     'yesterday' => $yesterday,
                //     'yesterday_count' => $yesterday_count,
                //     'tomorrow' => $tomorrow,
                //     'tomorrow_count' => $tomorrow_count
                // ]);
            }
            
   $today_sales = $this->getTodaySales($business_id, $end_date, $location_id, $permitted_locations);
    $today_purchases = $this->getTodayPurchases($business_id, $end_date, $location_id, $permitted_locations);
    $today_expenses = $this->getTodayExpenses($business_id, $end_date, $location_id, $permitted_locations);
    
            // Calculate profit
            $today_profit = $today_sales - $today_purchases - $today_expenses;
            
            // \Log::info("Calculated totals", [
            //     'sales' => $today_sales,
            //     'purchases' => $today_purchases,
            //     'expenses' => $today_expenses,
            //     'profit' => $today_profit
            // ]);
        
            // Cash balances
  $cash_in_hand = $this->getCashInHand($business_id, $end_date, $location_id, $permitted_locations);
    $bank_balance = $this->getBankBalance($business_id, $end_date, $location_id, $permitted_locations);
    
            // Outstanding amounts
            $customer_due = $this->getCustomerDue($business_id, $location_id, $permitted_locations);
            $supplier_due = $this->getSupplierDue($business_id, $location_id, $permitted_locations);
            
            // Today's collections
            $today_collections = $this->getTodayCollections($business_id, $end_date, $location_id, $permitted_locations);
            
            // Net worth calculation
            $net_worth = $cash_in_hand + $bank_balance + $customer_due - $supplier_due;
            
            // Activity metrics
            $transactions_count = $this->getTodayTransactionsCount($business_id, $end_date, $location_id, $permitted_locations);
            $new_customers = $this->getNewCustomersCount($business_id, $end_date);
            
            // Additional metrics
            $avg_transaction = $transactions_count > 0 ? $today_sales / $transactions_count : 0;
            $profit_margin = $today_sales > 0 ? ($today_profit / $today_sales) * 100 : 0;
            
            // Opening and closing balance
            $opening_balance = $this->getOpeningBalance($business_id, $end_date, $location_id, $permitted_locations);
            $closing_balance = $cash_in_hand + $bank_balance;
            $cash_flow = $closing_balance - $opening_balance;
            
            // Liquidity ratio
            $liquidity_ratio = $supplier_due > 0 ? ($cash_in_hand + $bank_balance) / $supplier_due : 0;

            \Log::info("=== DAILY REPORT DEBUG END ===");

            return response()->json([
                'today_sales' => $today_sales,
                'today_purchases' => $today_purchases,
                'today_expenses' => $today_expenses,
                'today_profit' => $today_profit,
                'cash_in_hand' => $cash_in_hand,
                'bank_balance' => $bank_balance,
                'customer_due' => $customer_due,
                'supplier_due' => $supplier_due,
                'today_collections' => $today_collections,
                'net_worth' => $net_worth,
                'transactions_count' => $transactions_count,
                'new_customers' => $new_customers,
                'avg_transaction' => $avg_transaction,
                'profit_margin' => $profit_margin,
                'opening_balance' => $opening_balance,
                'closing_balance' => $closing_balance,
                'cash_flow' => $cash_flow,
                'liquidity_ratio' => $liquidity_ratio,
                'debug_info' => [
                    'processed_date' => $end_date,
                    'total_system_transactions' => $total_transactions ?? 0,
                    'transactions_for_date' => $transactions_today ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Daily Report Summary Exception: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
        
            // Return default values on error
            return response()->json([
                'today_sales' => 0,
                'today_purchases' => 0,
                'today_expenses' => 0,
                'today_profit' => 0,
                'cash_in_hand' => 0,
                'bank_balance' => 0,
                'customer_due' => 0,
                'supplier_due' => 0,
                'today_collections' => 0,
                'net_worth' => 0,
                'transactions_count' => 0,
                'new_customers' => 0,
                'avg_transaction' => 0,
                'profit_margin' => 0,
                'opening_balance' => 0,
                'closing_balance' => 0,
                'cash_flow' => 0,
                'liquidity_ratio' => 0
            ]);
        }
    }

    /**
     * Get detailed breakdown data
     */
public function getDailyReportData(Request $request)
{
    if (!auth()->user()->can('AdvancedReports.daily_report')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        
        // === FIXED DATE HANDLING ===
        $end_date = $request->input('end_date');
        \Log::info("Original end_date from request: " . $end_date);
        
        if (!empty($end_date)) {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                    // Already in Y-m-d format
                    $end_date = $end_date;
                    \Log::info("Date already in Y-m-d format: " . $end_date);
                } else {
                    // Handle DD/MM/YYYY format manually to avoid confusion
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $end_date, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $year = $matches[3];
                        $end_date = "{$year}-{$month}-{$day}";
                        \Log::info("Date converted manually from DD/MM/YYYY to: " . $end_date);
                    } else {
                        // Fallback to transaction util
                        $original_date = $end_date;
                        $end_date = $this->transactionUtil->uf_date($end_date);
                        \Log::info("Date converted via transactionUtil from '{$original_date}' to '{$end_date}'");
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Date conversion error: ' . $e->getMessage());
                $end_date = Carbon::now()->format('Y-m-d');
                \Log::info("Fallback date: " . $end_date);
            }
        } else {
            $end_date = Carbon::now()->format('Y-m-d');
            \Log::info("Using today's date: " . $end_date);
        }
        
        $location_id = !empty($request->input('location_id')) ? $request->input('location_id') : null;
        $permitted_locations = auth()->user()->permitted_locations();

        \Log::info("Final parameters - Date: {$end_date}, Location: " . ($location_id ?? 'null') . ", Business: {$business_id}");

        // Get detailed sales breakdown
        $sales_breakdown = $this->getDetailedSalesBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        
        // Get detailed purchase breakdown
        $purchase_breakdown = $this->getDetailedPurchaseBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        
        // Get detailed expense breakdown
        $expense_breakdown = $this->getDetailedExpenseBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        
        // Get payment breakdown
        $payment_breakdown = $this->getPaymentBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        
        // Get top products
        $top_products = $this->getTopProducts($business_id, $end_date, $location_id, $permitted_locations);
        
        // Get payment method analysis
        $payment_methods = $this->getPaymentMethodAnalysis($business_id, $end_date, $location_id, $permitted_locations);

          // ADD THIS LINE - Get monthly cash breakdown
        $monthly_cash_breakdown = $this->getMonthlyCashBreakdown($business_id, $end_date, $location_id, $permitted_locations);

        $response_data = [
            'sales_breakdown' => $sales_breakdown,
            'purchase_breakdown' => $purchase_breakdown,
            'expense_breakdown' => $expense_breakdown,
            'payment_breakdown' => $payment_breakdown,
            'top_products' => $top_products,
            'payment_methods' => $payment_methods,
            'monthly_cash_breakdown' => $monthly_cash_breakdown, // ADD THIS LINE
            'debug_info' => [
                'date_used' => $end_date,
                'location_used' => $location_id,
                'business_id' => $business_id,
                'permitted_locations' => $permitted_locations
            ]
        ];
        
        return response()->json($response_data);
        
    } catch (\Exception $e) {
        \Log::error('Daily Report Data Error: ' . $e->getMessage());
        return response()->json(['error' => 'Error loading detailed data'], 500);
    }
}

/**
 * OPTIONAL: Separate endpoint for just monthly breakdown
 */
public function getMonthlyCashBreakdownData(Request $request)
{
    if (!auth()->user()->can('AdvancedReports.daily_report')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $end_date = $request->input('end_date');
        
        // Handle date conversion (same logic as other methods)
        if (!empty($end_date)) {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                    $end_date = $end_date;
                } else {
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $end_date, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $year = $matches[3];
                        $end_date = "{$year}-{$month}-{$day}";
                    } else {
                        $end_date = $this->transactionUtil->uf_date($end_date);
                    }
                }
            } catch (\Exception $e) {
                $end_date = Carbon::now()->format('Y-m-d');
            }
        } else {
            $end_date = Carbon::now()->format('Y-m-d');
        }
        
        $location_id = $request->input('location_id');
        $permitted_locations = auth()->user()->permitted_locations();
        
        $monthly_breakdown = $this->getMonthlyCashBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        
        return response()->json([
            'monthly_cash_breakdown' => $monthly_breakdown,
            'date_used' => $end_date
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Monthly Cash Breakdown Error: ' . $e->getMessage());
        return response()->json(['error' => 'Error loading monthly breakdown'], 500);
    }
}

    /**
     * Get today's sales
     */
    private function getTodaySales($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $result = $query->sum('final_total') ?? 0;
        \Log::info("Today's sales for date $date: $result");
        return $result;
    }

    /**
     * Get today's purchases
     */
    private function getTodayPurchases($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->whereIn('status', ['received', 'ordered']) // Include both statuses
            ->whereDate('transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $result = $query->sum('final_total') ?? 0;
        \Log::info("Today's purchases for date $date: $result");
        return $result;
    }

    /**
     * Get today's expenses
     */
    private function getTodayExpenses($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $result = $query->sum('final_total') ?? 0;
        \Log::info("Today's expenses for date $date: $result");
        return $result;
    }

    /**
     * Get cash in hand
     */
private function getCashInHand($business_id, $date, $location_id = null, $permitted_locations = null)
{
    \Log::info("=== CALCULATING CASH IN HAND FOR DATE: {$date} ===");
    
    $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        ->whereDate('transaction_payments.paid_on', '<=', $date); // ✅ ADD THIS LINE

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('t.location_id', $location_id);
    }

    // Enhanced calculation with proper transaction type handling
    $result = $query->sum(DB::raw('
        CASE 
            WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount
            WHEN t.type = "sell" THEN transaction_payments.amount
            WHEN t.type = "purchase" THEN -1 * transaction_payments.amount  
            WHEN t.type = "expense" THEN -1 * transaction_payments.amount
            ELSE transaction_payments.amount
        END
    ')) ?? 0;

    \Log::info("Cash in hand calculated for {$date}: {$result}");
    return $result;
}

    /**
     * Get bank balance
     */
private function getBankBalance($business_id, $date, $location_id = null, $permitted_locations = null)
{
    \Log::info("=== CALCULATING BANK BALANCE FOR DATE: {$date} ===");
    
    $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->whereIn('transaction_payments.method', ['bank_transfer', 'card', 'cheque'])
        ->whereDate('transaction_payments.paid_on', '<=', $date); // ✅ ADD THIS LINE

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('t.location_id', $location_id);
    }

    $result = $query->sum(DB::raw('
        CASE 
            WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount
            WHEN t.type = "sell" THEN transaction_payments.amount
            WHEN t.type = "purchase" THEN -1 * transaction_payments.amount
            WHEN t.type = "expense" THEN -1 * transaction_payments.amount
            ELSE transaction_payments.amount
        END
    ')) ?? 0;

    \Log::info("Bank balance calculated for {$date}: {$result}");
    return $result;
}
private function getDetailedCashBalance($business_id, $date, $location_id = null, $permitted_locations = null)
{
    \Log::info("=== DETAILED CASH BALANCE CALCULATION ===");
    
    $base_query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        ->whereDate('transaction_payments.paid_on', '<=', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $base_query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $base_query->where('t.location_id', $location_id);
    }

    // Sales receipts (cash inflow)
    $sales_cash = (clone $base_query)
        ->where('t.type', 'sell')
        ->where('transaction_payments.is_return', 0)
        ->sum('transaction_payments.amount') ?? 0;

    // Sales returns (cash outflow)
    $sales_returns_cash = (clone $base_query)
        ->where('t.type', 'sell')
        ->where('transaction_payments.is_return', 1)
        ->sum('transaction_payments.amount') ?? 0;

    // Purchase payments (cash outflow)
    $purchase_cash = (clone $base_query)
        ->where('t.type', 'purchase')
        ->where('transaction_payments.is_return', 0)
        ->sum('transaction_payments.amount') ?? 0;

    // Purchase returns received (cash inflow)
    $purchase_returns_cash = (clone $base_query)
        ->where('t.type', 'purchase')
        ->where('transaction_payments.is_return', 1)
        ->sum('transaction_payments.amount') ?? 0;

    // Expense payments (cash outflow)
    $expense_cash = (clone $base_query)
        ->where('t.type', 'expense')
        ->sum('transaction_payments.amount') ?? 0;

    $total_cash = $sales_cash - $sales_returns_cash - $purchase_cash + $purchase_returns_cash - $expense_cash;

    \Log::info("Cash breakdown:", [
        'sales_cash' => $sales_cash,
        'sales_returns_cash' => $sales_returns_cash,
        'purchase_cash' => $purchase_cash,
        'purchase_returns_cash' => $purchase_returns_cash,
        'expense_cash' => $expense_cash,
        'total_cash' => $total_cash
    ]);

    return $total_cash;
}
    /**
     * Get customer due amount
     */
    private function getCustomerDue($business_id, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereIn('payment_status', ['due', 'partial']);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        return $query->sum(DB::raw('final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id)')) ?? 0;
    }

    /**
     * Get supplier due amount
     */
    private function getSupplierDue($business_id, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->whereIn('payment_status', ['due', 'partial']);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        return $query->sum(DB::raw('final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id)')) ?? 0;
    }

    /**
     * Get today's collections
     */
    private function getTodayCollections($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->whereDate('transaction_payments.paid_on', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $result = $query->sum(DB::raw('IF(transaction_payments.is_return = 1, -1 * transaction_payments.amount, transaction_payments.amount)')) ?? 0;
        \Log::info("Today's collections for date $date: $result");
        return $result;
    }

    /**
     * Get today's transactions count
     */
    private function getTodayTransactionsCount($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'purchase'])
            ->whereIn('status', ['final', 'received', 'ordered'])
            ->whereDate('transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        return $query->count();
    }

    /**
     * Get new customers count for today
     */
    private function getNewCustomersCount($business_id, $date)
    {
        return Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * Get opening balance - simplified calculation
     */
    private function getOpeningBalance($business_id, $date, $location_id = null, $permitted_locations = null)
{
    // Get cash and bank balance as of end of previous day
    $previous_date = Carbon::parse($date)->subDay()->format('Y-m-d');
    
    \Log::info("Calculating opening balance for date {$date}, using previous date: {$previous_date}");
    
    // Get cash balance up to previous day
   $cash_balance = $this->getCashInHand($business_id, $previous_date, $location_id, $permitted_locations);
    $bank_balance = $this->getBankBalance($business_id, $previous_date, $location_id, $permitted_locations);

    
    // \Log::info("Opening balance calculation:", [
    //     'previous_date' => $previous_date,
    //     'cash_balance' => $cash_balance,
    //     'bank_balance' => $bank_balance,
    //     'total_opening_balance' => $opening_balance
    // ]);
    
        return $cash_balance + $bank_balance;

}

    /**
     * Get detailed sales breakdown
     */
private function getDetailedSalesBreakdown($business_id, $date, $location_id = null, $permitted_locations = null)
{
    \Log::info("=== SALES BREAKDOWN METHOD START ===");
    \Log::info("Parameters - Business ID: {$business_id}, Date: {$date}, Location: " . ($location_id ?? 'null'));
    
    // === STEP 1: BUILD BASE QUERY ===
    $query = Transaction::where('business_id', $business_id)
        ->where('type', 'sell')
        ->where('status', 'final')
        ->whereDate('transaction_date', $date);
    
    \Log::info("Base query built for business {$business_id}, date {$date}");

    // === STEP 2: APPLY LOCATION FILTERS ===
    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('location_id', $permitted_locations);
        \Log::info("Applied permitted locations filter: " . implode(',', $permitted_locations));
    }

    if (!empty($location_id)) {
        $query->where('location_id', $location_id);
        \Log::info("Applied specific location filter: {$location_id}");
    }

    // === STEP 3: DEBUG QUERY ===
    \Log::info("Final SQL query: " . $query->toSql());
    \Log::info("Query bindings: " . json_encode($query->getBindings()));
    
    // === STEP 4: CHECK IF ANY TRANSACTIONS EXIST ===
    $transaction_count = $query->count();
    \Log::info("Total transactions found: {$transaction_count}");
    
    if ($transaction_count == 0) {
        \Log::warning("NO TRANSACTIONS FOUND!");
        
        // Let's check if there are ANY transactions for this business
        $any_transactions = Transaction::where('business_id', $business_id)->count();
        \Log::info("Total transactions in business: {$any_transactions}");
        
        // Check transactions for today without filters
        $today_transactions = Transaction::where('business_id', $business_id)
            ->whereDate('transaction_date', $date)
            ->count();
        \Log::info("Transactions for date {$date} (no filters): {$today_transactions}");
        
        // Check sell transactions specifically
        $sell_transactions = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', $date)
            ->count();
        \Log::info("Sell transactions for date {$date}: {$sell_transactions}");
        
        // Check final status transactions
        $final_transactions = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->count();
        \Log::info("Final sell transactions for date {$date}: {$final_transactions}");
    }

    // === STEP 5: EXECUTE AGGREGATION QUERY ===
    $result = $query->select([
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(total_before_tax) as subtotal'),
        DB::raw('SUM(tax_amount) as tax'),
        DB::raw('SUM(discount_amount) as discount'),
        DB::raw('SUM(final_total) as total'),
        DB::raw('SUM(CASE WHEN payment_status = "paid" THEN final_total ELSE 0 END) as paid_amount'),
        DB::raw('SUM(CASE WHEN payment_status = "due" THEN final_total ELSE 0 END) as due_amount'),
        DB::raw('SUM(CASE WHEN payment_status = "partial" THEN final_total ELSE 0 END) as partial_amount')
    ])->first();

    \Log::info("Raw SQL result: " . json_encode($result));
    
    // === STEP 6: PROCESS RESULT ===
    if (!$result) {
        \Log::warning("Query returned null result");
        $result = (object)[
            'count' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'due_amount' => 0,
            'partial_amount' => 0
        ];
    } else {
        // Convert string values to numbers
        $result->count = (int)$result->count;
        $result->subtotal = (float)($result->subtotal ?? 0);
        $result->tax = (float)($result->tax ?? 0);
        $result->discount = (float)($result->discount ?? 0);
        $result->total = (float)($result->total ?? 0);
        $result->paid_amount = (float)($result->paid_amount ?? 0);
        $result->due_amount = (float)($result->due_amount ?? 0);
        $result->partial_amount = (float)($result->partial_amount ?? 0);
    }
    
    \Log::info("Final processed result: " . json_encode($result));
    \Log::info("=== SALES BREAKDOWN METHOD END ===");
    
    return $result;
}

public function testDataExists(Request $request)
{
    try {
        $business_id = $request->session()->get('user.business_id');
        $today = Carbon::now()->format('Y-m-d');
        
        \Log::info("=== TESTING DATA EXISTENCE ===");
        \Log::info("Business ID: {$business_id}");
        \Log::info("Test Date: {$today}");
        
        // Check basic data existence
        $total_transactions = Transaction::where('business_id', $business_id)->count();
        $recent_transactions = Transaction::where('business_id', $business_id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
        
        $today_sales = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', $today)
            ->count();
            
        $today_all_transactions = Transaction::where('business_id', $business_id)
            ->whereDate('transaction_date', $today)
            ->count();
            
        // Get sample transaction for structure inspection
        $sample_transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->latest()
            ->first();
            
        $result = [
            'business_id' => $business_id,
            'test_date' => $today,
            'total_transactions_in_business' => $total_transactions,
            'recent_transactions_30_days' => $recent_transactions,
            'today_all_transactions' => $today_all_transactions,
            'today_sales_count' => $today_sales,
            'sample_transaction' => $sample_transaction ? [
                'id' => $sample_transaction->id,
                'type' => $sample_transaction->type,
                'status' => $sample_transaction->status,
                'transaction_date' => $sample_transaction->transaction_date,
                'final_total' => $sample_transaction->final_total,
                'payment_status' => $sample_transaction->payment_status,
                'location_id' => $sample_transaction->location_id
            ] : null
        ];
        
        \Log::info("Test results: " . json_encode($result));
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        \Log::error('Test data error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    /**
     * Get detailed purchase breakdown
     */
    private function getDetailedPurchaseBreakdown($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->whereDate('transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        return $query->select([
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_before_tax) as subtotal'),
            DB::raw('SUM(tax_amount) as tax'),
            DB::raw('SUM(final_total) as total')
        ])->first();
    }

    /**
     * Get detailed expense breakdown
     */
    private function getDetailedExpenseBreakdown($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = Transaction::leftJoin('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'expense')
            ->whereDate('transactions.transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        return $query->select([
            'expense_categories.name as category',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(transactions.final_total) as total')
        ])
        ->groupBy('expense_categories.id', 'expense_categories.name')
        ->get();
    }

    /**
     * Get payment breakdown
     */
    private function getPaymentBreakdown($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->whereDate('transaction_payments.paid_on', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        return $query->select([
            'transaction_payments.method',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(transaction_payments.amount) as total')
        ])
        ->groupBy('transaction_payments.method')
        ->get();
    }

    /**
     * Get top products
     */
    private function getTopProducts($business_id, $date, $location_id = null, $permitted_locations = null, $limit = 10)
    {
        $query = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        return $query->select([
            'products.name',
            DB::raw('SUM(transaction_sell_lines.quantity) as quantity_sold'),
            DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as total_amount')
        ])
        ->groupBy('products.id', 'products.name')
        ->orderBy('total_amount', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Get payment method analysis
     */
    private function getPaymentMethodAnalysis($business_id, $date, $location_id = null, $permitted_locations = null)
    {
        $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->whereDate('transaction_payments.paid_on', $date);

        if ($permitted_locations != 'all' && is_array($permitted_locations)) {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $methods = $query->select([
            'transaction_payments.method',
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(transaction_payments.amount) as total_amount'),
            DB::raw('AVG(transaction_payments.amount) as avg_amount')
        ])
        ->groupBy('transaction_payments.method')
        ->get();

        $total_amount = $methods->sum('total_amount');
        
        return $methods->map(function ($method) use ($total_amount) {
            $method->percentage = $total_amount > 0 ? ($method->total_amount / $total_amount) * 100 : 0;
            return $method;
        });
    }

    /**
     * Debug method to check what data exists
     */
    private function debugTransactionData($business_id, $date)
    {
        \Log::info("=== DEBUG TRANSACTION DATA ===");
        
        // Check all transactions for the date
        $all_transactions = Transaction::where('business_id', $business_id)
            ->whereDate('transaction_date', $date)
            ->select('type', 'status', 'final_total', 'transaction_date', 'location_id')
            ->get();
            
        \Log::info("All transactions for date $date:", $all_transactions->toArray());
        
        // Check sales specifically
        $sales = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', $date)
            ->get(['status', 'final_total', 'payment_status']);
            
        \Log::info("Sales transactions:", $sales->toArray());
        
        // Check purchases specifically  
        $purchases = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->whereDate('transaction_date', $date)
            ->get(['status', 'final_total']);
            
        \Log::info("Purchase transactions:", $purchases->toArray());
        
        // Check expenses specifically
        $expenses = Transaction::where('business_id', $business_id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', $date)
            ->get(['final_total']);
            
        \Log::info("Expense transactions:", $expenses->toArray());
        
        // Check payments for today
        $payments = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->whereDate('transaction_payments.paid_on', $date)
            ->get(['method', 'amount', 'paid_on']);
            
        \Log::info("Payments for date $date:", $payments->toArray());
        
        \Log::info("=== END DEBUG ===");
    }

    /**
     * Debug detailed data - for troubleshooting breakdown sections
     */
    public function debugDetailedData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $end_date = $request->input('end_date');
        
        // Convert date
        if (!empty($end_date)) {
            try {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
                    $end_date = Carbon::createFromFormat('d/m/Y', $end_date)->format('Y-m-d');
                } else {
                    $end_date = $this->transactionUtil->uf_date($end_date);
                }
            } catch (\Exception $e) {
                $end_date = Carbon::now()->format('Y-m-d');
            }
        } else {
            $end_date = Carbon::now()->format('Y-m-d');
        }
        
        $location_id = $request->input('location_id');
        $permitted_locations = auth()->user()->permitted_locations();
        
        // Test each detailed query individually
        $debug_results = [
            'date_used' => $end_date,
            'location_id' => $location_id,
            'permitted_locations' => $permitted_locations
        ];
        
        // Test sales breakdown
        try {
            $sales_query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', $end_date);
                
            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $sales_query->whereIn('location_id', $permitted_locations);
            }
            if (!empty($location_id)) {
                $sales_query->where('location_id', $location_id);
            }
            
            $debug_results['sales_raw_count'] = $sales_query->count();
            $debug_results['sales_raw_sum'] = $sales_query->sum('final_total');
            $debug_results['sales_breakdown'] = $this->getDetailedSalesBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        } catch (\Exception $e) {
            $debug_results['sales_error'] = $e->getMessage();
        }
        
        // Test purchase breakdown
        try {
            $purchase_query = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('status', 'received')
                ->whereDate('transaction_date', $end_date);
                
            if ($permitted_locations != 'all' && is_array($permitted_locations)) {
                $purchase_query->whereIn('location_id', $permitted_locations);
            }
            if (!empty($location_id)) {
                $purchase_query->where('location_id', $location_id);
            }
            
            $debug_results['purchase_raw_count'] = $purchase_query->count();
            $debug_results['purchase_breakdown'] = $this->getDetailedPurchaseBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        } catch (\Exception $e) {
            $debug_results['purchase_error'] = $e->getMessage();
        }
        
        // Test top products
        try {
            $products_query = DB::table('transaction_sell_lines')
                ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereDate('transactions.transaction_date', $end_date);
                
            $debug_results['products_raw_count'] = $products_query->count();
            $debug_results['top_products'] = $this->getTopProducts($business_id, $end_date, $location_id, $permitted_locations, 5);
        } catch (\Exception $e) {
            $debug_results['products_error'] = $e->getMessage();
        }
        
        // Test payment methods
        try {
            $payment_query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->whereDate('transaction_payments.paid_on', $end_date);
                
            $debug_results['payments_raw_count'] = $payment_query->count();
            $debug_results['payment_methods'] = $this->getPaymentMethodAnalysis($business_id, $end_date, $location_id, $permitted_locations);
        } catch (\Exception $e) {
            $debug_results['payments_error'] = $e->getMessage();
        }
        
        // Test expenses
        try {
            $expense_query = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->whereDate('transaction_date', $end_date);
                
            $debug_results['expenses_raw_count'] = $expense_query->count();
            $debug_results['expense_breakdown'] = $this->getDetailedExpenseBreakdown($business_id, $end_date, $location_id, $permitted_locations);
        } catch (\Exception $e) {
            $debug_results['expenses_error'] = $e->getMessage();
        }
        
        return response()->json($debug_results);
    }



    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.daily_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $business = $this->businessUtil->getDetails($business_id);

        $filters = [
            'location_id' => $request->location_id,
            'end_date' => $request->end_date,
        ];

        $filename = 'daily_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new DailyReportExport($business_id, $filters), $filename);
    }


    /**
 * ADDITIONAL: Method to get account-wise balances if you have multiple bank accounts
 */
private function getAccountWiseBalances($business_id, $date, $location_id = null, $permitted_locations = null)
{
    $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->leftJoin('accounts as acc', 'transaction_payments.account_id', '=', 'acc.id')
        ->where('t.business_id', $business_id)
        ->whereDate('transaction_payments.paid_on', '<=', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('t.location_id', $location_id);
    }

    return $query->select([
        'transaction_payments.method',
        'acc.name as account_name',
        DB::raw('SUM(CASE 
            WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount
            WHEN t.type = "sell" THEN transaction_payments.amount
            WHEN t.type = "purchase" THEN -1 * transaction_payments.amount
            WHEN t.type = "expense" THEN -1 * transaction_payments.amount
            ELSE transaction_payments.amount
        END) as balance')
    ])
    ->groupBy('transaction_payments.method', 'acc.id', 'acc.name')
    ->get();
}

/**
 * DEBUGGING: Method to verify balance calculations
 */
public function verifyBalanceCalculations(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $date = $request->input('date', Carbon::now()->format('Y-m-d'));
    
    // Get current calculation
    $current_cash = $this->getCashInHand($business_id, $date);
    $current_bank = $this->getBankBalance($business_id, $date);
    
    // Get detailed breakdown
    $detailed_cash = $this->getDetailedCashBalance($business_id, $date);
    
    // Get raw payment data for verification
    $raw_payments = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->whereDate('transaction_payments.paid_on', '<=', $date)
        ->select([
            't.type',
            'transaction_payments.method',
            'transaction_payments.amount',
            'transaction_payments.is_return',
            'transaction_payments.paid_on'
        ])
        ->orderBy('transaction_payments.paid_on')
        ->get();
    
    return response()->json([
        'date' => $date,
        'current_cash_calculation' => $current_cash,
        'current_bank_calculation' => $current_bank,
        'detailed_cash_calculation' => $detailed_cash,
        'raw_payments_count' => $raw_payments->count(),
        'sample_payments' => $raw_payments->take(10),
        'payment_method_summary' => $raw_payments->groupBy('method')->map(function($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ];
        })
    ]);
}

/**
 * Get monthly cash flow breakdown for the report
 */
private function getMonthlyCashBreakdown($business_id, $date, $location_id = null, $permitted_locations = null)
{
    \Log::info("=== GETTING MONTHLY CASH BREAKDOWN UP TO DATE: {$date} ===");
    
    $query = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        ->whereDate('transaction_payments.paid_on', '<=', $date);

    if ($permitted_locations != 'all' && is_array($permitted_locations)) {
        $query->whereIn('t.location_id', $permitted_locations);
    }

    if (!empty($location_id)) {
        $query->where('t.location_id', $location_id);
    }

    $monthly_data = $query->select([
        DB::raw('DATE_FORMAT(transaction_payments.paid_on, "%Y-%m") as month'),
        DB::raw('DATE_FORMAT(transaction_payments.paid_on, "%M %Y") as month_name'),
        DB::raw('COUNT(*) as transaction_count'),
        DB::raw('SUM(CASE 
            WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount
            WHEN t.type = "sell" THEN transaction_payments.amount
            WHEN t.type = "purchase" THEN -1 * transaction_payments.amount
            WHEN t.type = "expense" THEN -1 * transaction_payments.amount
            ELSE transaction_payments.amount
        END) as net_cash_flow')
    ])
    ->groupBy('month', 'month_name')
    ->orderBy('month', 'desc')
    ->limit(6) // Get last 6 months
    ->get();

    // Calculate running total
    $running_total = 0;
    $breakdown = [];
    
    // Reverse to calculate running total from oldest to newest
    $reversed_data = $monthly_data->reverse();
    
    foreach ($reversed_data as $month_data) {
        $running_total += $month_data->net_cash_flow;
        
        $breakdown[] = [
            'month' => $month_data->month,
            'month_name' => $month_data->month_name,
            'transaction_count' => (int)$month_data->transaction_count,
            'net_cash_flow' => (float)$month_data->net_cash_flow,
            'running_total' => $running_total
        ];
    }
    
    // Reverse back to show newest first
    $breakdown = array_reverse($breakdown);
    
    \Log::info("Monthly cash breakdown calculated:", $breakdown);
    
    return $breakdown;
}

/**
 * DEBUG METHOD: Check what the difference would be
 */
public function debugCashCalculations(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $date = $request->input('date', '2025-08-16');
    
    // Current calculation (wrong)
    $current_cash = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        // NO date filter
        ->sum(DB::raw('IF(transaction_payments.is_return = 1, -1 * transaction_payments.amount, transaction_payments.amount)'));
    
    // Corrected calculation (right)
    $corrected_cash = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        ->whereDate('transaction_payments.paid_on', '<=', $date) // WITH date filter
        ->sum(DB::raw('
            CASE 
                WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount
                WHEN t.type = "sell" THEN transaction_payments.amount
                WHEN t.type = "purchase" THEN -1 * transaction_payments.amount
                WHEN t.type = "expense" THEN -1 * transaction_payments.amount
                ELSE transaction_payments.amount
            END
        '));
    
    // Show breakdown by date
    $daily_breakdown = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.method', 'cash')
        ->selectRaw('
            DATE(transaction_payments.paid_on) as payment_date,
            t.type as transaction_type,
            COUNT(*) as count,
            SUM(transaction_payments.amount) as total_amount
        ')
        ->groupBy('payment_date', 't.type')
        ->orderBy('payment_date', 'desc')
        ->limit(10)
        ->get();
    
    return response()->json([
        'target_date' => $date,
        'current_calculation' => $current_cash,
        'corrected_calculation' => $corrected_cash,
        'difference' => $current_cash - $corrected_cash,
        'daily_breakdown' => $daily_breakdown,
        'explanation' => "Current shows $current_cash (all time), Corrected shows $corrected_cash (up to $date)"
    ]);
}
}