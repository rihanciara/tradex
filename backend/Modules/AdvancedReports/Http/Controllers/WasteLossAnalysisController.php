<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\BusinessLocation;
use App\Contact;
use App\Category;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use Carbon\Carbon;

class WasteLossAnalysisController extends Controller
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display waste and loss analysis
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $locations = BusinessLocation::forDropdown($business_id, false);
        $business_locations = ['all' => __('All Locations')];
        if ($locations) {
            foreach ($locations as $key => $value) {
                $business_locations[$key] = $value;
            }
        }
        
        // Get categories
        $categories = Category::forDropdown($business_id, 'product');
        $categories = collect(['all' => __('All Categories')])->merge($categories)->toArray();
        
        // Get suppliers
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $suppliers = collect(['all' => __('All Suppliers')])->merge($suppliers)->toArray();

        $data = compact('business_locations', 'categories', 'suppliers');

        return view('advancedreports::waste-loss-analysis.index', $data);
    }

    /**
     * Get waste and loss analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $start_date = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->get('location_id');
        $category_id = $request->get('category_id');
        $supplier_id = $request->get('supplier_id');

        return response()->json([
            'expired_products' => $this->getExpiredProductsAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'damaged_goods' => $this->getDamagedGoodsAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'theft_shrinkage' => $this->getTheftShrinkageAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'loss_prevention' => $this->getLossPreventionInsights($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'summary_cards' => $this->getWasteLossSummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'trends' => $this->getWasteLossTrends($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
        ]);
    }

    /**
     * Get expired products analysis
     */
    private function getExpiredProductsAnalysis($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null)
    {
        $query = DB::table('purchase_lines as pl')
            ->join('variations as v', 'pl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('contacts as suppliers', 'p.supplier_id', '=', 'suppliers.id')
            ->where('p.business_id', $business_id)
            ->whereNotNull('pl.exp_date')
            ->where('pl.exp_date', '<=', Carbon::now())
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereRaw('pl.quantity > pl.quantity_sold + COALESCE(pl.quantity_adjusted, 0) + COALESCE(pl.quantity_returned, 0)'); // Still has remaining stock

        if ($location_id && $location_id !== 'all') {
            $query->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id !== 'all') {
            $query->where('p.category_id', $category_id);
        }

        if ($supplier_id && $supplier_id !== 'all') {
            $query->where('p.supplier_id', $supplier_id);
        }

        $expired_products = $query->select([
            'p.id as product_id',
            'p.name as product_name',
            'p.sku',
            'v.name as variation_name',
            'v.sub_sku',
            'c.name as category_name',
            'bl.name as location_name',
            'pl.exp_date',
            DB::raw('(pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) as qty_available'),
            'pl.purchase_price_inc_tax',
            DB::raw('((pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) * pl.purchase_price_inc_tax) as total_loss_value'),
            DB::raw('DATEDIFF(NOW(), pl.exp_date) as days_expired'),
            'suppliers.name as supplier_name'
        ])
        ->orderBy('pl.exp_date', 'desc')
        ->get();

        // Calculate expiry categories
        $expiry_categories = [
            'expired_today' => $expired_products->where('days_expired', 0)->count(),
            'expired_this_week' => $expired_products->where('days_expired', '<=', 7)->count(),
            'expired_this_month' => $expired_products->where('days_expired', '<=', 30)->count(),
            'expired_over_month' => $expired_products->where('days_expired', '>', 30)->count()
        ];

        // Get upcoming expirations (next 30 days)
        $upcoming_query = DB::table('purchase_lines as pl')
            ->join('variations as v', 'pl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('p.business_id', $business_id)
            ->whereNotNull('pl.exp_date')
            ->where('pl.exp_date', '>', Carbon::now())
            ->where('pl.exp_date', '<=', Carbon::now()->addDays(30))
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereRaw('pl.quantity > pl.quantity_sold + COALESCE(pl.quantity_adjusted, 0) + COALESCE(pl.quantity_returned, 0)');

        if ($location_id && $location_id !== 'all') {
            $upcoming_query->where('t.location_id', $location_id);
        }

        $upcoming_expirations = $upcoming_query
            ->select([
                'p.name as product_name',
                'pl.exp_date',
                DB::raw('(pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) as qty_available'),
                DB::raw('DATEDIFF(pl.exp_date, NOW()) as days_until_expiry'),
                DB::raw('((pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) * pl.purchase_price_inc_tax) as potential_loss_value')
            ])
            ->orderBy('pl.exp_date', 'asc')
            ->get();

        return [
            'expired_products' => $expired_products->take(50), // Limit for performance
            'expiry_categories' => $expiry_categories,
            'upcoming_expirations' => $upcoming_expirations->take(20),
            'total_expired_value' => $expired_products->sum('total_loss_value'),
            'total_expired_quantity' => $expired_products->sum('qty_available')
        ];
    }

    /**
     * Get damaged goods analysis
     */
    private function getDamagedGoodsAnalysis($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null)
    {
        // Get stock adjustment transactions (type = 'stock_adjustment' with reason damage/loss)
        $query = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->join('variations as v', 'sal.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->where('sal.quantity', '<', 0); // Negative adjustments (reductions)

        if ($location_id && $location_id !== 'all') {
            $query->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id !== 'all') {
            $query->where('p.category_id', $category_id);
        }

        $damaged_goods = $query->select([
            'p.id as product_id',
            'p.name as product_name',
            'p.sku',
            'v.name as variation_name',
            'v.sub_sku',
            'c.name as category_name',
            'bl.name as location_name',
            't.transaction_date as damage_date',
            'sal.quantity as damaged_quantity',
            DB::raw('COALESCE(v.default_purchase_price, sal.unit_price, 0) as unit_cost'),
            DB::raw('ABS(sal.quantity * COALESCE(v.default_purchase_price, sal.unit_price, 0)) as damage_value'),
            't.additional_notes as reason'
        ])
        ->orderBy('t.transaction_date', 'desc')
        ->get();

        // Calculate damage categories by reason/cause
        $damage_categories = [
            'physical_damage' => 0,
            'spoilage' => 0,
            'theft' => 0,
            'other' => 0
        ];

        foreach ($damaged_goods as $damage) {
            $reason = strtolower($damage->reason ?? '');
            if (strpos($reason, 'damage') !== false || strpos($reason, 'broken') !== false) {
                $damage_categories['physical_damage']++;
            } elseif (strpos($reason, 'spoil') !== false || strpos($reason, 'expire') !== false) {
                $damage_categories['spoilage']++;
            } elseif (strpos($reason, 'theft') !== false || strpos($reason, 'steal') !== false) {
                $damage_categories['theft']++;
            } else {
                $damage_categories['other']++;
            }
        }

        return [
            'damaged_goods' => $damaged_goods->take(50),
            'damage_categories' => $damage_categories,
            'total_damage_value' => $damaged_goods->sum('damage_value'),
            'total_damaged_quantity' => abs($damaged_goods->sum('damaged_quantity'))
        ];
    }

    /**
     * Get theft/shrinkage analysis
     */
    private function getTheftShrinkageAnalysis($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null)
    {
        // Calculate inventory discrepancies by comparing expected vs actual stock
        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->join('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
            ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('p.business_id', $business_id);

        if ($location_id && $location_id !== 'all') {
            $query->where('vld.location_id', $location_id);
        }

        if ($category_id && $category_id !== 'all') {
            $query->where('p.category_id', $category_id);
        }

        // Get stock movements for shrinkage calculation
        $stock_movements = $query->select([
            'p.id as product_id',
            'p.name as product_name',
            'p.sku',
            'c.name as category_name',
            'bl.name as location_name',
            'vld.qty_available as current_stock',
            'v.default_purchase_price as unit_cost'
        ])
        ->get();

        // Calculate potential shrinkage by analyzing stock adjustment patterns
        $shrinkage_query = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->join('variations as v', 'sal.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->where(function($query) {
                $query->whereNull('t.additional_notes')
                      ->orWhere('t.additional_notes', 'like', '%shrinkage%')
                      ->orWhere('t.additional_notes', 'like', '%theft%')
                      ->orWhere('t.additional_notes', 'like', '%missing%');
            });

        if ($location_id && $location_id !== 'all') {
            $shrinkage_query->where('t.location_id', $location_id);
        }

        $shrinkage_data = $shrinkage_query->select([
            'p.name as product_name',
            't.transaction_date',
            'sal.quantity as shrinkage_quantity',
            'sal.unit_price',
            DB::raw('ABS(sal.quantity * sal.unit_price) as shrinkage_value'),
            't.additional_notes as reason'
        ])
        ->orderBy('t.transaction_date', 'desc')
        ->get();

        // Calculate shrinkage patterns
        $shrinkage_by_month = $shrinkage_data->groupBy(function($item) {
            return Carbon::parse($item->transaction_date)->format('Y-m');
        })->map(function($group) {
            return [
                'total_value' => $group->sum('shrinkage_value'),
                'total_quantity' => abs($group->sum('shrinkage_quantity')),
                'incident_count' => $group->count()
            ];
        });

        return [
            'shrinkage_incidents' => $shrinkage_data->take(50),
            'shrinkage_by_month' => $shrinkage_by_month,
            'high_risk_products' => $this->identifyHighRiskProducts($business_id, $start_date, $end_date, $location_id),
            'total_shrinkage_value' => $shrinkage_data->sum('shrinkage_value'),
            'shrinkage_rate' => $this->calculateShrinkageRate($business_id, $start_date, $end_date)
        ];
    }

    /**
     * Identify high-risk products for theft/shrinkage
     */
    private function identifyHighRiskProducts($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->join('products as p', 'sal.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($location_id && $location_id !== 'all') {
            $query->where('t.location_id', $location_id);
        }

        return $query->select([
            'p.name as product_name',
            'p.sku',
            DB::raw('COUNT(*) as incident_count'),
            DB::raw('SUM(ABS(sal.quantity)) as total_quantity_lost'),
            DB::raw('SUM(ABS(sal.quantity * sal.unit_price)) as total_value_lost'),
            DB::raw('AVG(ABS(sal.quantity * sal.unit_price)) as avg_loss_per_incident')
        ])
        ->groupBy('p.id', 'p.name', 'p.sku')
        ->having('incident_count', '>=', 2) // Products with multiple incidents
        ->orderBy('total_value_lost', 'desc')
        ->take(20)
        ->get();
    }

    /**
     * Calculate overall shrinkage rate
     */
    private function calculateShrinkageRate($business_id, $start_date, $end_date)
    {
        $total_sales = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereBetween('transaction_date', [$start_date, $end_date])
            ->sum('final_total');

        $total_shrinkage = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->sum(DB::raw('ABS(sal.quantity * sal.unit_price)'));

        return $total_sales > 0 ? ($total_shrinkage / $total_sales) * 100 : 0;
    }

    /**
     * Get loss prevention insights
     */
    private function getLossPreventionInsights($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null)
    {
        // Identify patterns and recommendations for loss prevention
        $insights = [
            'top_loss_categories' => $this->getTopLossCategories($business_id, $start_date, $end_date, $location_id),
            'loss_by_location' => $this->getLossByLocation($business_id, $start_date, $end_date),
            'seasonal_patterns' => $this->getSeasonalLossPatterns($business_id, $start_date, $end_date),
            'recommendations' => $this->generateLossPreventionRecommendations($business_id, $start_date, $end_date, $location_id)
        ];

        return $insights;
    }

    /**
     * Get top loss categories
     */
    private function getTopLossCategories($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->join('products as p', 'sal.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if ($location_id && $location_id !== 'all') {
            $query->where('t.location_id', $location_id);
        }

        return $query->select([
            'c.name as category_name',
            DB::raw('COUNT(DISTINCT p.id) as affected_products'),
            DB::raw('COUNT(*) as total_incidents'),
            DB::raw('SUM(ABS(sal.quantity)) as total_quantity_lost'),
            DB::raw('SUM(ABS(sal.quantity * sal.unit_price)) as total_value_lost')
        ])
        ->groupBy('c.id', 'c.name')
        ->orderBy('total_value_lost', 'desc')
        ->take(10)
        ->get();
    }

    /**
     * Get loss by location
     */
    private function getLossByLocation($business_id, $start_date, $end_date)
    {
        return DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                'bl.name as location_name',
                DB::raw('COUNT(*) as total_incidents'),
                DB::raw('SUM(ABS(sal.quantity * sal.unit_price)) as total_loss_value')
            ])
            ->groupBy('bl.id', 'bl.name')
            ->orderBy('total_loss_value', 'desc')
            ->get();
    }

    /**
     * Get seasonal loss patterns
     */
    private function getSeasonalLossPatterns($business_id, $start_date, $end_date)
    {
        $monthly_losses = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                DB::raw('YEAR(t.transaction_date) as year'),
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('COUNT(*) as incident_count'),
                DB::raw('SUM(ABS(sal.quantity * sal.unit_price)) as loss_value')
            ])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return $monthly_losses->map(function($loss) {
            return [
                'period' => Carbon::createFromDate($loss->year, $loss->month, 1)->format('M Y'),
                'incident_count' => $loss->incident_count,
                'loss_value' => $loss->loss_value
            ];
        });
    }

    /**
     * Generate loss prevention recommendations
     */
    private function generateLossPreventionRecommendations($business_id, $start_date, $end_date, $location_id = null)
    {
        $recommendations = [];

        // Check for high expiry rates
        $expiry_rate = $this->calculateExpiryRate($business_id, $start_date, $end_date);
        if ($expiry_rate > 5) { // More than 5% expiry rate
            $recommendations[] = [
                'type' => 'FIFO_IMPLEMENTATION',
                'priority' => 'HIGH',
                'title' => 'Implement FIFO System',
                'description' => "High expiry rate detected ({$expiry_rate}%). Consider implementing First-In-First-Out inventory management.",
                'potential_savings' => $this->calculatePotentialExpirySavings($business_id)
            ];
        }

        // Check for shrinkage patterns
        $shrinkage_rate = $this->calculateShrinkageRate($business_id, $start_date, $end_date);
        if ($shrinkage_rate > 2) { // More than 2% shrinkage rate
            $recommendations[] = [
                'type' => 'SECURITY_ENHANCEMENT',
                'priority' => 'HIGH',
                'title' => 'Enhance Security Measures',
                'description' => "Shrinkage rate of {$shrinkage_rate}% is above industry average. Consider security improvements.",
                'potential_savings' => $this->calculatePotentialShrinkageSavings($business_id, $start_date, $end_date)
            ];
        }

        // Check for damaged goods patterns
        $damage_rate = $this->calculateDamageRate($business_id, $start_date, $end_date);
        if ($damage_rate > 3) {
            $recommendations[] = [
                'type' => 'HANDLING_IMPROVEMENT',
                'priority' => 'MEDIUM',
                'title' => 'Improve Product Handling',
                'description' => "Damage rate of {$damage_rate}% suggests handling process improvements are needed.",
                'potential_savings' => $this->calculatePotentialDamageSavings($business_id, $start_date, $end_date)
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate expiry rate
     */
    private function calculateExpiryRate($business_id, $start_date, $end_date)
    {
        $total_inventory_value = DB::table('purchase_lines as pl')
            ->join('variations as v', 'pl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->where('p.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereRaw('pl.quantity > pl.quantity_sold + COALESCE(pl.quantity_adjusted, 0) + COALESCE(pl.quantity_returned, 0)')
            ->sum(DB::raw('(pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) * pl.purchase_price_inc_tax'));

        $expired_value = DB::table('purchase_lines as pl')
            ->join('variations as v', 'pl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->where('p.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereNotNull('pl.exp_date')
            ->where('pl.exp_date', '<=', Carbon::now())
            ->whereRaw('pl.quantity > pl.quantity_sold + COALESCE(pl.quantity_adjusted, 0) + COALESCE(pl.quantity_returned, 0)')
            ->sum(DB::raw('(pl.quantity - pl.quantity_sold - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) * pl.purchase_price_inc_tax'));

        return $total_inventory_value > 0 ? ($expired_value / $total_inventory_value) * 100 : 0;
    }

    /**
     * Calculate damage rate
     */
    private function calculateDamageRate($business_id, $start_date, $end_date)
    {
        // Implementation similar to other rate calculations
        return 1.5; // Placeholder
    }

    /**
     * Calculate potential savings
     */
    private function calculatePotentialExpirySavings($business_id)
    {
        return 5000; // Placeholder calculation
    }

    private function calculatePotentialShrinkageSavings($business_id, $start_date, $end_date)
    {
        return 3000; // Placeholder calculation
    }

    private function calculatePotentialDamageSavings($business_id, $start_date, $end_date)
    {
        return 2000; // Placeholder calculation
    }

    /**
     * Get waste and loss summary cards
     */
    private function getWasteLossSummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        $expired_analysis = $this->getExpiredProductsAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id);
        $damaged_analysis = $this->getDamagedGoodsAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id);
        $shrinkage_analysis = $this->getTheftShrinkageAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id);

        return [
            'total_expired_value' => $expired_analysis['total_expired_value'],
            'total_damaged_value' => $damaged_analysis['total_damage_value'],
            'total_shrinkage_value' => $shrinkage_analysis['total_shrinkage_value'],
            'total_loss_value' => $expired_analysis['total_expired_value'] + $damaged_analysis['total_damage_value'] + $shrinkage_analysis['total_shrinkage_value'],
            'expired_products_count' => count($expired_analysis['expired_products']),
            'damaged_incidents_count' => count($damaged_analysis['damaged_goods']),
            'shrinkage_incidents_count' => count($shrinkage_analysis['shrinkage_incidents']),
            'upcoming_expirations' => count($expired_analysis['upcoming_expirations'])
        ];
    }

    /**
     * Get waste and loss trends
     */
    private function getWasteLossTrends($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        // Monthly trends for different types of losses
        $monthly_trends = DB::table('transactions as t')
            ->join('stock_adjustment_lines as sal', 't.id', '=', 'sal.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('t.adjustment_type', 'decrease')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->select([
                DB::raw('YEAR(t.transaction_date) as year'),
                DB::raw('MONTH(t.transaction_date) as month'),
                DB::raw('SUM(ABS(sal.quantity * sal.unit_price)) as total_loss'),
                DB::raw('COUNT(*) as incident_count')
            ])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return $monthly_trends->map(function($trend) {
            return [
                'period' => Carbon::createFromDate($trend->year, $trend->month, 1)->format('M Y'),
                'total_loss' => $trend->total_loss,
                'incident_count' => $trend->incident_count
            ];
        });
    }
}