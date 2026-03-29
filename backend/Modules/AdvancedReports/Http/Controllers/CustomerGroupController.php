<?php

/**
 * Customer Group Performance Report Controller
 * 
 * This controller manages the Customer Group Performance analytics system providing:
 * - Multi-level drill-down analysis (Groups → Salespeople → Customers → Invoices)
 * - Dynamic customer segmentation (VIP, Regular, New, Unassigned)
 * - Comprehensive KPI metrics and aging analysis
 * - Real-time filtering and export capabilities
 * 
 * @package    AdvancedReports
 * @subpackage Controllers
 * @author     Horizonsoft Solutions
 * @version    1.1.0
 * @since      1.0.0
 */

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Customer Group Performance Report Controller
 * 
 * Provides comprehensive customer group analytics with Tally-like functionality
 * including multi-level drill-down capabilities and dynamic customer segmentation
 */
class CustomerGroupController extends Controller
{
    /**
     * Display the customer group performance report dashboard
     * 
     * Loads the main dashboard view with filters, location data, customer groups,
     * salespeople for the dropdown filters, and initializes the analytics interface.
     * 
     * @return \Illuminate\Contracts\View\View
     * @throws \Exception When business not found
     * 
     * @since 1.0.0
     */
    public function index()
    {
        $business_id = session()->get('user.business_id');
        
        // Get business information
        $business = Business::findOrFail($business_id);
        
        // Get locations for filtering
        $locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('All Locations'), '');

        // Get customer groups for filtering  
        $customer_groups = DB::table('customer_groups')
            ->where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('All Groups'), '');

        // Get salespeople for filtering
        $salespeople = DB::table('users')
            ->where('business_id', $business_id)
            ->select(DB::raw('CONCAT(first_name, " ", COALESCE(last_name, "")) as full_name'), 'id')
            ->pluck('full_name', 'id')
            ->prepend(__('All Salespeople'), '');

        // Get payment methods
        $payment_methods = [
            '' => __('All Payment Methods'),
            'cash' => __('Cash'),
            'card' => __('Card'),
            'cheque' => __('Cheque'),
            'bank_transfer' => __('Bank Transfer'),
            'other' => __('Other'),
        ];

        return view('advancedreports::customer-group.index', compact(
            'business',
            'locations',
            'customer_groups', 
            'salespeople',
            'payment_methods'
        ));
    }

    /**
     * Get customer group analytics data via AJAX
     * 
     * Returns comprehensive analytics including:
     * - Summary KPI metrics (customers, sales, efficiency, outstanding)
     * - Group leaderboard with ranking and performance metrics
     * - Aging analysis with bucket distribution
     * - Top performers identification
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @since 1.0.0
     */
    public function getCustomerGroupData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->input('location_id');
        $customer_group_id = $request->input('customer_group_id');
        $salesperson_id = $request->input('salesperson_id');
        $payment_method = $request->input('payment_method');
        $include_returns = $request->input('include_returns', true);
        $include_drafts = $request->input('include_drafts', false);

        try {
            $analytics = [
                'summary_metrics' => $this->getSummaryMetrics($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts),
                'group_leaderboard' => $this->getGroupLeaderboard($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts),
                'aging_analysis' => $this->getAgingAnalysis($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id),
                'top_performers' => $this->getTopPerformers($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts)
            ];

            return response()->json($analytics);
        } catch (\Exception $e) {
            \Log::error('Customer Group Analytics Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading customer group data'
            ], 500);
        }
    }

    /**
     * Get summary KPI metrics
     */
    private function getSummaryMetrics($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);
        
        $metrics = $query->select([
            DB::raw('COUNT(DISTINCT t.contact_id) as total_customers'),
            DB::raw('COUNT(DISTINCT t.id) as total_invoices'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) as gross_sales'),
            DB::raw('SUM(CASE WHEN t.type = "sell_return" THEN t.final_total ELSE 0 END) as returns'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
            DB::raw('SUM(t.discount_amount) as total_discounts'),
            DB::raw('COALESCE(AVG(CASE WHEN t.type = "sell" THEN t.final_total END), 0) as avg_invoice_value'),
        ])->first();

        // Get collections data
        $collections = $this->getCollectionsData($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method);
        
        // Calculate outstanding dues
        $outstanding = $this->getOutstandingDues($business_id, $customer_group_id, $salesperson_id);

        // Get top performing group
        $top_group = $this->getTopPerformingGroup($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);
        
        // Get top salesperson
        $top_salesperson = $this->getTopSalesperson($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);

        return [
            'total_customers' => $metrics->total_customers,
            'total_invoices' => $metrics->total_invoices,
            'gross_sales' => $metrics->gross_sales,
            'returns' => $metrics->returns,
            'net_sales' => $metrics->net_sales,
            'total_discounts' => $metrics->total_discounts,
            'avg_invoice_value' => $metrics->avg_invoice_value,
            'total_collected' => $collections['total_collected'],
            'collection_efficiency' => $collections['collection_efficiency'],
            'outstanding_due' => $outstanding,
            'top_group' => $top_group,
            'top_salesperson' => $top_salesperson,
        ];
    }

    /**
     * Get group leaderboard with salesperson performance
     */
    private function getGroupLeaderboard($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        // First get the raw data grouped by actual database fields
        $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);
        
        $rawData = $query->select([
            'cg.name as existing_group_name',
            DB::raw('CONCAT(u.first_name, " ", COALESCE(u.last_name, "")) as salesperson_name'),
            'u.id as salesperson_id',
            DB::raw('COUNT(DISTINCT t.contact_id) as customer_count'),
            DB::raw('COUNT(DISTINCT t.id) as invoice_count'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) as gross_sales'),
            DB::raw('SUM(CASE WHEN t.type = "sell_return" THEN t.final_total ELSE 0 END) as returns'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
            DB::raw('SUM(t.discount_amount) as discounts'),
            DB::raw('COALESCE(AVG(CASE WHEN t.type = "sell" THEN t.final_total END), 0) as avg_invoice_value'),
        ])
        ->groupBy('cg.name', 'salesperson_name', 'u.id')
        ->orderBy('net_sales', 'desc')
        ->get();

        // Now apply dynamic grouping logic in PHP
        $leaderboard = $rawData->map(function($row) {
            $row->customer_group = $this->determineCustomerGroup($row->existing_group_name, $row->gross_sales);
            return $row;
        });

        // Add collections data for each group-salesperson combination
        foreach ($leaderboard as $row) {
            $collections = $this->getCollectionsForSalesperson($business_id, $start_date, $end_date, $row->salesperson_id, $location_id, $customer_group_id, $payment_method);
            $row->total_collected = $collections['total_collected'];
            $row->collection_efficiency = $collections['collection_efficiency'];
            
            // Calculate outstanding for this salesperson
            $row->outstanding_due = $this->getOutstandingForSalesperson($business_id, $row->salesperson_id, $customer_group_id);
            
            // Calculate gross profit margin (simplified calculation)
            $row->gross_profit = $row->net_sales * 0.3; // Assuming 30% margin, can be made configurable
            $row->margin_percentage = $row->net_sales > 0 ? ($row->gross_profit / $row->net_sales) * 100 : 0;
        }

        return $leaderboard->values();
    }

    /**
     * Determine customer group based on existing assignment or sales volume
     * 
     * Implements dynamic customer segmentation logic:
     * 1. Prioritizes existing customer group assignments
     * 2. Falls back to sales-based segmentation:
     *    - VIP Customers: ≥$10,000 in sales
     *    - Regular Customers: $1,000-$9,999 in sales  
     *    - New Customers: $1-$999 in sales
     *    - Unassigned: $0 in sales
     * 
     * @param  string|null $existingGroupName Existing customer group assignment
     * @param  float $grossSales Total gross sales amount
     * @return string Customer group classification
     * 
     * @since 1.0.0
     */
    private function determineCustomerGroup($existingGroupName, $grossSales)
    {
        // If customer has an existing group assignment, use it
        if (!empty($existingGroupName)) {
            return $existingGroupName;
        }
        
        // Otherwise, use dynamic grouping based on sales volume
        if ($grossSales >= 10000) {
            return 'VIP Customers';
        } elseif ($grossSales >= 1000) {
            return 'Regular Customers';
        } elseif ($grossSales > 0) {
            return 'New Customers';
        } else {
            return 'Unassigned';
        }
    }

    /**
     * Get salesperson drill-down data for a specific customer group
     * 
     * Second level of drill-down analysis showing all salespeople performance
     * within a specific customer group with detailed metrics including
     * sales volume, customer count, and performance indicators.
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  string $customer_group URL-encoded customer group name
     * @return \Illuminate\Http\JsonResponse
     * 
     * @since 1.0.0
     */
    public function getSalespersonDrilldown(Request $request, $customer_group)
    {
        $business_id = $request->session()->get('user.business_id');
        $customer_group = urldecode($customer_group);
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->input('location_id');
        $payment_method = $request->input('payment_method');

        try {
            $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, null, null, $payment_method, true, false);
        
        // Apply group filter based on the customer_group parameter
        if ($customer_group !== 'All' && $customer_group !== '') {
            if (in_array($customer_group, ['VIP Customers', 'Regular Customers', 'New Customers', 'Unassigned'])) {
                // Dynamic grouping logic
                $query->havingRaw($this->getDynamicGroupCondition($customer_group));
            } else {
                // Static customer group
                $query->where('cg.name', $customer_group);
            }
        }

        $salespeople = $query->select([
            DB::raw('CONCAT(u.first_name, " ", COALESCE(u.last_name, "")) as salesperson_name'),
            'u.id as salesperson_id',
            DB::raw('COUNT(DISTINCT t.contact_id) as customer_count'),
            DB::raw('COUNT(DISTINCT t.id) as invoice_count'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) as gross_sales'),
            DB::raw('SUM(CASE WHEN t.type = "sell_return" THEN t.final_total ELSE 0 END) as returns'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
        ])
        ->groupBy('u.id', 'salesperson_name')
        ->orderBy('net_sales', 'desc')
        ->get();

        return response()->json($salespeople);
        
        } catch (\Exception $e) {
            \Log::error('Salesperson Drill-down Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading salesperson data'
            ], 500);
        }
    }

    /**
     * Get customer drill-down data for a specific salesperson
     * 
     * Third level of drill-down analysis showing all customers handled by
     * a specific salesperson with purchase history, risk assessment, and
     * outstanding dues analysis.
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int $salesperson_id Salesperson user ID
     * @return \Illuminate\Http\JsonResponse
     * 
     * @since 1.0.0
     */
    public function getCustomerDrilldown(Request $request, $salesperson_id)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->input('location_id');
        $payment_method = $request->input('payment_method');

        $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, null, $salesperson_id, $payment_method, true, false);
        
        $customers = $query->select([
            'c.id as customer_id',
            DB::raw('COALESCE(c.name, c.mobile, "Unknown") as customer_name'),
            'c.mobile',
            'c.email',
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) as total_sales'),
            DB::raw('SUM(CASE WHEN t.type = "sell_return" THEN t.final_total ELSE 0 END) as returns'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
            DB::raw('COUNT(DISTINCT t.id) as invoice_count'),
            DB::raw('MAX(t.transaction_date) as last_sale_date'),
            DB::raw('COALESCE(AVG(CASE WHEN t.type = "sell" THEN t.final_total END), 0) as avg_invoice_value'),
        ])
        ->groupBy('c.id', 'customer_name', 'c.mobile', 'c.email')
        ->orderBy('net_sales', 'desc')
        ->get();

        // Add outstanding dues and risk tags for each customer
        foreach ($customers as $customer) {
            $customer->outstanding_due = $this->getCustomerOutstanding($business_id, $customer->customer_id);
            $customer->risk_tag = $this->calculateRiskTag($customer);
        }

        return response()->json($customers);
    }

    /**
     * Get invoice drill-down data for a specific customer
     */
    public function getInvoiceDrilldown(Request $request, $customer_id)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->input('location_id');

        $query = DB::table('transactions as t')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $customer_id)
            ->where('t.type', 'sell')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        $invoices = $query->select([
            't.id',
            't.invoice_no',
            't.transaction_date',
            't.final_total as amount',
            't.discount_amount as discount',
            DB::raw('COALESCE(SUM(tp.amount), 0) as paid'),
            DB::raw('t.final_total - COALESCE(SUM(tp.amount), 0) as due'),
            DB::raw('CASE 
                WHEN t.final_total - COALESCE(SUM(tp.amount), 0) <= 0 THEN "Paid"
                WHEN COALESCE(SUM(tp.amount), 0) > 0 THEN "Partial"  
                ELSE "Due"
            END as payment_status'),
        ])
        ->groupBy('t.id', 't.invoice_no', 't.transaction_date', 't.final_total', 't.discount_amount')
        ->orderBy('t.transaction_date', 'desc')
        ->get();

        // Add product summary for each invoice
        foreach ($invoices as $invoice) {
            $products = DB::table('transaction_sell_lines as tsl')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->where('tsl.transaction_id', $invoice->id)
                ->select('p.name', 'tsl.quantity')
                ->get();
            
            $invoice->products = $products->pluck('name')->take(3)->implode(', ');
            $invoice->total_quantity = $products->sum('quantity');
        }

        return response()->json($invoices);
    }

    /**
     * Get aging analysis data
     */
    private function getAgingAnalysis($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('customer_groups as cg', 'c.customer_group_id', '=', 'cg.id')
            ->leftJoin('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->having('outstanding', '>', 0);

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        if ($customer_group_id) {
            $query->where('c.customer_group_id', $customer_group_id);
        }

        if ($salesperson_id) {
            $query->where('t.created_by', $salesperson_id);
        }

        $aging = $query->select([
            DB::raw('t.final_total - COALESCE(SUM(tp.amount), 0) as outstanding'),
            DB::raw('DATEDIFF(CURDATE(), t.transaction_date) as days_outstanding'),
            DB::raw('CASE 
                WHEN DATEDIFF(CURDATE(), t.transaction_date) <= 30 THEN "0-30 days"
                WHEN DATEDIFF(CURDATE(), t.transaction_date) <= 60 THEN "31-60 days"
                WHEN DATEDIFF(CURDATE(), t.transaction_date) <= 90 THEN "61-90 days"
                ELSE "90+ days"
            END as aging_bucket'),
        ])
        ->groupBy('t.id', 't.final_total', 't.transaction_date')
        ->get();

        // Group by aging buckets
        $aging_summary = $aging->groupBy('aging_bucket')->map(function($bucket) {
            return [
                'count' => $bucket->count(),
                'total_amount' => $bucket->sum('outstanding'),
            ];
        });

        return $aging_summary;
    }

    /**
     * Export customer group data
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $export_type = $request->input('export_type', 'comprehensive');

        $filename = "customer_group_report_" . $start_date . "_to_" . $end_date . ".csv";

        $callback = function() use ($business_id, $export_type, $start_date, $end_date, $request) {
            $file = fopen('php://output', 'w');
            
            if ($export_type === 'group_leaderboard' || $export_type === 'comprehensive') {
                // Group leaderboard data
                fputcsv($file, ['CUSTOMER GROUP LEADERBOARD']);
                fputcsv($file, ['Customer Group', 'Salesperson', 'Customers', 'Invoices', 'Gross Sales', 'Returns', 'Net Sales', 'Collections', 'Outstanding', 'Margin %']);
                
                $leaderboard = $this->getGroupLeaderboard($business_id, $start_date, $end_date, 
                    $request->input('location_id'), $request->input('customer_group_id'), 
                    $request->input('salesperson_id'), $request->input('payment_method'), 
                    $request->input('include_returns', true), $request->input('include_drafts', false));
                
                foreach ($leaderboard as $row) {
                    fputcsv($file, [
                        $row->customer_group,
                        $row->salesperson_name,
                        $row->customer_count,
                        $row->invoice_count,
                        $row->gross_sales,
                        $row->returns,
                        $row->net_sales,
                        $row->total_collected ?? 0,
                        $row->outstanding_due ?? 0,
                        number_format($row->margin_percentage ?? 0, 2) . '%'
                    ]);
                }
                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Helper Methods

    private function buildBaseQuery($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('customer_groups as cg', 'c.customer_group_id', '=', 'cg.id')
            ->leftJoin('users as u', 't.created_by', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        // Type filters
        $types = ['sell'];
        if ($include_returns) {
            $types[] = 'sell_return';
        }
        $query->whereIn('t.type', $types);

        // Draft filter
        if (!$include_drafts) {
            $query->where('t.is_quotation', 0);
        }

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        if ($customer_group_id) {
            $query->where('c.customer_group_id', $customer_group_id);
        }

        if ($salesperson_id) {
            $query->where('t.created_by', $salesperson_id);
        }

        if ($payment_method) {
            $query->join('transaction_payments as tpay', 't.id', '=', 'tpay.transaction_id')
                  ->where('tpay.method', $payment_method);
        }

        return $query;
    }

    private function getCollectionsData($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null)
    {
        $query = DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->whereBetween('tp.paid_on', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        if ($customer_group_id) {
            $query->where('c.customer_group_id', $customer_group_id);
        }

        if ($salesperson_id) {
            $query->where('t.created_by', $salesperson_id);
        }

        if ($payment_method) {
            $query->where('tp.method', $payment_method);
        }

        $collections = $query->sum('tp.amount');
        
        // Calculate collection efficiency (collections vs sales)
        $total_sales = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($customer_group_id, function($q) use ($customer_group_id) {
                return $q->where('c.customer_group_id', $customer_group_id);
            })
            ->when($salesperson_id, function($q) use ($salesperson_id) {
                return $q->where('t.created_by', $salesperson_id);
            })
            ->sum('t.final_total');

        $efficiency = $total_sales > 0 ? ($collections / $total_sales) * 100 : 0;

        return [
            'total_collected' => $collections,
            'collection_efficiency' => $efficiency,
        ];
    }

    private function getCollectionsForSalesperson($business_id, $start_date, $end_date, $salesperson_id, $location_id = null, $customer_group_id = null, $payment_method = null)
    {
        return $this->getCollectionsData($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method);
    }

    private function getOutstandingDues($business_id, $customer_group_id = null, $salesperson_id = null)
    {
        $subquery = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT transaction_id, SUM(amount) as total_payments FROM transaction_payments GROUP BY transaction_id) as tp'), 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell');

        if ($customer_group_id) {
            $subquery->where('c.customer_group_id', $customer_group_id);
        }

        if ($salesperson_id) {
            $subquery->where('t.created_by', $salesperson_id);
        }

        return $subquery->select(DB::raw('SUM(t.final_total - COALESCE(tp.total_payments, 0)) as outstanding'))
                        ->value('outstanding') ?: 0;
    }

    private function getOutstandingForSalesperson($business_id, $salesperson_id, $customer_group_id = null)
    {
        return $this->getOutstandingDues($business_id, $customer_group_id, $salesperson_id);
    }

    private function getCustomerOutstanding($business_id, $customer_id)
    {
        return DB::table('transactions as t')
            ->leftJoin(DB::raw('(SELECT transaction_id, SUM(amount) as total_payments FROM transaction_payments GROUP BY transaction_id) as tp'), 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $customer_id)
            ->where('t.type', 'sell')
            ->select(DB::raw('SUM(t.final_total - COALESCE(tp.total_payments, 0)) as outstanding'))
            ->value('outstanding') ?: 0;
    }

    private function getTopPerformingGroup($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);
        
        $top_group = $query->select([
            DB::raw('CASE 
                WHEN cg.name IS NOT NULL THEN cg.name 
                ELSE "Unassigned" 
            END as customer_group'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
        ])
        ->groupBy('customer_group')
        ->orderBy('net_sales', 'desc')
        ->first();

        return $top_group ? $top_group->customer_group : 'No Data';
    }

    private function getTopSalesperson($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        $query = $this->buildBaseQuery($business_id, $start_date, $end_date, $location_id, $customer_group_id, $salesperson_id, $payment_method, $include_returns, $include_drafts);
        
        $top_salesperson = $query->select([
            DB::raw('CONCAT(u.first_name, " ", COALESCE(u.last_name, "")) as salesperson_name'),
            DB::raw('SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE -t.final_total END) as net_sales'),
        ])
        ->groupBy('salesperson_name', 'u.id')
        ->orderBy('net_sales', 'desc')
        ->first();

        return $top_salesperson ? $top_salesperson->salesperson_name : 'No Data';
    }

    private function getTopPerformers($business_id, $start_date, $end_date, $location_id = null, $customer_group_id = null, $salesperson_id = null, $payment_method = null, $include_returns = true, $include_drafts = false)
    {
        // This can be expanded to return top 3 groups, salespeople, customers etc.
        return [
            'top_groups' => [],
            'top_salespeople' => [],
            'top_customers' => []
        ];
    }

    private function calculateRiskTag($customer)
    {
        $outstanding_ratio = $customer->net_sales > 0 ? ($customer->outstanding_due / $customer->net_sales) : 0;
        $days_since_last_sale = Carbon::now()->diffInDays(Carbon::parse($customer->last_sale_date));

        if ($outstanding_ratio > 0.5 || $days_since_last_sale > 90) {
            return 'High Risk';
        } elseif ($outstanding_ratio > 0.2 || $days_since_last_sale > 60) {
            return 'Medium Risk';
        } else {
            return 'Low Risk';
        }
    }

    private function getDynamicGroupCondition($group)
    {
        switch ($group) {
            case 'VIP Customers':
                return 'SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) >= 10000';
            case 'Regular Customers':
                return 'SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) BETWEEN 1000 AND 9999';
            case 'New Customers':
                return 'SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) BETWEEN 1 AND 999';
            case 'Unassigned':
                return 'SUM(CASE WHEN t.type = "sell" THEN t.final_total ELSE 0 END) = 0';
            default:
                return '1=1';
        }
    }
}