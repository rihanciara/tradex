<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Business;
use App\Transaction;
use App\TransactionSellLine;
use App\TaxRate;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TaxComplianceController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display the tax compliance report dashboard
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        // Get available tax jurisdictions
        $tax_jurisdictions = $this->getTaxJurisdictions($business_id);
        
        // Get tax rates for filters
        $tax_rates = TaxRate::where('business_id', $business_id)
                           ->where('deleted_at', null)
                           ->pluck('name', 'id')
                           ->prepend(__('lang_v1.all'), '');

        // Define tax periods for compliance
        $tax_periods = [
            'monthly' => __('Monthly'),
            'quarterly' => __('Quarterly'),
            'semi_annual' => __('Semi-Annual'),
            'annual' => __('Annual'),
        ];

        // Define compliance statuses
        $compliance_statuses = [
            'compliant' => __('Compliant'),
            'pending' => __('Pending Review'),
            'overdue' => __('Overdue'),
            'exempted' => __('Exempted'),
        ];

        // Get currency information from session/business
        $currency_symbol = session('currency')['symbol'] ?? ($business->currency->symbol ?? '');
        $currency_placement = session('business.currency_symbol_placement') ?? 'before';

        return view('advancedreports::tax-compliance.index', compact(
            'business',
            'tax_jurisdictions',
            'tax_rates',
            'tax_periods',
            'compliance_statuses',
            'currency_symbol',
            'currency_placement'
        ));
    }

    /**
     * Get tax compliance summary data
     */
    public function getSummary(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Date range for analysis
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $jurisdiction = $request->input('jurisdiction', 'all');

        // Total tax collected
        $total_tax_collected = $this->calculateTotalTaxCollected($business_id, $start_date, $end_date, $jurisdiction);
        
        // Tax liability
        $tax_liability = $this->calculateTaxLiability($business_id, $start_date, $end_date, $jurisdiction);
        
        // Filing compliance score
        $compliance_score = $this->calculateComplianceScore($business_id, $start_date, $end_date);
        
        // Potential savings
        $potential_savings = $this->calculateTaxOptimizationSavings($business_id, $start_date, $end_date);

        // Tax breakdown by jurisdiction
        $tax_by_jurisdiction = $this->getTaxBreakdownByJurisdiction($business_id, $start_date, $end_date);
        
        // Monthly tax liability trend
        $monthly_tax_trend = $this->getMonthlyTaxTrend($business_id, $start_date, $end_date);
        
        // Tax rate optimization insights
        $optimization_insights = $this->getTaxOptimizationInsights($business_id, $start_date, $end_date);
        
        // Upcoming filing deadlines
        $upcoming_deadlines = $this->getUpcomingFilingDeadlines($business_id);
        
        // Tax audit risk assessment
        $audit_risk = $this->calculateAuditRisk($business_id, $start_date, $end_date);

        return [
            'total_tax_collected' => $total_tax_collected,
            'tax_liability' => $tax_liability,
            'compliance_score' => $compliance_score,
            'potential_savings' => $potential_savings,
            'tax_by_jurisdiction' => $tax_by_jurisdiction,
            'monthly_tax_trend' => $monthly_tax_trend,
            'optimization_insights' => $optimization_insights,
            'upcoming_deadlines' => $upcoming_deadlines,
            'audit_risk' => $audit_risk,
        ];
    }

    /**
     * Get detailed tax liability calculations
     */
    public function getTaxLiabilityDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Sales tax liability
        $sales_tax_liability = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                'tr.name as tax_name',
                'tr.amount as tax_rate',
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price) as tax_amount'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price) as taxable_amount'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            )
            ->groupBy('tr.id', 'tr.name', 'tr.amount')
            ->get();

        // Purchase tax liability (input tax credits)
        $purchase_tax_credits = DB::table('transactions as t')
            ->join('purchase_lines as pl', 't.id', '=', 'pl.transaction_id')
            ->join('tax_rates as tr', 'pl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                'tr.name as tax_name',
                'tr.amount as tax_rate',
                DB::raw('SUM(pl.quantity * pl.pp_without_discount * tr.amount / 100) as tax_credit_amount'),
                DB::raw('SUM(pl.quantity * pl.pp_without_discount) as taxable_amount'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            )
            ->groupBy('tr.id', 'tr.name', 'tr.amount')
            ->get();

        // Net tax liability calculation
        $net_liability = [];
        foreach ($sales_tax_liability as $sales_tax) {
            $credit = $purchase_tax_credits->where('tax_name', $sales_tax->tax_name)->first();
            $credit_amount = $credit ? $credit->tax_credit_amount : 0;
            
            $net_liability[] = [
                'tax_name' => $sales_tax->tax_name,
                'tax_rate' => $sales_tax->tax_rate,
                'output_tax' => $sales_tax->tax_amount,
                'input_tax_credit' => $credit_amount,
                'net_liability' => $sales_tax->tax_amount - $credit_amount,
                'sales_transactions' => $sales_tax->transaction_count,
                'purchase_transactions' => $credit ? $credit->transaction_count : 0,
            ];
        }

        return $net_liability;
    }

    /**
     * Get filing assistance report
     */
    public function getFilingAssistance(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $period = $request->input('period', 'monthly');
        $year = $request->input('year', Carbon::now()->year);
        $report_type = $request->input('report_type');

        $filing_data = [];
        
        // Check if period is a specific month like "September 2025"
        if (preg_match('/^(\w+)\s+(\d{4})$/', $period, $matches)) {
            $month_name = $matches[1];
            $year = $matches[2];
            
            try {
                $start_date = Carbon::createFromFormat('F Y', $month_name . ' ' . $year)->startOfMonth();
                $end_date = Carbon::createFromFormat('F Y', $month_name . ' ' . $year)->endOfMonth();
                
                $filing_data[] = [
                    'period' => $start_date->format('F Y'),
                    'period_key' => $start_date->format('Y-m'),
                    'gross_sales' => $this->calculateGrossSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'taxable_sales' => $this->calculateTaxableSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_collected' => $this->calculateTotalTaxCollected($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_paid_on_purchases' => $this->calculateTaxPaidOnPurchases($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'filing_deadline' => $this->getFilingDeadline($start_date, 'monthly'),
                    'filing_status' => $this->getFilingStatus($business_id, $start_date->format('Y-m')),
                ];
            } catch (Exception $e) {
                // If parsing fails, fall back to monthly for current year
                $period = 'monthly';
            }
        }
        
        if ($period === 'monthly' && empty($filing_data)) {
            for ($month = 1; $month <= 12; $month++) {
                $start_date = Carbon::create($year, $month, 1)->startOfMonth();
                $end_date = Carbon::create($year, $month, 1)->endOfMonth();
                
                $filing_data[] = [
                    'period' => $start_date->format('F Y'),
                    'period_key' => $start_date->format('Y-m'),
                    'gross_sales' => $this->calculateGrossSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'taxable_sales' => $this->calculateTaxableSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_collected' => $this->calculateTotalTaxCollected($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_paid_on_purchases' => $this->calculateTaxPaidOnPurchases($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'filing_deadline' => $this->getFilingDeadline($start_date, $period),
                    'filing_status' => $this->getFilingStatus($business_id, $start_date->format('Y-m')),
                ];
            }
        } elseif ($period === 'quarterly') {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $start_date = Carbon::create($year)->quarter($quarter)->startOfQuarter();
                $end_date = Carbon::create($year)->quarter($quarter)->endOfQuarter();
                
                $filing_data[] = [
                    'period' => 'Q' . $quarter . ' ' . $year,
                    'period_key' => $year . '-Q' . $quarter,
                    'gross_sales' => $this->calculateGrossSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'taxable_sales' => $this->calculateTaxableSales($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_collected' => $this->calculateTotalTaxCollected($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'tax_paid_on_purchases' => $this->calculateTaxPaidOnPurchases($business_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
                    'filing_deadline' => $this->getFilingDeadline($start_date, $period),
                    'filing_status' => $this->getFilingStatus($business_id, $year . '-Q' . $quarter),
                ];
            }
        }

        // If report_type is 'filing', return a printable view
        if ($report_type === 'filing') {
            $business = Business::find($business_id);
            $currency_symbol = session('currency')['symbol'] ?? ($business->currency->symbol ?? '');
            $currency_placement = session('business.currency_symbol_placement') ?? 'before';
            
            return view('advancedreports::tax-compliance.filing-report', compact(
                'filing_data', 
                'business', 
                'period', 
                'year', 
                'currency_symbol',
                'currency_placement'
            ));
        }

        return $filing_data;
    }

    /**
     * Get tax optimization insights
     */
    public function getTaxOptimizationInsights($business_id, $start_date, $end_date)
    {
        $insights = [];

        // 1. Tax rate comparison analysis
        $tax_efficiency = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                'tr.name as tax_name',
                'tr.amount as tax_rate',
                DB::raw('AVG(((tsl.unit_price - COALESCE(tsl.unit_price_before_discount, tsl.unit_price)) / NULLIF(tsl.unit_price, 0)) * 100) as avg_profit_margin'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price) as revenue'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price) as tax_amount')
            )
            ->groupBy('tr.id', 'tr.name', 'tr.amount')
            ->orderBy('revenue', 'desc')
            ->get();

        $insights[] = [
            'type' => 'tax_efficiency',
            'title' => 'Tax Rate Efficiency Analysis',
            'description' => 'Products with higher tax rates but better profit margins',
            'data' => $tax_efficiency,
            'recommendation' => 'Focus on promoting products with higher profit margins despite tax burden.'
        ];

        // 2. Seasonal tax optimization
        $seasonal_analysis = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('MONTHNAME(t.transaction_date) as month_name'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price) as tax_collected'),
                DB::raw('AVG(((tsl.unit_price - COALESCE(tsl.unit_price_before_discount, tsl.unit_price)) / NULLIF(tsl.unit_price, 0)) * 100) as avg_profit_margin')
            )
            ->groupBy('month', 'month_name')
            ->orderBy('month')
            ->get();

        $insights[] = [
            'type' => 'seasonal_optimization',
            'title' => 'Seasonal Tax Optimization',
            'description' => 'Tax collection patterns and profit margins by month',
            'data' => $seasonal_analysis,
            'recommendation' => 'Plan inventory and pricing strategies around seasonal tax patterns.'
        ];

        // 3. Input tax credit opportunities
        $credit_opportunities = DB::table('transactions as t')
            ->join('purchase_lines as pl', 't.id', '=', 'pl.transaction_id')
            ->leftJoin('tax_rates as tr', 'pl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                DB::raw('COUNT(CASE WHEN pl.tax_id IS NULL THEN 1 END) as untaxed_purchases'),
                DB::raw('COUNT(*) as total_purchases'),
                DB::raw('SUM(CASE WHEN pl.tax_id IS NULL THEN pl.quantity * pl.pp_without_discount ELSE 0 END) as untaxed_amount'),
                DB::raw('SUM(pl.quantity * pl.pp_without_discount) as total_purchase_amount')
            )
            ->first();

        $missed_credits = ($credit_opportunities->untaxed_amount * 0.18); // Assuming 18% average tax rate

        $insights[] = [
            'type' => 'credit_opportunities',
            'title' => 'Input Tax Credit Opportunities',
            'description' => 'Potential savings from claiming input tax credits',
            'data' => [
                'untaxed_purchases' => $credit_opportunities->untaxed_purchases,
                'untaxed_amount' => $credit_opportunities->untaxed_amount,
                'potential_savings' => $missed_credits,
                'coverage_rate' => (($credit_opportunities->total_purchases - $credit_opportunities->untaxed_purchases) / $credit_opportunities->total_purchases) * 100
            ],
            'recommendation' => 'Ensure all eligible purchases include proper tax documentation for credit claims.'
        ];

        return $insights;
    }

    /**
     * Export tax compliance report
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $report_type = $request->input('report_type', 'liability');
        $start_date = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $filename = 'tax_compliance_' . $report_type . '_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($business_id, $report_type, $start_date, $end_date) {
            $file = fopen('php://output', 'w');
            
            if ($report_type === 'liability') {
                // Tax liability export
                fputcsv($file, [
                    'Tax Name',
                    'Tax Rate (%)',
                    'Output Tax',
                    'Input Tax Credit',
                    'Net Liability',
                    'Sales Transactions',
                    'Purchase Transactions'
                ]);

                $liability_data = $this->getTaxLiabilityDetails(request()->merge([
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]));

                foreach ($liability_data as $row) {
                    fputcsv($file, [
                        $row['tax_name'],
                        $row['tax_rate'],
                        number_format($row['output_tax'], 2),
                        number_format($row['input_tax_credit'], 2),
                        number_format($row['net_liability'], 2),
                        $row['sales_transactions'],
                        $row['purchase_transactions']
                    ]);
                }
            } elseif ($report_type === 'filing') {
                // Filing assistance export
                fputcsv($file, [
                    'Period',
                    'Gross Sales',
                    'Taxable Sales',
                    'Tax Collected',
                    'Tax Paid on Purchases',
                    'Filing Deadline',
                    'Filing Status'
                ]);

                $filing_data = $this->getFilingAssistance(request()->merge([
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]));

                foreach ($filing_data as $row) {
                    fputcsv($file, [
                        $row['period'],
                        number_format($row['gross_sales'], 2),
                        number_format($row['taxable_sales'], 2),
                        number_format($row['tax_collected'], 2),
                        number_format($row['tax_paid_on_purchases'], 2),
                        $row['filing_deadline'],
                        $row['filing_status']
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Helper methods

    private function getTaxJurisdictions($business_id)
    {
        return TaxRate::where('business_id', $business_id)
                     ->where('deleted_at', null)
                     ->select('name', 'amount')
                     ->distinct()
                     ->get()
                     ->map(function ($tax) {
                         return [
                             'name' => $tax->name,
                             'rate' => $tax->amount,
                             'jurisdiction' => $this->determineJurisdiction($tax->name)
                         ];
                     })
                     ->groupBy('jurisdiction');
    }

    private function determineJurisdiction($tax_name)
    {
        // Determine jurisdiction based on tax name patterns
        if (strpos(strtolower($tax_name), 'gst') !== false || strpos(strtolower($tax_name), 'vat') !== false) {
            return 'Federal';
        } elseif (strpos(strtolower($tax_name), 'state') !== false || strpos(strtolower($tax_name), 'local') !== false) {
            return 'State/Local';
        } else {
            return 'Other';
        }
    }

    private function calculateTotalTaxCollected($business_id, $start_date, $end_date, $jurisdiction = 'all')
    {
        return DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->sum(DB::raw('tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price'));
    }

    private function calculateTaxLiability($business_id, $start_date, $end_date, $jurisdiction = 'all')
    {
        $output_tax = $this->calculateTotalTaxCollected($business_id, $start_date, $end_date, $jurisdiction);
        $input_tax = $this->calculateTaxPaidOnPurchases($business_id, $start_date, $end_date);
        
        return max(0, $output_tax - $input_tax);
    }

    private function calculateTaxPaidOnPurchases($business_id, $start_date, $end_date)
    {
        return DB::table('transactions as t')
            ->join('purchase_lines as pl', 't.id', '=', 'pl.transaction_id')
            ->join('tax_rates as tr', 'pl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->sum(DB::raw('pl.quantity * pl.pp_without_discount * tr.amount / 100'));
    }

    private function calculateGrossSales($business_id, $start_date, $end_date)
    {
        return DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('final_total');
    }

    private function calculateTaxableSales($business_id, $start_date, $end_date)
    {
        return DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->whereNotNull('tsl.tax_id')
            ->sum(DB::raw('tsl.quantity * tsl.unit_price'));
    }

    private function calculateComplianceScore($business_id, $start_date, $end_date)
    {
        // Simplified compliance score calculation
        $total_transactions = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->count();

        $compliant_transactions = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->whereNotNull('tsl.tax_id')
            ->distinct('t.id')
            ->count();

        return $total_transactions > 0 ? ($compliant_transactions / $total_transactions) * 100 : 100;
    }

    private function calculateTaxOptimizationSavings($business_id, $start_date, $end_date)
    {
        // Estimate potential savings from optimization
        $untaxed_purchases = DB::table('transactions as t')
            ->join('purchase_lines as pl', 't.id', '=', 'pl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->whereNull('pl.tax_id')
            ->sum(DB::raw('pl.quantity * pl.pp_without_discount'));

        return $untaxed_purchases * 0.18; // Assuming 18% potential savings
    }

    private function getTaxBreakdownByJurisdiction($business_id, $start_date, $end_date)
    {
        return DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                'tr.name as jurisdiction',
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price) as tax_amount')
            )
            ->groupBy('tr.name')
            ->get();
    }

    private function getMonthlyTaxTrend($business_id, $start_date, $end_date)
    {
        return DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->select(
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax - tsl.quantity * tsl.unit_price) as tax_amount')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getUpcomingFilingDeadlines($business_id)
    {
        // Generate upcoming filing deadlines (simplified)
        $deadlines = [];
        $current_date = Carbon::now();
        
        for ($i = 0; $i < 6; $i++) {
            $deadline_date = $current_date->copy()->addMonths($i)->endOfMonth()->addDays(15);
            $period = $current_date->copy()->addMonths($i)->format('F Y');
            
            $deadlines[] = [
                'period' => $period,
                'deadline' => $deadline_date->format('Y-m-d'),
                'days_remaining' => $current_date->diffInDays($deadline_date),
                'status' => $deadline_date->isPast() ? 'overdue' : ($deadline_date->diffInDays($current_date) <= 7 ? 'urgent' : 'upcoming')
            ];
        }
        
        return collect($deadlines)->sortBy('deadline')->values()->all();
    }

    private function calculateAuditRisk($business_id, $start_date, $end_date)
    {
        // Simplified audit risk calculation
        $risk_factors = 0;
        $total_factors = 5;

        // Factor 1: Tax compliance rate
        $compliance_score = $this->calculateComplianceScore($business_id, $start_date, $end_date);
        if ($compliance_score < 95) $risk_factors++;

        // Factor 2: Large tax discrepancies
        $tax_variance = abs($this->calculateTotalTaxCollected($business_id, $start_date, $end_date) - 
                           $this->calculateTaxPaidOnPurchases($business_id, $start_date, $end_date));
        if ($tax_variance > 50000) $risk_factors++;

        // Factor 3: Frequent tax rate changes
        $tax_rate_changes = TaxRate::where('business_id', $business_id)
                                  ->where('updated_at', '>=', $start_date)
                                  ->count();
        if ($tax_rate_changes > 10) $risk_factors++;

        // Factor 4: High volume transactions
        $transaction_count = DB::table('transactions')
            ->where('business_id', $business_id)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->count();
        if ($transaction_count > 10000) $risk_factors++;

        // Factor 5: Missing documentation
        $undocumented_transactions = DB::table('transactions')
            ->where('business_id', $business_id)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->whereNull('invoice_no')
            ->count();
        if ($undocumented_transactions > 100) $risk_factors++;

        $risk_percentage = ($risk_factors / $total_factors) * 100;
        
        return [
            'risk_percentage' => $risk_percentage,
            'risk_level' => $risk_percentage >= 60 ? 'High' : ($risk_percentage >= 30 ? 'Medium' : 'Low'),
            'risk_factors' => $risk_factors,
            'total_factors' => $total_factors
        ];
    }

    private function getFilingDeadline($period_start, $period_type)
    {
        switch ($period_type) {
            case 'monthly':
                return $period_start->copy()->endOfMonth()->addDays(15)->format('Y-m-d');
            case 'quarterly':
                return $period_start->copy()->endOfQuarter()->addDays(30)->format('Y-m-d');
            case 'annual':
                return $period_start->copy()->endOfYear()->addDays(90)->format('Y-m-d');
            default:
                return $period_start->copy()->endOfMonth()->addDays(15)->format('Y-m-d');
        }
    }

    private function getFilingStatus($business_id, $period_key)
    {
        // Simplified filing status check
        // In a real implementation, this would check against a filing log table
        return ['pending', 'filed', 'overdue'][array_rand(['pending', 'filed', 'overdue'])];
    }
}