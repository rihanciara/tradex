<?php

namespace Modules\AdvancedReports\Utils;

use App\Business;
use App\Contact;
use App\Transaction;
use App\Variation;
use App\Product;
use App\VariationLocationDetails;
// CustomerRecognitionSetting
use Modules\AdvancedReports\Entities\CustomerRecognitionSetting;
use Modules\AdvancedReports\Entities\CustomerEngagement;
use Modules\AdvancedReports\Entities\CustomerAward;
use Modules\AdvancedReports\Entities\AwardPeriod;
use Modules\AdvancedReports\Entities\CustomerRecognitionCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerRecognitionUtil
{
/**
 * Calculate customer scores for a specific period - UPDATED
 */
public static function calculateCustomerScores($business_id, $period_type, $period_start, $period_end)
{
    $settings = CustomerRecognitionSetting::getForBusiness($business_id);
    
    if (!$settings || !$settings->is_active) {
        return collect([]);
    }

    // Get sales data for the period
    $salesData = self::getSalesDataForPeriod($business_id, $period_start, $period_end);
    
    // Get engagement data for the period
    $engagementData = self::getEngagementDataForPeriod($business_id, $period_start, $period_end);
    
    // Combine and calculate scores
    $customerScores = [];
    
    foreach ($salesData as $sale) {
        $customer_id = $sale->customer_id;
        $engagement_points = $engagementData->get($customer_id, 0);
          // ADD THIS LOGGING
    if (strpos($sale->customer_name, 'Robert') !== false) {
        \Log::info('Robert Johnson in calculateCustomerScores - Raw Sale Data: ' . json_encode([
            'customer_name' => $sale->customer_name,
            'sales_total' => $sale->sales_total,
            'total_paid' => $sale->total_paid ?? 'MISSING PROPERTY',
            'balance_due' => $sale->balance_due ?? 'MISSING PROPERTY', 
            'payment_percentage' => $sale->payment_percentage ?? 'MISSING PROPERTY',
            'has_payment_data' => isset($sale->total_paid),
            'settings_uses_payment_data' => $settings ? $settings->usesPaymentData() : 'NO SETTINGS'
        ]));
    }
        // Check if payment data is available (new columns)
        $has_payment_data = isset($sale->total_paid);
        
       if ($has_payment_data && $settings->usesPaymentData()) {
        // Use full payment data
        $sales_data = [
            'sales_total' => $sale->sales_total,
            'total_paid' => $sale->total_paid ?? $sale->sales_total,
            'balance_due' => $sale->balance_due ?? 0,
            'payment_percentage' => $sale->payment_percentage ?? 100
        ];
    } else {
        // Backward compatibility - assume full payment
        $sales_data = [
            'sales_total' => $sale->sales_total,
            'total_paid' => $sale->sales_total,  // ← THIS IS THE ISSUE!
            'balance_due' => 0,
            'payment_percentage' => 100
        ];
    }

      // ADD MORE LOGGING
    if (strpos($sale->customer_name, 'Robert') !== false) {
        \Log::info('Robert Johnson Final Sales Data Array: ' . json_encode($sales_data));
    }
        
        $final_score = self::calculateFinalScore(
            $sales_data,              // Pass array instead of scalar
            $engagement_points,
            $settings->scoring_method,
            $settings->sales_weight,
            $settings->engagement_weight
        );
        
        $customerScores[] = [
            'customer_id' => $customer_id,
            'customer_name' => $sale->customer_name,
            'customer_business_name' => $sale->customer_business_name,
            'customer_mobile' => $sale->customer_mobile,
            'sales_total' => $sale->sales_total,
            'total_paid' => $sales_data['total_paid'],
            'balance_due' => $sales_data['balance_due'],
            'payment_percentage' => $sales_data['payment_percentage'],
            'transaction_count' => $sale->transaction_count,
            'avg_transaction_value' => $sale->avg_transaction_value,
            'engagement_points' => $engagement_points,
            'final_score' => $final_score,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end,
        ];
    }
    
    // Sort by final score descending
    usort($customerScores, function ($a, $b) {
        if ($a['final_score'] == $b['final_score']) {
            return $b['sales_total'] <=> $a['sales_total']; // Tie-breaker: higher sales
        }
        return $b['final_score'] <=> $a['final_score'];
    });
    
    // Add rankings
    foreach ($customerScores as $index => &$score) {
        $score['rank_position'] = $index + 1;
    }
    
    return collect($customerScores);
}

/**
 * Get sales data for customers in a period - FIXED VERSION
 */
private static function getSalesDataForPeriod($business_id, $start_date, $end_date)
{
    // Let's build this step by step to debug the issue
    $query = DB::table('transactions as t')
        ->join('contacts as c', 't.contact_id', '=', 'c.id')
        ->leftJoin('transaction_payments as tp', function($join) use ($business_id) {
            $join->on('t.id', '=', 'tp.transaction_id')
                 ->where('tp.business_id', '=', $business_id)
                 ->where('tp.is_return', '=', 0);
        })
        ->where('t.business_id', $business_id)
        ->where('t.type', 'sell')
        ->where('t.status', 'final')
        ->whereBetween('t.transaction_date', [$start_date, $end_date])
        ->whereNotNull('t.contact_id')
        ->select([
            'c.id as customer_id',
            'c.name as customer_name',
            'c.supplier_business_name as customer_business_name',
            'c.mobile as customer_mobile',
            'c.created_at as customer_registered_date',
            
            // Sales totals
            DB::raw('SUM(t.final_total) as sales_total'),
            DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
            DB::raw('AVG(t.final_total) as avg_transaction_value'),
            
            // Payment totals - EXPLICIT NULL HANDLING
            DB::raw('COALESCE(SUM(tp.amount), 0) as total_paid'),
            DB::raw('SUM(t.final_total) - COALESCE(SUM(tp.amount), 0) as balance_due'),
            DB::raw('CASE 
                WHEN SUM(t.final_total) = 0 THEN 100
                ELSE ROUND((COALESCE(SUM(tp.amount), 0) / SUM(t.final_total)) * 100, 2)
            END as payment_percentage'),
            
            DB::raw('MIN(t.transaction_date) as first_purchase_date')
        ])
        ->groupBy(['c.id', 'c.name', 'c.supplier_business_name', 'c.mobile', 'c.created_at'])
        ->having('sales_total', '>', 0);

    $results = $query->get();
    
    // Debug: Log the SQL and results
    \Log::info('Sales Data Query SQL: ' . $query->toSql());
    \Log::info('Sales Data Results Count: ' . $results->count());
    
    // Log Robert Johnson's data specifically
    $robert = $results->where('customer_name', 'Robert Johnson')->first();
    if ($robert) {
        \Log::info('Robert Johnson Data from Query: ' . json_encode($robert));
    }
    // Log each customer's data
foreach ($results as $result) {
    if (strpos($result->customer_name, 'Robert') !== false) {
        \Log::info('Robert Johnson Debug Data: ' . json_encode([
            'customer_name' => $result->customer_name,
            'sales_total' => $result->sales_total,
            'total_paid' => $result->total_paid ?? 'MISSING',
            'balance_due' => $result->balance_due ?? 'MISSING',
            'payment_percentage' => $result->payment_percentage ?? 'MISSING'
        ]));
    }
}
    return $results;
}

    /**
     * Get engagement data for customers in a period
     */
    private static function getEngagementDataForPeriod($business_id, $start_date, $end_date)
    {
        return CustomerEngagement::where('business_id', $business_id)
            ->where('status', 'verified')
            ->whereBetween('recorded_date', [$start_date, $end_date])
            ->select([
                'customer_id',
                DB::raw('SUM(points) as total_points')
            ])
            ->groupBy('customer_id')
            ->pluck('total_points', 'customer_id');
    }

/**
 * Calculate final score based on scoring method - FIXED VERSION
 */
private static function calculateFinalScore($sales_data, $engagement_points, $scoring_method, $sales_weight, $engagement_weight)
{
    // Handle backward compatibility - if $sales_data is a number, convert to array format
    if (is_numeric($sales_data)) {
        $sales_value = $sales_data;
        $sales_data = [
            'sales_total' => $sales_value,
            'total_paid' => $sales_value,  // Assume full payment for backward compatibility
            'balance_due' => 0,
            'payment_percentage' => 100
        ];
    }
    
    // Extract values from sales data
    $sales_value = $sales_data['sales_total'] ?? 0;
    $total_paid = $sales_data['total_paid'] ?? $sales_value;  // Default to sales value if not provided
    $balance_due = $sales_data['balance_due'] ?? 0;
    $payment_percentage = $sales_data['payment_percentage'] ?? ($sales_value > 0 ? ($total_paid / $sales_value) * 100 : 100);
    
    switch ($scoring_method) {
        case 'pure_sales':
            return $sales_value;
            
        case 'pure_payments':
            // Only actual payments count
            return $total_paid;
            
        case 'weighted_payments':
            // Use payments instead of sales for scoring
            $payment_score = $total_paid * $sales_weight;
            $engagement_score = ($engagement_points * 10) * $engagement_weight;
            return $payment_score + $engagement_score;
            
        case 'payment_adjusted':
            // Reduce sales score based on unpaid percentage
            $payment_factor = max(0.1, $payment_percentage / 100); // Minimum 10% credit
            $adjusted_sales = $sales_value * $payment_factor;
            $sales_score = $adjusted_sales * $sales_weight;
            $engagement_score = ($engagement_points * 10) * $engagement_weight;
            return $sales_score + $engagement_score;
            
        case 'weighted':
        default:
            // Original method - sales total
            $sales_score = $sales_value * $sales_weight;
            $engagement_score = ($engagement_points * 10) * $engagement_weight;
            return $sales_score + $engagement_score;
    }
}

/**
 * Update cache for all customers in a period
 */
public static function updateCacheForPeriod($business_id, $period_type, $period_start, $period_end)
{
    try {
        $scores = self::calculateCustomerScores($business_id, $period_type, $period_start, $period_end);
        
        if ($scores->isEmpty()) {
            \Log::info('No scores to cache for period', [
                'business_id' => $business_id,
                'period_type' => $period_type,
                'period_start' => $period_start,
                'period_end' => $period_end
            ]);
            return collect([]);
        }
        
        // Clear existing cache for this period
        CustomerRecognitionCache::where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->where('period_start', $period_start)
            ->where('period_end', $period_end)
            ->delete();
        
        // Insert new cache data using the bulk method from your model
        $customer_data = $scores->map(function ($score) {
            return [
                'customer_id' => $score['customer_id'],
                'sales_total' => $score['sales_total'],
                'engagement_points' => $score['engagement_points'],
                'final_score' => $score['final_score'],
                'transaction_count' => $score['transaction_count'],
                'current_rank' => $score['rank_position']
            ];
        })->toArray();
        
        // Use the bulk update method from your model
        CustomerRecognitionCache::bulkUpdateCache(
            $business_id, 
            $period_type, 
            $period_start, 
            $period_end, 
            $customer_data
        );
        
        \Log::info('Cache updated successfully', [
            'business_id' => $business_id,
            'period_type' => $period_type,
            'cached_customers' => count($customer_data)
        ]);
        
        return $scores;
        
    } catch (\Exception $e) {
        \Log::error('Cache update failed: ' . $e->getMessage(), [
            'business_id' => $business_id,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Don't throw error - caching is optional, just log it
        return $scores ?? collect([]);
    }
}

/**
 * Finalize period and create award records
 */
public static function finalizePeriod($business_id, $period_type, $period_start, $period_end, $winner_count, $user_id)
{
    DB::beginTransaction();
    
    try {
        \Log::info('Starting period finalization', [
            'business_id' => $business_id,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'winner_count' => $winner_count
        ]);
        
        // Get or create period record
        $period = AwardPeriod::firstOrCreate([
            'business_id' => $business_id,
            'period_type' => $period_type,
            'period_start' => $period_start
        ], [
            'period_end' => $period_end
        ]);
        
        if ($period->is_finalized) {
            throw new \Exception('Period is already finalized');
        }
        
        // Calculate final scores
        $scores = self::calculateCustomerScores($business_id, $period_type, $period_start, $period_end);
        
        if ($scores->isEmpty()) {
            throw new \Exception('No customer scores found for this period');
        }
        
        // Take only the top winners
        $winners = $scores->take($winner_count);
        
        // FORCE DELETE all existing awards for this period using raw SQL
        $deleted = DB::delete("
            DELETE FROM customer_awards 
            WHERE business_id = ? 
            AND period_type = ? 
            AND period_start = ? 
            AND period_end = ?
        ", [$business_id, $period_type, $period_start, $period_end]);
        
        \Log::info('Force deleted existing awards', [
            'deleted_count' => $deleted
        ]);
        
        // Create new awards using plain SQL to avoid any Eloquent issues
        $insert_data = [];
        $now = now();
        
        foreach ($winners as $winner) {
            $insert_data[] = [
                'business_id' => $business_id,
                'customer_id' => $winner['customer_id'],
                'period_id' => $period->id,
                'period_type' => $period_type,
                'period_start' => $period_start,
                'period_end' => $period_end,
                'rank_position' => $winner['rank_position'],
                'sales_total' => $winner['sales_total'],
                'engagement_points' => $winner['engagement_points'],
                'final_score' => $winner['final_score'],
                'transaction_count' => $winner['transaction_count'],
                'avg_transaction_value' => $winner['avg_transaction_value'],
                'award_type' => 'none',
                'is_awarded' => 0,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        // Bulk insert using DB::table
        if (!empty($insert_data)) {
            DB::table('customer_awards')->insert($insert_data);
        }
        
        \Log::info('Created new award records', [
            'created_count' => count($insert_data)
        ]);
        
        // Update period as finalized
        $period->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $user_id,
            'total_participants' => $scores->count(),
            'winners_count' => $winners->count(),
            'period_summary' => [
                'total_sales' => $scores->sum('sales_total'),
                'total_engagement_points' => $scores->sum('engagement_points'),
                'avg_score' => $scores->avg('final_score'),
                'top_score' => $winners->first()['final_score'] ?? 0
            ]
        ]);
        
        // Update cache (with error handling)
        try {
            self::updateCacheForPeriod($business_id, $period_type, $period_start, $period_end);
        } catch (\Exception $e) {
            \Log::warning('Cache update failed during finalization', [
                'error' => $e->getMessage()
            ]);
        }
        
        DB::commit();
        
        \Log::info('Period finalization completed successfully', [
            'period_id' => $period->id,
            'winners_created' => count($insert_data)
        ]);
        
        return $period;
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Finalize period failed', [
            'error' => $e->getMessage(),
            'business_id' => $business_id,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

    /**
     * Award a gift to a customer
     */
    public static function awardCustomer($award_id, $award_data, $user_id, $location_id = null)
    {
        DB::beginTransaction();
        
        try {
            $award = CustomerAward::findOrFail($award_id);
            
            $update_data = [
                'award_type' => $award_data['award_type'],
                'is_awarded' => true,
                'awarded_by' => $user_id,
                'awarded_date' => now(),
                'award_notes' => $award_data['notes'] ?? null
            ];
            
            if ($award_data['award_type'] === 'catalog') {
                // For product-based awards, the controller already validated the product
                // and prepared the gift description and monetary value
                $variation_id = $award_data['catalog_item_id']; // This is now the product variation ID
                $update_data['catalog_item_id'] = $variation_id;
                $update_data['gift_description'] = $award_data['gift_description'];
                $update_data['gift_monetary_value'] = $award_data['gift_monetary_value'];
                
                // Decrease product stock by specified quantity when awarded
                if ($location_id) {
                    $variation = Variation::find($variation_id);
                    if ($variation) {
                        // Get quantity to deduct (default to 1)
                        $quantity = $award_data['award_quantity'] ?? 1;
                        
                        // Check if stock management is enabled for this product
                        $product = Product::find($variation->product_id);
                        if ($product && $product->enable_stock == 1) {
                            // Decrease stock by specified quantity (using negative value)
                            $affected = VariationLocationDetails::where('variation_id', $variation_id)
                                ->where('product_id', $variation->product_id)
                                ->where('location_id', $location_id)
                                ->increment('qty_available', -$quantity); // Negative increment = decrease
                            
                            $update_data['stock_deducted'] = true;
                            
                            // Log for debugging
                            \Log::info('Customer Award Stock Deduction', [
                                'product_id' => $variation->product_id,
                                'variation_id' => $variation_id,
                                'location_id' => $location_id,
                                'quantity_deducted' => $quantity,
                                'rows_affected' => $affected,
                                'product_name' => $product->name
                            ]);
                        }
                    }
                }
                
            } else {
                // Manual award
                $update_data['gift_description'] = $award_data['gift_description'];
                $update_data['gift_monetary_value'] = $award_data['gift_monetary_value'] ?? 0;
            }
            
            $award->update($update_data);
            
            DB::commit();
            
            return $award;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

/**
 * Get current period winners for summary cards
 */
public static function getCurrentPeriodWinners($business_id)
{
    $winners = [];
    $periods = ['weekly', 'monthly', 'yearly'];
    
    foreach ($periods as $period_type) {
        $dates = AwardPeriod::getPeriodDates($period_type);
        
        // Try to get from cache first
        $cached_winner = CustomerRecognitionCache::with('customer')
            ->where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->where('current_rank', 1)
            ->first();
        
        if ($cached_winner && !$cached_winner->isStale(30)) { // Cache valid for 30 minutes
            $customer = $cached_winner->customer;
            $winners[$period_type] = [
                'customer_id' => $cached_winner->customer_id,
                'customer_name' => $customer ? $customer->name : 'Unknown Customer',
                'customer_business_name' => $customer ? $customer->supplier_business_name : '',
                'sales_total' => $cached_winner->sales_total,
                'final_score' => $cached_winner->final_score,
                'engagement_points' => $cached_winner->engagement_points
            ];
        } else {
            // Fallback to live calculation
            $scores = self::calculateCustomerScores($business_id, $period_type, $dates['start'], $dates['end']);
            $winner_score = $scores->first();
            
            if ($winner_score) {
                $winners[$period_type] = [
                    'customer_id' => $winner_score['customer_id'],
                    'customer_name' => $winner_score['customer_name'],
                    'customer_business_name' => $winner_score['customer_business_name'],
                    'sales_total' => $winner_score['sales_total'],
                    'final_score' => $winner_score['final_score'],
                    'engagement_points' => $winner_score['engagement_points']
                ];
                
                // Update cache asynchronously (don't wait for it)
                try {
                    self::updateCacheForPeriod($business_id, $period_type, $dates['start'], $dates['end']);
                } catch (\Exception $e) {
                    \Log::warning('Background cache update failed: ' . $e->getMessage());
                }
            } else {
                $winners[$period_type] = null;
            }
        }
    }
    
    return $winners;
}

    /**
     * Get customer purchase history and products
     */
    public static function getCustomerPurchaseDetails($business_id, $customer_id, $period_start, $period_end)
    {
        // Get transactions in period
        $transactions = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
            ->leftJoin('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.contact_id', $customer_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereBetween('transactions.transaction_date', [$period_start, $period_end])
            ->select([
                'transactions.id as transaction_id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'p.name as product_name',
                'p.sku',
                'v.name as variation_name',
                'tsl.quantity',
                'tsl.unit_price_inc_tax',
                DB::raw('(tsl.quantity * tsl.unit_price_inc_tax) as line_total')
            ])
            ->orderBy('transactions.transaction_date', 'desc')
            ->get();
        
        // Get product summary
        $products = $transactions->groupBy('product_name')->map(function ($group) {
            return [
                'product_name' => $group->first()->product_name,
                'sku' => $group->first()->sku,
                'total_quantity' => $group->sum('quantity'),
                'total_amount' => $group->sum('line_total'),
                'purchase_count' => $group->count()
            ];
        })->values();
        
// Get engagement activities
$engagements = CustomerEngagement::where('business_id', $business_id)
    ->where('customer_id', $customer_id)
    ->whereBetween('recorded_date', [$period_start, $period_end])
    ->with('recordedBy')
    ->orderBy('recorded_date', 'desc')
    ->get();

// Calculate summary properly
$unique_transactions = $transactions->groupBy('transaction_id');
$total_transactions = $unique_transactions->count();
$total_amount = $unique_transactions->sum(function ($group) {
    return $group->first()->final_total;
});

return [
    'transactions' => $transactions,
    'products' => $products,
    'engagements' => $engagements,
    'summary' => [
        'total_transactions' => $total_transactions,
        'total_amount' => $total_amount,
        'total_products' => $products->count(),
        'total_engagement_points' => $engagements->sum('points'),
        'avg_transaction_value' => $total_transactions > 0 ? ($total_amount / $total_transactions) : 0,
        'first_transaction' => $unique_transactions->min(function ($group) {
            return $group->first()->transaction_date;
        }),
        'last_transaction' => $unique_transactions->max(function ($group) {
            return $group->first()->transaction_date;
        })
    ]
];
    }

    /**
     * Get period statistics for charts
     */
    public static function getPeriodStatistics($business_id, $period_type, $months_back = 12)
    {
        $statistics = [];
        $current_date = Carbon::now();
        
        for ($i = 0; $i < $months_back; $i++) {
            $date = $current_date->copy();
            
            if ($period_type === 'monthly') {
                $date->subMonths($i);
                $dates = AwardPeriod::getPeriodDates('monthly', $date);
                $label = $date->format('M Y');
            } elseif ($period_type === 'weekly') {
                $date->subWeeks($i);
                $dates = AwardPeriod::getPeriodDates('weekly', $date);
                $label = 'Week ' . $date->weekOfYear . ' ' . $date->year;
            } else {
                $date->subYears($i);
                $dates = AwardPeriod::getPeriodDates('yearly', $date);
                $label = $date->year;
            }
            
            $period_data = CustomerRecognitionCache::where('business_id', $business_id)
                ->where('period_type', $period_type)
                ->where('period_start', $dates['start'])
                ->where('period_end', $dates['end'])
                ->selectRaw('
                    COUNT(*) as total_participants,
                    SUM(sales_total) as total_sales,
                    SUM(engagement_points) as total_engagement,
                    AVG(final_score) as avg_score,
                    MAX(final_score) as top_score
                ')
                ->first();
            
            $statistics[] = [
                'period' => $label,
                'period_start' => $dates['start'],
                'period_end' => $dates['end'],
                'total_participants' => $period_data->total_participants ?? 0,
                'total_sales' => $period_data->total_sales ?? 0,
                'total_engagement' => $period_data->total_engagement ?? 0,
                'avg_score' => $period_data->avg_score ?? 0,
                'top_score' => $period_data->top_score ?? 0
            ];
        }
        
        return array_reverse($statistics);
    }

    /**
     * Get top performers across all periods
     */
    public static function getTopPerformersAllTime($business_id, $limit = 10)
    {
        return CustomerAward::where('business_id', $business_id)
            ->with(['customer'])
            ->select([
                'customer_id',
                DB::raw('COUNT(*) as total_awards'),
                DB::raw('SUM(sales_total) as total_sales'),
                DB::raw('SUM(engagement_points) as total_engagement'),
                DB::raw('AVG(rank_position) as avg_rank'),
                DB::raw('MIN(rank_position) as best_rank'),
                DB::raw('MAX(period_end) as last_win_date')
            ])
            ->groupBy('customer_id')
            ->orderByDesc('total_awards')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    /**
     * Initialize settings for a business
     */
    public static function initializeBusinessSettings($business_id)
    {
        return CustomerRecognitionSetting::firstOrCreate([
            'business_id' => $business_id
        ], [
            'weekly_enabled' => true,
            'monthly_enabled' => true,
            'yearly_enabled' => true,
            'winner_count_weekly' => 3,
            'winner_count_monthly' => 5,
            'winner_count_yearly' => 10,
            'scoring_method' => 'weighted',
            'sales_weight' => 0.70,
            'engagement_weight' => 0.30,
            'module_start_date' => Carbon::now()->format('Y-m-d'),
            'calculate_historical' => false,
            'historical_months' => 12,
            'is_active' => true
        ]);
    }

    /**
     * Get current period dates based on period type
     */
    public static function getCurrentPeriodDates($period_type = 'monthly')
    {
        $now = Carbon::now();
        
        switch ($period_type) {
            case 'weekly':
                return [
                    'start' => $now->startOfWeek()->format('Y-m-d'),
                    'end' => $now->endOfWeek()->format('Y-m-d')
                ];
                
            case 'yearly':
                return [
                    'start' => $now->startOfYear()->format('Y-m-d'),
                    'end' => $now->endOfYear()->format('Y-m-d')
                ];
                
            case 'monthly':
            default:
                return [
                    'start' => $now->startOfMonth()->format('Y-m-d'),
                    'end' => $now->endOfMonth()->format('Y-m-d')
                ];
        }
    }
}