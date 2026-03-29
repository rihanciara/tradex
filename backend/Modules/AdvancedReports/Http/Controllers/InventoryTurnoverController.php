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
use Carbon\Carbon;

class InventoryTurnoverController extends Controller
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
     * Display inventory turnover report index page
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations
        $locations = BusinessLocation::forDropdown($business_id, true);
        $business_locations = ['all' => __('All')];
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
        
        return view('advancedreports::inventory-turnover.index', $data);
    }

    /**
     * Get inventory turnover analytics data
     */
    public function getAnalytics(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $category_id = $request->get('category_id');
        $supplier_id = $request->get('supplier_id');

        // Set default date range if not provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = Carbon::now()->format('Y-m-d');
            $start_date = Carbon::now()->subMonths(6)->format('Y-m-d');
        }

        return response()->json([
            'stock_rotation_analysis' => $this->getStockRotationAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'movement_classification' => $this->getMovementClassification($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'inventory_aging' => $this->getInventoryAging($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'stock_recommendations' => $this->getStockRecommendations($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'summary_cards' => $this->getInventorySummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id),
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Get stock rotation analysis
     */
    private function getStockRotationAnalysis($business_id, $start_date, $end_date, $location_id = null, $category_id = null, $supplier_id = null)
    {
        // Build base query for inventory turnover calculation
        $query = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('contacts as sup', 'p.created_by', '=', 'sup.id') // Assuming supplier relationship
            ->where('p.business_id', $business_id)
            ->where('p.type', '!=', 'modifier');

        if ($category_id && $category_id != 'all') {
            $query->where('p.category_id', $category_id);
        }

        // Get current stock levels
        $stockQuery = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id);

        if ($location_id && $location_id != 'all') {
            $stockQuery->where('vld.location_id', $location_id);
        }

        // Get sales data for turnover calculation
        $salesData = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date);

        if ($location_id && $location_id != 'all') {
            $salesData->where('t.location_id', $location_id);
        }

        if ($category_id && $category_id != 'all') {
            $salesData->where('p.category_id', $category_id);
        }

        // Calculate turnover metrics
        $turnoverData = $salesData->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                COALESCE(c.name, "Uncategorized") as category_name,
                COALESCE(b.name, "No Brand") as brand_name,
                SUM(tsl.quantity) as total_sold,
                AVG(tsl.unit_price_inc_tax) as avg_selling_price,
                SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_revenue,
                COUNT(DISTINCT t.transaction_date) as sales_days,
                COUNT(DISTINCT t.id) as total_transactions,
                MIN(t.transaction_date) as first_sale_date,
                MAX(t.transaction_date) as last_sale_date
            ')
            ->groupBy('p.id', 'p.name', 'p.sku', 'c.name', 'b.name')
            ->get();

        // Get current stock for each product with latest purchase price
        $currentStock = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('purchase_lines as pl', function($join) {
                $join->on('pl.variation_id', '=', 'pv.id')
                     ->whereRaw('pl.id = (SELECT id FROM purchase_lines pl2 WHERE pl2.variation_id = pv.id ORDER BY pl2.created_at DESC LIMIT 1)');
            })
            ->where('p.business_id', $business_id)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('vld.location_id', $location_id);
            })
            ->when($category_id && $category_id != 'all', function($q) use ($category_id) {
                return $q->where('p.category_id', $category_id);
            })
            ->selectRaw('
                p.id as product_id,
                SUM(vld.qty_available) as current_stock,
                COALESCE(AVG(pl.purchase_price), 0) as avg_cost
            ')
            ->groupBy('p.id')
            ->get()
            ->keyBy('product_id');

        // Calculate turnover ratios and metrics
        $periodDays = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;

        $analysisData = $turnoverData->map(function($item) use ($currentStock, $periodDays) {
            $stock = $currentStock->get($item->product_id);
            $currentStockQty = $stock ? $stock->current_stock : 0;
            $avgCost = $stock ? $stock->avg_cost : 0;

            // Calculate inventory turnover ratio (COGS / Average Inventory)
            // Simplified: Total Sold / Average Stock (assuming current stock as average)
            $turnoverRatio = $currentStockQty > 0 ? round($item->total_sold / $currentStockQty, 2) : 0;
            
            // Days inventory outstanding (DIO) = (Average Inventory / COGS) * Period Days
            $daysInventoryOutstanding = $item->total_sold > 0 ? round(($currentStockQty / $item->total_sold) * $periodDays, 1) : 999;
            
            // Velocity (units per day)
            $velocity = $periodDays > 0 ? round($item->total_sold / $periodDays, 2) : 0;
            
            // Stock cover (days)
            $stockCover = $velocity > 0 ? round($currentStockQty / $velocity, 1) : 999;

            // Classification
            if ($turnoverRatio >= 4 || $velocity >= 1) {
                $classification = 'Fast Moving';
                $color = '#28a745'; // Green
            } elseif ($turnoverRatio >= 2 || $velocity >= 0.5) {
                $classification = 'Medium Moving';
                $color = '#ffc107'; // Yellow
            } elseif ($turnoverRatio >= 0.5 || $velocity >= 0.1) {
                $classification = 'Slow Moving';
                $color = '#fd7e14'; // Orange
            } else {
                $classification = 'Dead Stock';
                $color = '#dc3545'; // Red
            }

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'category_name' => $item->category_name,
                'brand_name' => $item->brand_name,
                'current_stock' => $currentStockQty,
                'total_sold' => $item->total_sold,
                'total_revenue' => $item->total_revenue,
                'avg_selling_price' => $item->avg_selling_price,
                'avg_cost' => $avgCost,
                'turnover_ratio' => $turnoverRatio,
                'days_inventory_outstanding' => $daysInventoryOutstanding,
                'velocity' => $velocity,
                'stock_cover_days' => $stockCover,
                'total_transactions' => $item->total_transactions,
                'sales_days' => $item->sales_days,
                'first_sale_date' => $item->first_sale_date,
                'last_sale_date' => $item->last_sale_date,
                'classification' => $classification,
                'classification_color' => $color,
                'stock_value' => $currentStockQty * $avgCost
            ];
        })->sortByDesc('turnover_ratio')->values();

        return $analysisData->toArray();
    }

    /**
     * Get movement classification (Fast/Medium/Slow/Dead stock)
     */
    private function getMovementClassification($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        $rotationData = $this->getStockRotationAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id);
        
        // Group by classification
        $classifications = collect($rotationData)->groupBy('classification')->map(function($group, $classification) {
            return [
                'classification' => $classification,
                'count' => $group->count(),
                'total_stock_value' => $group->sum('stock_value'),
                'total_revenue' => $group->sum('total_revenue'),
                'avg_turnover_ratio' => $group->avg('turnover_ratio'),
                'avg_velocity' => $group->avg('velocity'),
                'products' => $group->take(10)->toArray() // Top 10 products per classification
            ];
        })->values();

        // Movement trends by category
        $categoryMovement = collect($rotationData)->groupBy('category_name')->map(function($group, $category) {
            $fastMoving = $group->where('classification', 'Fast Moving')->count();
            $mediumMoving = $group->where('classification', 'Medium Moving')->count();
            $slowMoving = $group->where('classification', 'Slow Moving')->count();
            $deadStock = $group->where('classification', 'Dead Stock')->count();
            $total = $group->count();

            return [
                'category' => $category,
                'total_products' => $total,
                'fast_moving' => $fastMoving,
                'medium_moving' => $mediumMoving,
                'slow_moving' => $slowMoving,
                'dead_stock' => $deadStock,
                'fast_moving_percentage' => $total > 0 ? round(($fastMoving / $total) * 100, 1) : 0,
                'slow_dead_percentage' => $total > 0 ? round((($slowMoving + $deadStock) / $total) * 100, 1) : 0
            ];
        })->values();

        return [
            'classifications' => $classifications->toArray(),
            'category_movement' => $categoryMovement->toArray(),
            'top_performers' => collect($rotationData)->where('classification', 'Fast Moving')->take(10)->toArray(),
            'worst_performers' => collect($rotationData)->where('classification', 'Dead Stock')->take(10)->toArray()
        ];
    }

    /**
     * Get inventory aging report
     */
    private function getInventoryAging($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        // Get purchase history to calculate aging
        $purchaseData = DB::table('purchase_lines as pl')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'pl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($category_id && $category_id != 'all', function($q) use ($category_id) {
                return $q->where('p.category_id', $category_id);
            })
            ->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                COALESCE(c.name, "Uncategorized") as category_name,
                t.transaction_date as purchase_date,
                pl.quantity as purchased_qty,
                pl.purchase_price_inc_tax as purchase_price,
                DATEDIFF(CURDATE(), t.transaction_date) as days_in_stock
            ')
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        // Get current stock to match with purchase history
        $currentStock = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('vld.location_id', $location_id);
            })
            ->when($category_id && $category_id != 'all', function($q) use ($category_id) {
                return $q->where('p.category_id', $category_id);
            })
            ->selectRaw('
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                SUM(vld.qty_available) as current_stock
            ')
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->having('current_stock', '>', 0)
            ->get();

        // Calculate aging buckets
        $agingData = $currentStock->map(function($stock) use ($purchaseData) {
            // Get most recent purchase for this product
            $recentPurchase = $purchaseData->where('product_id', $stock->product_id)->first();
            
            if (!$recentPurchase) {
                $daysInStock = 365; // Default to 1 year if no purchase history
                $purchaseDate = null;
                $purchasePrice = 0;
            } else {
                $daysInStock = $recentPurchase->days_in_stock;
                $purchaseDate = $recentPurchase->purchase_date;
                $purchasePrice = $recentPurchase->purchase_price;
            }

            // Determine aging bucket
            if ($daysInStock <= 30) {
                $agingBucket = '0-30 Days';
                $riskLevel = 'Low';
                $color = '#28a745';
            } elseif ($daysInStock <= 60) {
                $agingBucket = '31-60 Days';
                $riskLevel = 'Low';
                $color = '#6f42c1';
            } elseif ($daysInStock <= 90) {
                $agingBucket = '61-90 Days';
                $riskLevel = 'Medium';
                $color = '#ffc107';
            } elseif ($daysInStock <= 180) {
                $agingBucket = '91-180 Days';
                $riskLevel = 'Medium';
                $color = '#fd7e14';
            } elseif ($daysInStock <= 365) {
                $agingBucket = '181-365 Days';
                $riskLevel = 'High';
                $color = '#dc3545';
            } else {
                $agingBucket = '365+ Days';
                $riskLevel = 'Critical';
                $color = '#6c757d';
            }

            return [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product_name,
                'product_sku' => $stock->product_sku,
                'current_stock' => $stock->current_stock,
                'days_in_stock' => $daysInStock,
                'purchase_date' => $purchaseDate,
                'purchase_price' => $purchasePrice,
                'stock_value' => $stock->current_stock * $purchasePrice,
                'aging_bucket' => $agingBucket,
                'risk_level' => $riskLevel,
                'color' => $color
            ];
        });

        // Group by aging buckets
        $agingBuckets = $agingData->groupBy('aging_bucket')->map(function($group, $bucket) {
            return [
                'bucket' => $bucket,
                'count' => $group->count(),
                'total_stock_value' => $group->sum('stock_value'),
                'total_quantity' => $group->sum('current_stock'),
                'avg_days' => $group->avg('days_in_stock'),
                'products' => $group->sortByDesc('stock_value')->take(10)->values()->toArray()
            ];
        })->values();

        // Risk analysis
        $riskAnalysis = $agingData->count() > 0 ? $agingData->groupBy('risk_level')->map(function($group, $risk) use ($agingData) {
            return [
                'risk_level' => $risk,
                'count' => $group->count(),
                'total_stock_value' => $group->sum('stock_value'),
                'percentage' => round(($group->count() / $agingData->count()) * 100, 1)
            ];
        })->values() : collect([]);

        return [
            'aging_buckets' => $agingBuckets->toArray(),
            'risk_analysis' => $riskAnalysis->toArray(),
            'oldest_stock' => $agingData->sortByDesc('days_in_stock')->take(10)->values()->toArray(),
            'highest_value_aged' => $agingData->where('days_in_stock', '>', 90)->sortByDesc('stock_value')->take(10)->values()->toArray()
        ];
    }

    /**
     * Get optimal stock level recommendations
     */
    private function getStockRecommendations($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        $rotationData = collect($this->getStockRotationAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id));
        
        $recommendations = $rotationData->map(function($item) {
            $currentStock = $item['current_stock'];
            $velocity = $item['velocity']; // Units per day
            $turnoverRatio = $item['turnover_ratio'];
            
            // Calculate safety stock (typically 1-2 weeks of average demand)
            $safetyStock = max(ceil($velocity * 14), 1); // 2 weeks safety
            
            // Calculate reorder point (lead time assumed as 7 days + safety stock)
            $leadTimeDays = 7;
            $reorderPoint = ceil($velocity * $leadTimeDays) + $safetyStock;
            
            // Calculate economic order quantity (simplified)
            // EOQ = sqrt(2 * Annual Demand * Order Cost / Holding Cost)
            // Simplified: Based on velocity and turnover
            $monthlyDemand = $velocity * 30;
            $optimalOrderQty = max(ceil($monthlyDemand), 10); // Minimum order of 10
            
            // Recommendation logic
            if ($currentStock <= $reorderPoint && $velocity > 0) {
                $action = 'REORDER NOW';
                $priority = 'High';
                $color = '#dc3545';
                $recommendedQty = $optimalOrderQty;
                $reason = 'Stock below reorder point';
            } elseif ($currentStock >= ($velocity * 90) && $velocity > 0) {
                $action = 'REDUCE STOCK';
                $priority = 'Medium';
                $color = '#fd7e14';
                $recommendedQty = ceil($velocity * 60); // 2 months stock
                $reason = 'Overstocked - reduce to 2 months supply';
            } elseif ($item['classification'] == 'Dead Stock') {
                $action = 'LIQUIDATE';
                $priority = 'High';
                $color = '#6c757d';
                $recommendedQty = 0;
                $reason = 'No sales activity - consider liquidation';
            } elseif ($item['classification'] == 'Slow Moving' && $currentStock > ($velocity * 60)) {
                $action = 'REDUCE STOCK';
                $priority = 'Low';
                $color = '#ffc107';
                $recommendedQty = ceil($velocity * 45); // 1.5 months stock
                $reason = 'Slow moving with excess stock';
            } else {
                $action = 'MAINTAIN';
                $priority = 'Low';
                $color = '#28a745';
                $recommendedQty = $currentStock;
                $reason = 'Stock levels are appropriate';
            }
            
            // Calculate potential savings/costs
            if ($action == 'REDUCE STOCK') {
                $excessStock = max($currentStock - $recommendedQty, 0);
                $potentialSavings = $excessStock * $item['avg_cost'];
            } elseif ($action == 'REORDER NOW') {
                $stockoutRisk = ($reorderPoint - $currentStock) * $item['avg_selling_price'];
                $potentialSavings = -$stockoutRisk; // Negative indicates lost sales risk
            } else {
                $potentialSavings = 0;
            }

            return [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'category_name' => $item['category_name'],
                'current_stock' => $currentStock,
                'velocity' => $velocity,
                'safety_stock' => $safetyStock,
                'reorder_point' => $reorderPoint,
                'optimal_order_qty' => $optimalOrderQty,
                'recommended_qty' => $recommendedQty,
                'action' => $action,
                'priority' => $priority,
                'color' => $color,
                'reason' => $reason,
                'potential_savings' => $potentialSavings,
                'turnover_ratio' => $turnoverRatio,
                'classification' => $item['classification'],
                'stock_value' => $item['stock_value']
            ];
        });

        // Group recommendations by action type
        $actionSummary = $recommendations->groupBy('action')->map(function($group, $action) {
            return [
                'action' => $action,
                'count' => $group->count(),
                'total_stock_value' => $group->sum('stock_value'),
                'potential_impact' => $group->sum('potential_savings'),
                'products' => $group->sortByDesc('potential_savings')->take(10)->values()->toArray()
            ];
        })->values();

        // Priority recommendations
        $highPriority = $recommendations->where('priority', 'High')->sortByDesc('potential_savings')->take(20)->values();
        $mediumPriority = $recommendations->where('priority', 'Medium')->sortByDesc('potential_savings')->take(15)->values();

        return [
            'recommendations' => $recommendations->sortByDesc('potential_savings')->take(50)->values()->toArray(),
            'action_summary' => $actionSummary->toArray(),
            'high_priority' => $highPriority->toArray(),
            'medium_priority' => $mediumPriority->toArray(),
            'total_potential_savings' => $recommendations->sum('potential_savings')
        ];
    }

    /**
     * Get inventory summary cards
     */
    private function getInventorySummaryCards($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id)
    {
        // Get basic inventory metrics
        $inventoryQuery = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftJoin('purchase_lines as pl', function($join) {
                $join->on('pl.variation_id', '=', 'pv.id')
                     ->whereRaw('pl.id = (SELECT id FROM purchase_lines pl2 WHERE pl2.variation_id = pv.id ORDER BY pl2.created_at DESC LIMIT 1)');
            })
            ->where('p.business_id', $business_id)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('vld.location_id', $location_id);
            })
            ->when($category_id && $category_id != 'all', function($q) use ($category_id) {
                return $q->where('p.category_id', $category_id);
            });

        $inventorySummary = $inventoryQuery->selectRaw('
                COUNT(DISTINCT p.id) as total_products,
                SUM(vld.qty_available) as total_stock_qty,
                SUM(vld.qty_available * COALESCE(pl.purchase_price, 0)) as total_stock_value,
                AVG(COALESCE(pl.purchase_price, 0)) as avg_cost_per_unit
            ')->first();

        // Get sales velocity
        $salesVelocity = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $start_date)
            ->whereDate('t.transaction_date', '<=', $end_date)
            ->when($location_id && $location_id != 'all', function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->when($category_id && $category_id != 'all', function($q) use ($category_id) {
                return $q->where('p.category_id', $category_id);
            })
            ->selectRaw('
                SUM(tsl.quantity) as total_sold,
                COUNT(DISTINCT p.id) as products_sold
            ')->first();

        $periodDays = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;
        $avgDailyVelocity = $periodDays > 0 ? ($salesVelocity->total_sold ?? 0) / $periodDays : 0;

        // Calculate turnover metrics
        $rotationData = collect($this->getStockRotationAnalysis($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id));
        $avgTurnoverRatio = $rotationData->avg('turnover_ratio') ?? 0;
        
        // Movement classification counts
        $fastMoving = $rotationData->where('classification', 'Fast Moving')->count();
        $deadStock = $rotationData->where('classification', 'Dead Stock')->count();
        
        return [
            'total_products' => $inventorySummary->total_products ?? 0,
            'total_stock_qty' => $inventorySummary->total_stock_qty ?? 0,
            'total_stock_value' => $inventorySummary->total_stock_value ?? 0,
            'avg_turnover_ratio' => round($avgTurnoverRatio, 2),
            'avg_daily_velocity' => round($avgDailyVelocity, 2),
            'fast_moving_count' => $fastMoving,
            'dead_stock_count' => $deadStock,
            'products_sold' => $salesVelocity->products_sold ?? 0,
            'formatted_stock_value' => $this->businessUtil->num_f($inventorySummary->total_stock_value ?? 0, true),
            'stock_health_percentage' => $rotationData->count() > 0 ? round(($fastMoving / $rotationData->count()) * 100, 1) : 0
        ];
    }

    /**
     * Get recommendations data for DataTable
     */
    public function getRecommendationsData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $location_id = $request->get('location_id', 'all');
        $category_id = $request->get('category_id', 'all');
        $supplier_id = $request->get('supplier_id', 'all');
        $action_filter = $request->get('action_filter');

        $stockRecommendations = $this->getStockRecommendations($business_id, $start_date, $end_date, $location_id, $category_id, $supplier_id);
        $recommendations = collect($stockRecommendations['recommendations'] ?? []);

        // Apply action filter if provided
        if ($action_filter && $action_filter !== '') {
            $recommendations = $recommendations->filter(function($rec) use ($action_filter) {
                return $rec['action'] === $action_filter;
            });
        }

        return DataTables::of($recommendations)
            ->addColumn('product', function($rec) {
                return '<strong>' . $rec['product_name'] . '</strong><br><small class="text-muted">' . $rec['product_sku'] . '</small>';
            })
            ->addColumn('action_badge', function($rec) {
                $colorClass = 'bg-gray';
                if ($rec['action'] === 'REORDER NOW') $colorClass = 'bg-red';
                else if ($rec['action'] === 'REDUCE STOCK') $colorClass = 'bg-yellow';
                else if ($rec['action'] === 'LIQUIDATE') $colorClass = 'bg-gray';
                else if ($rec['action'] === 'MAINTAIN') $colorClass = 'bg-green';

                return '<span class="badge ' . $colorClass . '">' . $rec['action'] . '</span>';
            })
            ->addColumn('velocity_formatted', function($rec) {
                return number_format($rec['velocity'], 2) . '/day';
            })
            ->addColumn('turnover_formatted', function($rec) {
                return number_format($rec['turnover_ratio'], 2);
            })
            ->addColumn('impact_formatted', function($rec) {
                $impact = $rec['potential_savings'] >= 0 ?
                    '+' . number_format($rec['potential_savings']) :
                    '-' . number_format(abs($rec['potential_savings']));
                $color = $rec['potential_savings'] >= 0 ? 'green' : 'red';
                return '<span style="color: ' . $color . '">' . $impact . '</span>';
            })
            ->addColumn('reason_short', function($rec) {
                return '<small>' . $rec['reason'] . '</small>';
            })
            ->rawColumns(['product', 'action_badge', 'impact_formatted', 'reason_short'])
            ->make(true);
    }
}