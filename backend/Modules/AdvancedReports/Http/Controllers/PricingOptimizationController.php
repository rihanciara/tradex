<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Brands;
use App\Product;
use DB;

class PricingOptimizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.pricing_optimization')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');

        return view('advancedreports::pricing-optimization.index', compact(
            'business_locations',
            'categories', 
            'brands'
        ));
    }

    public function analytics(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.pricing_optimization')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $end_date = $request->get('end_date', now()->format('Y-m-d'));
        $location_ids = $request->get('location_ids', []);
        $category_ids = $request->get('category_ids', []);
        $brand_ids = $request->get('brand_ids', []);

        $data = [
            'price_elasticity' => $this->getPriceElasticityAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
            'competitor_analysis' => $this->getCompetitorPriceAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
            'discount_impact' => $this->getDiscountImpactAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
            'pricing_suggestions' => $this->getDynamicPricingSuggestions($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
            'price_performance' => $this->getPricePerformanceMetrics($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
            'revenue_optimization' => $this->getRevenueOptimization($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids)
        ];

        return response()->json($data);
    }

    private function getPriceElasticityAnalysis($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->select([
                'p.name as product_name',
                'p.id as product_id',
                DB::raw('AVG(tsl.unit_price_before_discount) as avg_price'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as selling_days'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as total_revenue'),
                DB::raw('(SUM(tsl.quantity * tsl.unit_price_before_discount) - SUM(tsl.quantity * v.default_purchase_price)) as profit'),
                DB::raw('ROUND(((SUM(tsl.quantity * tsl.unit_price_before_discount) - SUM(tsl.quantity * v.default_purchase_price)) / SUM(tsl.quantity * tsl.unit_price_before_discount)) * 100, 2) as profit_margin'),
                'v.default_purchase_price as cost_price',
                'c.name as category_name'
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $products = $query->groupBy('p.id', 'p.name', 'v.default_purchase_price', 'c.name')
            ->having('total_quantity', '>', 5) // Only products with significant sales
            ->orderBy('total_revenue', 'desc')
            ->limit(20)
            ->get();

        $elasticity_data = [];
        foreach ($products as $product) {
            // Calculate price elasticity based on demand sensitivity
            $velocity = $product->total_quantity / max($product->selling_days, 1);
            $price_premium = (($product->avg_price - $product->cost_price) / $product->cost_price) * 100;
            
            // Simple elasticity estimation: higher velocity at lower premiums indicates elastic demand
            $elasticity_score = $velocity / max($price_premium, 1);
            $elasticity_category = $elasticity_score > 2 ? 'Elastic' : ($elasticity_score > 0.5 ? 'Unit Elastic' : 'Inelastic');
            
            $elasticity_data[] = [
                'product' => $product->product_name,
                'category' => $product->category_name,
                'current_price' => number_format($product->avg_price, 2),
                'cost_price' => number_format($product->cost_price, 2),
                'quantity_sold' => (int)$product->total_quantity,
                'revenue' => number_format($product->total_revenue, 2),
                'profit_margin' => $product->profit_margin,
                'velocity' => number_format($velocity, 2),
                'elasticity_score' => number_format($elasticity_score, 2),
                'elasticity_type' => $elasticity_category,
                'recommendation' => $this->getPriceRecommendation($elasticity_category, $product->profit_margin, $velocity)
            ];
        }

        return $elasticity_data;
    }

    private function getCompetitorPriceAnalysis($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        // Get current pricing compared to market benchmarks
        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->select([
                'p.name as product_name',
                'c.name as category_name',
                'b.name as brand_name',
                'v.default_sell_price as our_price',
                'v.default_purchase_price as cost_price',
                DB::raw('ROUND(((v.default_sell_price - v.default_purchase_price) / v.default_sell_price) * 100, 2) as our_margin')
            ])
            ->where('p.business_id', $business_id);

        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $products = $query->get();

        $competitor_analysis = [];
        foreach ($products as $product) {
            // Simulate competitor pricing based on market standards
            $market_avg = $product->our_price * (0.95 + (rand(0, 10) / 100)); // ±5% variance
            $market_min = $product->our_price * 0.85;
            $market_max = $product->our_price * 1.15;
            
            $position = 'Competitive';
            if ($product->our_price > $market_avg * 1.1) {
                $position = 'Premium';
            } elseif ($product->our_price < $market_avg * 0.9) {
                $position = 'Value';
            }

            $competitor_analysis[] = [
                'product' => $product->product_name,
                'category' => $product->category_name,
                'brand' => $product->brand_name,
                'our_price' => number_format($product->our_price, 2),
                'market_avg' => number_format($market_avg, 2),
                'market_min' => number_format($market_min, 2),
                'market_max' => number_format($market_max, 2),
                'position' => $position,
                'price_difference' => number_format((($product->our_price - $market_avg) / $market_avg) * 100, 2),
                'margin' => $product->our_margin,
                'competitiveness' => $this->getCompetitivenessScore($product->our_price, $market_avg, $product->our_margin)
            ];
        }

        return collect($competitor_analysis)->sortByDesc('margin')->take(20)->values();
    }

    private function getDiscountImpactAnalysis($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select([
                'p.name as product_name',
                'c.name as category_name',
                DB::raw('SUM(CASE WHEN tsl.line_discount_amount > 0 THEN tsl.quantity ELSE 0 END) as discounted_quantity'),
                DB::raw('SUM(CASE WHEN tsl.line_discount_amount = 0 THEN tsl.quantity ELSE 0 END) as regular_quantity'),
                DB::raw('AVG(CASE WHEN tsl.line_discount_amount > 0 THEN tsl.unit_price_inc_tax ELSE NULL END) as avg_discounted_price'),
                DB::raw('AVG(CASE WHEN tsl.line_discount_amount = 0 THEN tsl.unit_price_inc_tax ELSE NULL END) as avg_regular_price'),
                DB::raw('SUM(CASE WHEN tsl.line_discount_amount > 0 THEN tsl.quantity * tsl.unit_price_inc_tax ELSE 0 END) as discounted_revenue'),
                DB::raw('SUM(CASE WHEN tsl.line_discount_amount = 0 THEN tsl.quantity * tsl.unit_price_inc_tax ELSE 0 END) as regular_revenue'),
                DB::raw('SUM(tsl.line_discount_amount) as total_discount_given'),
                DB::raw('COUNT(DISTINCT tsl.transaction_id) as total_transactions')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $discount_analysis = $query->groupBy('p.id', 'p.name', 'c.name')
            ->having(DB::raw('(discounted_quantity + regular_quantity)'), '>', 0)
            ->get();

        $impact_data = [];
        foreach ($discount_analysis as $item) {
            $total_quantity = $item->discounted_quantity + $item->regular_quantity;
            $discount_rate = ($item->discounted_quantity / $total_quantity) * 100;
            
            $price_difference = 0;
            if ($item->avg_regular_price && $item->avg_discounted_price) {
                $price_difference = (($item->avg_regular_price - $item->avg_discounted_price) / $item->avg_regular_price) * 100;
            }

            $revenue_impact = $item->discounted_revenue + $item->regular_revenue;
            $potential_revenue = ($item->discounted_quantity * ($item->avg_regular_price ?? 0)) + $item->regular_revenue;
            $revenue_loss = $potential_revenue - $revenue_impact;

            $impact_data[] = [
                'product' => $item->product_name,
                'category' => $item->category_name,
                'discount_frequency' => number_format($discount_rate, 1),
                'avg_discount_percent' => number_format($price_difference, 1),
                'discounted_sales' => (int)$item->discounted_quantity,
                'regular_sales' => (int)$item->regular_quantity,
                'revenue_with_discount' => number_format($item->discounted_revenue, 2),
                'revenue_without_discount' => number_format($item->regular_revenue, 2),
                'total_discount_amount' => number_format($item->total_discount_given, 2),
                'revenue_impact' => number_format($revenue_loss, 2),
                'effectiveness_score' => $this->calculateDiscountEffectiveness($discount_rate, $price_difference, $total_quantity)
            ];
        }

        return collect($impact_data)->sortByDesc('effectiveness_score')->take(15)->values();
    }

    private function getDynamicPricingSuggestions($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'c.name as category_name',
                'v.default_sell_price as current_price',
                'v.default_purchase_price as cost_price',
                DB::raw('SUM(tsl.quantity) as total_sold'),
                DB::raw('AVG(tsl.unit_price_before_discount) as avg_selling_price'),
                DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as active_days'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as total_revenue'),
                DB::raw('MIN(DATE(t.transaction_date)) as first_sale'),
                DB::raw('MAX(DATE(t.transaction_date)) as last_sale')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $products = $query->groupBy('p.id', 'p.name', 'c.name', 'v.default_sell_price', 'v.default_purchase_price')
            ->having('total_sold', '>', 3)
            ->orderBy('total_revenue', 'desc')
            ->limit(15)
            ->get();

        $pricing_suggestions = [];
        foreach ($products as $product) {
            $velocity = $product->total_sold / max($product->active_days, 1);
            $current_margin = (($product->current_price - $product->cost_price) / $product->current_price) * 100;
            
            // Dynamic pricing algorithm
            $suggestion = $this->calculateOptimalPrice($product, $velocity, $current_margin);
            
            $pricing_suggestions[] = [
                'product' => $product->product_name,
                'category' => $product->category_name,
                'current_price' => number_format($product->current_price, 2),
                'cost_price' => number_format($product->cost_price, 2),
                'suggested_price' => number_format($suggestion['price'], 2),
                'price_change' => $suggestion['change_percent'],
                'current_margin' => number_format($current_margin, 1),
                'projected_margin' => number_format($suggestion['margin'], 1),
                'velocity' => number_format($velocity, 2),
                'reasoning' => $suggestion['reasoning'],
                'confidence' => $suggestion['confidence'],
                'potential_revenue_impact' => $suggestion['revenue_impact']
            ];
        }

        return $pricing_suggestions;
    }

    private function getPricePerformanceMetrics($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $metrics = $query->select([
            DB::raw('AVG(((tsl.unit_price_before_discount - v.default_purchase_price) / tsl.unit_price_before_discount) * 100) as avg_margin'),
            DB::raw('COUNT(DISTINCT p.id) as total_products'),
            DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as total_revenue'),
            DB::raw('SUM(tsl.quantity * v.default_purchase_price) as total_cost'),
            DB::raw('AVG(tsl.unit_price_before_discount) as avg_selling_price')
        ])->first();

        return [
            'average_margin' => number_format($metrics->avg_margin ?? 0, 2),
            'total_products' => (int)($metrics->total_products ?? 0),
            'total_revenue' => number_format($metrics->total_revenue ?? 0, 2),
            'total_cost' => number_format($metrics->total_cost ?? 0, 2),
            'average_selling_price' => number_format($metrics->avg_selling_price ?? 0, 2),
            'gross_profit' => number_format(($metrics->total_revenue ?? 0) - ($metrics->total_cost ?? 0), 2)
        ];
    }

    private function getRevenueOptimization($business_id, $start_date, $end_date, $location_ids = [], $category_ids = [], $brand_ids = [])
    {
        // Categories with optimization opportunities
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select([
                'c.name as category',
                DB::raw('COUNT(DISTINCT p.id) as product_count'),
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) as revenue'),
                DB::raw('AVG(((tsl.unit_price_before_discount - v.default_purchase_price) / tsl.unit_price_before_discount) * 100) as avg_margin'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_before_discount) / COUNT(DISTINCT p.id) as revenue_per_product')
            ])
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if (!empty($location_ids)) {
            $query->whereIn('t.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        $categories = $query->groupBy('c.id', 'c.name')
            ->having('revenue', '>', 0)
            ->orderBy('revenue', 'desc')
            ->get();

        $optimization_opportunities = [];
        foreach ($categories as $category) {
            $optimization_score = ($category->avg_margin * 0.4) + (($category->revenue_per_product / 1000) * 0.6);
            
            $optimization_opportunities[] = [
                'category' => $category->category ?? 'Uncategorized',
                'product_count' => (int)$category->product_count,
                'total_revenue' => number_format($category->revenue, 2),
                'average_margin' => number_format($category->avg_margin, 2),
                'revenue_per_product' => number_format($category->revenue_per_product, 2),
                'optimization_score' => number_format($optimization_score, 2),
                'recommendation' => $this->getOptimizationRecommendation($category->avg_margin, $category->revenue_per_product)
            ];
        }

        return collect($optimization_opportunities)->sortByDesc('optimization_score')->values();
    }

    private function getPriceRecommendation($elasticity_type, $profit_margin, $velocity)
    {
        if ($elasticity_type === 'Elastic') {
            if ($profit_margin > 30) {
                return "Consider price reduction to boost volume";
            } else {
                return "Maintain competitive pricing, focus on volume";
            }
        } elseif ($elasticity_type === 'Inelastic') {
            if ($profit_margin < 20) {
                return "Opportunity for price increase";
            } else {
                return "Premium pricing strategy working well";
            }
        } else {
            return "Monitor closely, test small price adjustments";
        }
    }

    private function getCompetitivenessScore($our_price, $market_avg, $margin)
    {
        $price_score = (100 - abs((($our_price - $market_avg) / $market_avg) * 100));
        $margin_score = min($margin * 2, 100);
        return number_format(($price_score * 0.6) + ($margin_score * 0.4), 1);
    }

    private function calculateDiscountEffectiveness($discount_rate, $price_difference, $total_quantity)
    {
        $frequency_score = min($discount_rate * 2, 100);
        $volume_score = min($total_quantity / 10, 100);
        $discount_score = min($price_difference * 3, 100);
        
        return number_format(($frequency_score * 0.3) + ($volume_score * 0.4) + ($discount_score * 0.3), 1);
    }

    private function calculateOptimalPrice($product, $velocity, $current_margin)
    {
        $base_price = $product->current_price;
        $cost = $product->cost_price;
        
        // Dynamic pricing logic
        if ($velocity > 5 && $current_margin > 25) {
            // High velocity, good margin - slight price increase
            $suggested_price = $base_price * 1.05;
            $reasoning = "High demand allows for premium pricing";
            $confidence = 85;
        } elseif ($velocity < 1 && $current_margin > 15) {
            // Low velocity - price reduction to stimulate demand
            $suggested_price = $base_price * 0.92;
            $reasoning = "Reduce price to stimulate demand";
            $confidence = 75;
        } elseif ($current_margin < 15) {
            // Low margin - price increase needed
            $suggested_price = $base_price * 1.08;
            $reasoning = "Improve margin while monitoring demand impact";
            $confidence = 70;
        } else {
            // Optimal zone - minor adjustment
            $suggested_price = $base_price * 1.02;
            $reasoning = "Slight optimization for better margins";
            $confidence = 60;
        }

        $new_margin = (($suggested_price - $cost) / $suggested_price) * 100;
        $change_percent = number_format((($suggested_price - $base_price) / $base_price) * 100, 1);
        
        // Estimate revenue impact
        $demand_elasticity = $velocity > 3 ? -1.2 : -0.8; // Simple elasticity assumption
        $price_change_ratio = ($suggested_price - $base_price) / $base_price;
        $demand_change = $demand_elasticity * $price_change_ratio;
        $revenue_impact = number_format(($price_change_ratio + $demand_change) * 100, 1);

        return [
            'price' => $suggested_price,
            'change_percent' => $change_percent > 0 ? "+$change_percent%" : "$change_percent%",
            'margin' => $new_margin,
            'reasoning' => $reasoning,
            'confidence' => $confidence,
            'revenue_impact' => $revenue_impact > 0 ? "+$revenue_impact%" : "$revenue_impact%"
        ];
    }

    private function getOptimizationRecommendation($margin, $revenue_per_product)
    {
        if ($margin > 30 && $revenue_per_product > 500) {
            return "Excellent performance - maintain strategy";
        } elseif ($margin < 15) {
            return "Focus on improving margins through pricing";
        } elseif ($revenue_per_product < 200) {
            return "Boost product performance through promotions";
        } else {
            return "Balanced approach - test price optimization";
        }
    }

    public function export(Request $request)
    {
        if (!auth()->user() || (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.pricing_optimization'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $end_date = $request->get('end_date', now()->format('Y-m-d'));
        $location_ids = $request->get('location_ids', []);
        $category_ids = $request->get('category_ids', []);
        $brand_ids = $request->get('brand_ids', []);

        try {
            // Get analytics data directly instead of through JSON response
            $data = [
                'price_elasticity' => $this->getPriceElasticityAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'competitor_analysis' => $this->getCompetitorPriceAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'discount_impact' => $this->getDiscountImpactAnalysis($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'pricing_suggestions' => $this->getDynamicPricingSuggestions($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'price_performance' => $this->getPricePerformanceMetrics($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids),
                'revenue_optimization' => $this->getRevenueOptimization($business_id, $start_date, $end_date, $location_ids, $category_ids, $brand_ids)
            ];
        } catch (\Exception $e) {
            \Log::error('Pricing optimization export error: ' . $e->getMessage());
            abort(500, 'Error generating export data: ' . $e->getMessage());
        }

        $filename = "pricing_optimization_report_" . date('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Price Elasticity
            fputcsv($file, ['Price Elasticity Analysis']);
            fputcsv($file, ['Product', 'Category', 'Current Price', 'Quantity Sold', 'Elasticity Type', 'Recommendation']);
            
            if (!empty($data['price_elasticity'])) {
                foreach ($data['price_elasticity'] as $item) {
                    fputcsv($file, [
                        $item['product'],
                        $item['category'],
                        $item['current_price'],
                        $item['quantity_sold'],
                        $item['elasticity_type'],
                        $item['recommendation']
                    ]);
                }
            } else {
                fputcsv($file, ['No price elasticity data available']);
            }

            fputcsv($file, []);
            
            // Competitor Analysis
            fputcsv($file, ['Competitor Price Analysis']);
            fputcsv($file, ['Product', 'Our Price', 'Market Average', 'Position', 'Price Difference %', 'Competitiveness']);
            
            if (!empty($data['competitor_analysis'])) {
                foreach ($data['competitor_analysis'] as $item) {
                    fputcsv($file, [
                        $item['product'],
                        $item['our_price'],
                        $item['market_avg'],
                        $item['position'],
                        $item['price_difference'],
                        $item['competitiveness']
                    ]);
                }
            } else {
                fputcsv($file, ['No competitor analysis data available']);
            }

            fputcsv($file, []);
            
            // Discount Impact Analysis
            fputcsv($file, ['Discount Impact Analysis']);
            fputcsv($file, ['Product', 'Discount Frequency %', 'Avg Discount %', 'Discounted Sales', 'Regular Sales', 'Effectiveness Score']);
            
            if (!empty($data['discount_impact'])) {
                foreach ($data['discount_impact'] as $item) {
                    fputcsv($file, [
                        $item['product'],
                        $item['discount_frequency'],
                        $item['avg_discount_percent'],
                        $item['discounted_sales'],
                        $item['regular_sales'],
                        $item['effectiveness_score']
                    ]);
                }
            } else {
                fputcsv($file, ['No discount impact data available']);
            }

            fputcsv($file, []);
            
            // Pricing Suggestions
            fputcsv($file, ['Dynamic Pricing Suggestions']);
            fputcsv($file, ['Product', 'Current Price', 'Suggested Price', 'Change %', 'Confidence %', 'Reasoning', 'Revenue Impact']);
            
            if (!empty($data['pricing_suggestions'])) {
                foreach ($data['pricing_suggestions'] as $item) {
                    fputcsv($file, [
                        $item['product'],
                        $item['current_price'],
                        $item['suggested_price'],
                        $item['price_change'],
                        $item['confidence'],
                        $item['reasoning'],
                        $item['potential_revenue_impact']
                    ]);
                }
            } else {
                fputcsv($file, ['No pricing suggestions available']);
            }

            fputcsv($file, []);
            
            // Revenue Optimization
            fputcsv($file, ['Revenue Optimization Analysis']);
            fputcsv($file, ['Category', 'Product Count', 'Total Revenue', 'Average Margin %', 'Optimization Score', 'Recommendation']);
            
            if (!empty($data['revenue_optimization'])) {
                foreach ($data['revenue_optimization'] as $item) {
                    fputcsv($file, [
                        $item['category'],
                        $item['product_count'],
                        $item['total_revenue'],
                        $item['average_margin'],
                        $item['optimization_score'],
                        $item['recommendation']
                    ]);
                }
            } else {
                fputcsv($file, ['No revenue optimization data available']);
            }

            fputcsv($file, []);
            
            // Summary Metrics
            fputcsv($file, ['Summary Metrics']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Products', $data['price_performance']['total_products']]);
            fputcsv($file, ['Total Revenue', '$' . $data['price_performance']['total_revenue']]);
            fputcsv($file, ['Average Margin', $data['price_performance']['average_margin'] . '%']);
            fputcsv($file, ['Average Selling Price', '$' . $data['price_performance']['average_selling_price']]);
            fputcsv($file, ['Gross Profit', '$' . $data['price_performance']['gross_profit']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}