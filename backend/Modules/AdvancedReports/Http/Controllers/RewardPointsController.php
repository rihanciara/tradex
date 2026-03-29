<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Business;
use App\Contact;
use App\Transaction;
use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdvancedReports\Exports\RewardPointsExport;

class RewardPointsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            return view('advancedreports::reward-points.index', [
                'reward_points_not_enabled' => true,
                'customers' => [],
                'min_redeem_point' => 0,
                'max_redeem_point' => 0
            ]);
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get business settings for reward points calculation
        $business = Business::find($business_id);
        $min_redeem_point = $business->min_redeem_point ?? 1;
        $max_redeem_point = $business->max_redeem_point ?? 1000;

        // Get all customers for filtering
        $customers = Contact::where('business_id', $business_id)
                           ->where('type', 'customer')
                           ->pluck('name', 'id');

        return view('advancedreports::reward-points.index', compact(
            'customers',
            'min_redeem_point',
            'max_redeem_point'
        ));
    }

    public function getSummaryData(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            return response()->json(['error' => 'Reward points module is not enabled'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            // Calculate total points issued (based on sales transactions)
            $totalPointsIssued = $this->calculateTotalPointsIssued($business_id, $start_date, $end_date);
            
            // Calculate total points redeemed
            $totalPointsRedeemed = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start_date, $end_date])
                ->sum('rp_redeemed');

            // Calculate outstanding liability
            $outstandingLiability = $totalPointsIssued - $totalPointsRedeemed;
            
            // Liability amount in BDT (assuming 1 point = 1 BDT)
            $liabilityAmountBdt = $outstandingLiability * 1.0;

            // Count active customers with points
            $activeCustomersWithPoints = $this->countActiveCustomersWithPoints($business_id);

            // Calculate redemption rate
            $redemptionRate = $totalPointsIssued > 0 ? round(($totalPointsRedeemed / $totalPointsIssued) * 100, 2) : 0;

            // Average points per customer
            $avgPointsPerCustomer = $activeCustomersWithPoints > 0 ? 
                round($outstandingLiability / $activeCustomersWithPoints, 0) : 0;

            // Points redeemed this month
            $pointsRedeemedThisMonth = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                    Carbon::now()->endOfMonth()->format('Y-m-d')
                ])
                ->sum('rp_redeemed');

            return response()->json([
                'total_points_issued' => $totalPointsIssued,
                'total_points_redeemed' => $totalPointsRedeemed,
                'outstanding_liability' => $outstandingLiability,
                'liability_amount_bdt' => number_format($liabilityAmountBdt, 2),
                'active_customers_with_points' => $activeCustomersWithPoints,
                'redemption_rate' => $redemptionRate,
                'avg_points_per_customer' => $avgPointsPerCustomer,
                'points_redeemed_this_month' => $pointsRedeemedThisMonth
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load summary data: ' . $e->getMessage()], 500);
        }
    }

    public function getCustomerSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            return response()->json(['error' => 'Reward points module is not enabled'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $customer_id = $request->get('customer_id');

        try {
            $query = DB::table('contacts as c')
                ->select([
                    'c.id as customer_id',
                    'c.name as customer_name',
                    'c.mobile as customer_mobile',
                    'c.created_at as customer_registered_date',
                    // Use contacts table for authoritative balances
                    'c.total_rp as current_balance',
                    'c.total_rp_used as total_redeemed_points',
                    'c.total_rp_expired as total_expired_points',
                    DB::raw('(c.total_rp + c.total_rp_used + c.total_rp_expired) as total_earned_points'),
                    // Use transactions for period-specific and activity data
                    DB::raw('COALESCE(period_earned.period_earned, 0) as period_earned_points'),
                    DB::raw('COALESCE(period_redeemed.period_redeemed, 0) as period_redeemed_points'),
                    DB::raw('COALESCE(period_earned.transaction_count, 0) as total_transactions'),
                    DB::raw('COALESCE(period_redeemed.redemption_count, 0) as redemption_transactions'),
                    DB::raw('COALESCE(period_earned.first_purchase, NULL) as first_earning_date'),
                    DB::raw('COALESCE(period_earned.last_purchase, NULL) as last_activity_date')
                ])
                ->leftJoin(DB::raw('(
                    SELECT 
                        contact_id,
                        SUM(rp_earned) as period_earned,
                        COUNT(*) as transaction_count,
                        MIN(transaction_date) as first_purchase,
                        MAX(transaction_date) as last_purchase
                    FROM transactions 
                    WHERE business_id = ' . $business_id . ' 
                        AND type = "sell" 
                        AND status = "final"
                        AND contact_id IS NOT NULL
                        AND transaction_date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                        AND rp_earned > 0
                    GROUP BY contact_id
                ) as period_earned'), 'c.id', '=', 'period_earned.contact_id')
                ->leftJoin(DB::raw('(
                    SELECT 
                        contact_id,
                        SUM(rp_redeemed) as period_redeemed,
                        COUNT(*) as redemption_count
                    FROM transactions 
                    WHERE business_id = ' . $business_id . ' 
                        AND type = "sell" 
                        AND status = "final"
                        AND rp_redeemed > 0
                        AND contact_id IS NOT NULL
                        AND transaction_date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                    GROUP BY contact_id
                ) as period_redeemed'), 'c.id', '=', 'period_redeemed.contact_id')
                ->where('c.business_id', $business_id)
                ->where('c.type', 'customer');

            if ($customer_id) {
                $query->where('c.id', $customer_id);
            }

            // Only show customers who have either earned or redeemed points
            $query->havingRaw('(total_earned_points > 0 OR total_redeemed_points > 0)');

            $customers = $query->get();

            $data = [];
            foreach ($customers as $customer) {
                $liabilityAmount = $customer->current_balance * 1.0; // 1 point = 1 BDT
                
                $data[] = [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->customer_name,
                    'customer_mobile' => $customer->customer_mobile,
                    'total_earned_points' => (int) $customer->total_earned_points,
                    'total_redeemed_points' => (int) $customer->total_redeemed_points,
                    'current_balance' => (int) $customer->current_balance,
                    'liability_amount' => number_format($liabilityAmount, 2),
                    'total_transactions' => (int) $customer->total_transactions,
                    'redemption_transactions' => (int) $customer->redemption_transactions,
                    'first_earning_date' => $customer->first_earning_date,
                    'last_activity_date' => $customer->last_activity_date,
                    'status' => $this->getCustomerStatus($customer->current_balance, $customer->last_activity_date)
                ];
            }

            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load customer summary: ' . $e->getMessage()], 500);
        }
    }

    public function getTransactionDetails(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            return response()->json(['error' => 'Reward points module is not enabled'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $customer_id = $request->get('customer_id');

        try {
            $query = DB::table('transactions as t')
                ->select([
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date',
                    'c.name as customer_name',
                    'c.id as customer_id',
                    't.final_total as invoice_amount',
                    't.rp_earned as points_earned',
                    't.rp_redeemed as points_redeemed',
                    't.rp_redeemed_amount as points_value_redeemed',
                    DB::raw('(t.final_total - COALESCE(t.rp_redeemed_amount, 0)) as final_payable')
                ])
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('c.type', 'customer')
                ->whereBetween('t.transaction_date', [$start_date, $end_date])
                ->whereNotNull('t.contact_id');

            if ($customer_id) {
                $query->where('c.id', $customer_id);
            }

            // Show transactions that either earned points or redeemed points
            $query->where(function($q) {
                $q->where('t.rp_earned', '>', 0)
                  ->orWhere('t.rp_redeemed', '>', 0);
            });

            $transactions = $query->orderBy('t.transaction_date', 'desc')->get();

            $data = [];
            foreach ($transactions as $transaction) {
                $data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'invoice_no' => $transaction->invoice_no,
                    'transaction_date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i'),
                    'customer_name' => $transaction->customer_name,
                    'customer_id' => $transaction->customer_id,
                    'invoice_amount' => number_format($transaction->invoice_amount, 2),
                    'points_earned' => (int) $transaction->points_earned,
                    'points_redeemed' => (int) $transaction->points_redeemed,
                    'points_value_redeemed' => number_format($transaction->points_value_redeemed, 2),
                    'final_payable' => number_format($transaction->final_payable, 2),
                    'transaction_type' => $this->getTransactionType($transaction->points_earned, $transaction->points_redeemed)
                ];
            }

            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load transaction details: ' . $e->getMessage()], 500);
        }
    }

    public function getTopPerformers(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            return response()->json(['error' => 'Reward points module is not enabled'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            // Top 5 Point Earners
            $topEarners = DB::table('transactions as t')
                ->select([
                    'c.name as customer_name',
                    'c.id as customer_id',
                    DB::raw('SUM(t.rp_earned) as total_earned_points'),
                    DB::raw('COUNT(*) as transaction_count')
                ])
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('c.type', 'customer')
                ->whereBetween('t.transaction_date', [$start_date, $end_date])
                ->whereNotNull('t.contact_id')
                ->where('t.rp_earned', '>', 0)
                ->groupBy('c.id', 'c.name')
                ->orderBy('total_earned_points', 'desc')
                ->limit(5)
                ->get();

            // Top 5 Point Redeemers
            $topRedeemers = DB::table('transactions as t')
                ->select([
                    'c.name as customer_name',
                    'c.id as customer_id',
                    DB::raw('SUM(t.rp_redeemed) as total_redeemed_points'),
                    DB::raw('SUM(t.rp_redeemed_amount) as total_redeemed_amount'),
                    DB::raw('COUNT(*) as redemption_count')
                ])
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->where('c.type', 'customer')
                ->whereBetween('t.transaction_date', [$start_date, $end_date])
                ->whereNotNull('t.contact_id')
                ->where('t.rp_redeemed', '>', 0)
                ->groupBy('c.id', 'c.name')
                ->orderBy('total_redeemed_points', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'top_earners' => $topEarners,
                'top_redeemers' => $topRedeemers
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load top performers: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.reward_points')) {
            abort(403, 'Unauthorized');
        }

        // Check if reward points module is enabled
        if (!$this->checkRewardPointsModule()) {
            abort(403, 'Reward points module is not enabled');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $export_type = $request->get('export_type', 'customer_summary'); // customer_summary, transaction_details

        try {
            // Prepare filters
            $filters = [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'export_type' => $export_type
            ];

            if ($export_type === 'customer_summary') {
                $fileName = 'reward-points-customer-summary-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
            } else {
                $fileName = 'reward-points-transactions-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
            }

            return Excel::download(new RewardPointsExport($business_id, $filters), $fileName);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    // Helper Methods

    /**
     * Check if reward points module is enabled
     */
    private function checkRewardPointsModule()
    {
        $reward_enabled = (request()->session()->get('business.enable_rp') == 1);
        return $reward_enabled;
    }

    private function calculateTotalPointsIssued($business_id, $start_date, $end_date)
    {
        // Use transactions for period-specific analysis
        return Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->whereNotNull('contact_id')
            ->sum('rp_earned');
    }

    private function countActiveCustomersWithPoints($business_id)
    {
        // Use contacts table for authoritative count of customers with points
        return Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where('total_rp', '>', 0)
            ->count();
    }

    private function getCustomerStatus($balance, $lastActivity)
    {
        if ($balance <= 0) return 'inactive';
        
        $daysSinceActivity = Carbon::parse($lastActivity)->diffInDays(Carbon::now());
        
        if ($daysSinceActivity <= 30) return 'active';
        if ($daysSinceActivity <= 90) return 'moderate';
        return 'inactive';
    }

    private function getTransactionType($pointsEarned, $pointsRedeemed)
    {
        if ($pointsEarned > 0 && $pointsRedeemed > 0) return 'both';
        if ($pointsEarned > 0) return 'earned';
        if ($pointsRedeemed > 0) return 'redeemed';
        return 'none';
    }

}